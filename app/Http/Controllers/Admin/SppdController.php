<?php

namespace App\Http\Controllers\Admin;

use App\Datatables\SppdDatatable;
use App\Http\Controllers\Controller;
use App\Models\Sppd;
use App\Models\SppdApprovalConfigStep;
use App\Models\User;
use App\Services\SppdDetailPresenter;
use App\Services\SubscriptionCipherService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SppdController extends Controller
{
    protected SubscriptionCipherService $subscriptionCipher;

    protected SppdDetailPresenter $detailPresenter;

    /**
     * @param  SubscriptionCipherService  $subscriptionCipher
     * @param  SppdDetailPresenter  $detailPresenter
     */
    public function __construct(SubscriptionCipherService $subscriptionCipher, SppdDetailPresenter $detailPresenter)
    {
        $this->subscriptionCipher = $subscriptionCipher;
        $this->detailPresenter    = $detailPresenter;
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

        $sppd = Sppd::with(SppdDetailPresenter::relations())->findOrFail($id);

        if ($clientIds && !in_array($sppd->client_id, $clientIds)) {
            abort(403, 'Unauthorized access');
        }

        return response()->json([
            'success' => true,
            'data'    => $this->detailPresenter->present($sppd, true),
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
