<?php

namespace App\Http\Controllers\Admin;

use App\Datatables\SppdDatatable;
use App\Http\Controllers\Controller;
use App\Models\Sppd;
use App\Models\SppdApprovalConfigStep;
use App\Models\User;
use App\Services\SubscriptionCipherService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SppdController extends Controller
{
    protected SubscriptionCipherService $subscriptionCipher;

    /**
     * @param  SubscriptionCipherService  $subscriptionCipher
     */
    public function __construct(SubscriptionCipherService $subscriptionCipher)
    {
        $this->subscriptionCipher = $subscriptionCipher;
    }

    /**
     * Show the SPPD listing or return DataTables JSON for AJAX requests.
     *
     * @param  Request       $request
     * @param  SppdDatatable $sppdDatatable
     * @return \Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    public function index(Request $request, SppdDatatable $sppdDatatable)
    {
        if ($request->ajax()) {
            return $sppdDatatable->render($request);
        }

        return view('admin.sppd.index');
    }

    /**
     * Return full SPPD detail as JSON for the detail modal.
     *
     * Includes costs, attachments, approval logs, and can_act flag
     * indicating whether the current client user may approve/reject this step.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(int $id)
    {
        $user      = Auth::user();
        $clientIds = $user?->accessibleClientIds() ?? [];

        $sppd = Sppd::with([
            'karyawan',
            'approvalConfig.steps',
            'costs',
            'attachments',
            'approvalLogs',
        ])->findOrFail($id);

        if ($clientIds && !in_array($sppd->client_id, $clientIds)) {
            abort(403, 'Unauthorized access');
        }

        // Determine step progress
        $stepProgress = null;
        $canAct       = false;
        if ($sppd->approval_config_id && $sppd->approvalConfig) {
            $totalSteps   = $sppd->approvalConfig->steps->count();
            $stepProgress = $sppd->current_approval_step . '/' . $totalSteps;

            if ($sppd->status === 'waiting_approval') {
                $step   = $sppd->approvalConfig->steps
                    ->where('step_order', $sppd->current_approval_step)
                    ->first();
                $canAct = $step && $step->actor_type === 'client';
            }
        }

        // Most recent rejection log
        $rejectionLog = $sppd->approvalLogs
            ->where('status', 'rejected')
            ->sortByDesc('step_order')
            ->first();

        // Cost totals
        $totalBiaya         = $sppd->costs->sum('subtotal');
        $totalDiterimaPegawai = $sppd->costs->where('diterima_pegawai', true)->sum('subtotal');

        // Map attachments
        $attachments = $sppd->attachments->map(fn ($att) => [
            'id'        => $att->id,
            'jenis'     => $att->jenis,
            'file_name' => $att->file_name,
            'url'       => $att->url,
        ])->values();

        // Map approval logs
        $logs = $sppd->approvalLogs->map(fn ($log) => [
            'step_order'  => $log->step_order,
            'step_name'   => $log->step_name,
            'status'      => $log->status,
            'notes'       => $log->notes,
            'acted_from'  => $log->acted_from,
            'acted_at'    => $log->acted_at ? $log->acted_at->format('d/m/Y H:i') : '-',
        ])->values();

        $karyawan = $sppd->karyawan;

        return response()->json([
            'success' => true,
            'data'    => [
                'id'                   => $sppd->id,
                'kode'                 => $sppd->kode,
                'karyawan'             => $karyawan ? trim($karyawan->first_name . ' ' . $karyawan->last_name) : '-',
                'empid'                => $karyawan?->empid ?? '-',
                'jabatan'              => $karyawan?->jabatan ?? '-',
                'lokasi'               => $sppd->lokasi ?? $sppd->tujuan ?? '-',
                'keterangan'           => $sppd->keterangan ?? '-',
                'tanggal_berangkat'    => $sppd->tanggal_berangkat ? date('d/m/Y', strtotime($sppd->tanggal_berangkat)) : '-',
                'tanggal_kembali'      => $sppd->tanggal_kembali ? date('d/m/Y', strtotime($sppd->tanggal_kembali)) : '-',
                'status'               => $sppd->status,
                'step_progress'        => $stepProgress,
                'can_act'              => $canAct,
                'rejection_notes'      => $sppd->status === 'rejected'
                    ? ($rejectionLog ? ($rejectionLog->notes ?? '-') : '-')
                    : null,
                'total_biaya'          => 'Rp ' . number_format($totalBiaya, 0, ',', '.'),
                'total_diterima_pegawai' => 'Rp ' . number_format($totalDiterimaPegawai, 0, ',', '.'),
                'costs'                => $sppd->costs->map(fn ($c) => [
                    'uraian'           => $c->uraian,
                    'nominal'          => number_format($c->nominal ?? 0, 0, ',', '.'),
                    'hari'             => $c->hari,
                    'subtotal'         => number_format($c->subtotal ?? 0, 0, ',', '.'),
                    'diterima_pegawai' => (bool) $c->diterima_pegawai,
                ])->values(),
                'attachments'          => $attachments,
                'approval_logs'        => $logs,
            ],
        ]);
    }

    /**
     * Proxy an approve action to the Payroll API with encrypted subscription headers.
     * Validates that the current step is a client step before forwarding.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function approve(int $id)
    {
        $user      = Auth::user();
        $clientIds = $user?->accessibleClientIds() ?? [];

        $sppd = Sppd::with('approvalConfig.steps')->findOrFail($id);

        if ($clientIds && !in_array($sppd->client_id, $clientIds)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access'], 403);
        }

        if ($sppd->status !== 'waiting_approval') {
            return response()->json(['success' => false, 'message' => 'SPPD sudah diproses sebelumnya']);
        }

        if ($sppd->approval_config_id && $sppd->approvalConfig) {
            $step = $sppd->approvalConfig->steps
                ->where('step_order', $sppd->current_approval_step)
                ->first();
            if (!$step || $step->actor_type !== 'client') {
                return response()->json(['success' => false, 'message' => 'Bukan giliran client untuk approve SPPD ini'], 403);
            }
        }

        return $this->callPayrollApi('POST', 'api/sppd/' . $id . '/approve', [
            'status_by'   => Auth::id(),
            'status_from' => 'clientkpu',
        ], 'SPPD berhasil di-approve');
    }

    /**
     * Proxy a reject action to the Payroll API with encrypted subscription headers.
     * Validates that the current step is a client step before forwarding.
     *
     * @param  Request  $request
     * @param  int      $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function reject(Request $request, int $id)
    {
        $request->validate(['notes' => 'required|string|max:500']);

        $user      = Auth::user();
        $clientIds = $user?->accessibleClientIds() ?? [];

        $sppd = Sppd::with('approvalConfig.steps')->findOrFail($id);

        if ($clientIds && !in_array($sppd->client_id, $clientIds)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access'], 403);
        }

        if ($sppd->status !== 'waiting_approval') {
            return response()->json(['success' => false, 'message' => 'SPPD sudah diproses sebelumnya']);
        }

        if ($sppd->approval_config_id && $sppd->approvalConfig) {
            $step = $sppd->approvalConfig->steps
                ->where('step_order', $sppd->current_approval_step)
                ->first();
            if (!$step || $step->actor_type !== 'client') {
                return response()->json(['success' => false, 'message' => 'Bukan giliran client untuk reject SPPD ini'], 403);
            }
        }

        return $this->callPayrollApi('POST', 'api/sppd/' . $id . '/reject', [
            'status_by'   => Auth::id(),
            'status_from' => 'clientkpu',
            'notes'       => $request->input('notes'),
        ], 'SPPD berhasil di-reject');
    }

    /**
     * Build encrypted headers and call the Payroll API.
     * Returns a JSON response mirroring the Payroll API result on success.
     *
     * @param  string  $method
     * @param  string  $endpoint  Relative endpoint, e.g. 'api/sppd/5/approve'
     * @param  array   $payload
     * @param  string  $successMessage
     * @return \Illuminate\Http\JsonResponse
     */
    private function callPayrollApi(string $method, string $endpoint, array $payload, string $successMessage)
    {
        $payrollBaseUrl  = rtrim(config('services.payroll.api_url'));
        $subscriptionKey = (string) config('services.payroll.subscription_key', env('SUBSCRIPTION_KEY'));

        if (!$payrollBaseUrl || !$subscriptionKey) {
            return response()->json(['success' => false, 'message' => 'Konfigurasi Payroll API belum diatur'], 500);
        }

        $headers = $this->subscriptionCipher->buildHeaders($subscriptionKey, $method, $endpoint);

        if (empty($headers['X-Subscription-Encrypted'])) {
            return response()->json(['success' => false, 'message' => 'Gagal mengenkripsi subscription payload'], 500);
        }

        try {
            $response = Http::timeout(30)
                ->acceptJson()
                ->withHeaders($headers)
                ->post($payrollBaseUrl . $endpoint, $payload);

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => $response->json('message') ?? 'Gagal menghubungi Payroll API',
                ], $response->status());
            }

            return response()->json([
                'success'      => true,
                'message'      => $response->json('message') ?? $successMessage,
                'status'       => $response->json('status'),
                'current_step' => $response->json('current_step'),
                'total_steps'  => $response->json('total_steps'),
            ]);
        } catch (\Throwable $e) {
            Log::error('SPPD Payroll API call gagal', ['endpoint' => $endpoint, 'error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan saat menghubungi Payroll API'], 500);
        }
    }
}
