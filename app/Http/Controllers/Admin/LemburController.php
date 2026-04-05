<?php

namespace App\Http\Controllers\Admin;

use App\Datatables\LemburDatatable;
use App\Http\Controllers\Controller;
use App\Models\LemburKaryawan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Services\SubscriptionCipherService;

class LemburController extends Controller
{
    protected SubscriptionCipherService $subscriptionCipher;

    public function __construct(SubscriptionCipherService $subscriptionCipher)
    {
        $this->subscriptionCipher = $subscriptionCipher;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, LemburDatatable $lemburDatatable)
    {
        if ($request->ajax()) {
            return $lemburDatatable->render($request);
        }

        return view('admin.lembur.index');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $lembur = LemburKaryawan::with(['user', 'client', 'checkInLocation', 'checkOutLocation', 'statusBy'])
            ->findOrFail($id);

        // Check if user has access to this lembur data
        $user = User::with('areas')->find(Auth::id());
        if ($user->id_client && $lembur->client_id != $user->id_client) {
            abort(403, 'Unauthorized access');
        }

        // Generate photo URLs using custom_public disk
        $startPhotoUrl = null;
        if ($lembur->start_photo) {
            if (Storage::disk('custom_public')->exists($lembur->start_photo)) {
                $startPhotoUrl = Storage::disk('custom_public')->url($lembur->start_photo);
            }
        }

        $endPhotoUrl = null;
        if ($lembur->end_photo) {
            if (Storage::disk('custom_public')->exists($lembur->end_photo)) {
                $endPhotoUrl = Storage::disk('custom_public')->url($lembur->end_photo);
            }
        }

        // Calculate duration
        $durasi = '-';
        if ($lembur->start && $lembur->end) {
            $startTime = strtotime($lembur->start);
            $endTime = strtotime($lembur->end);
            $durasiDetik = $endTime - $startTime;
            
            $hours = floor($durasiDetik / 3600);
            $minutes = floor(($durasiDetik % 3600) / 60);
            
            $durasi = $hours . ' jam ' . $minutes . ' menit';
        }

        // Get employee details
        $karyawan = $lembur->user;
        $empId = $karyawan->emp_id ?? '-';
        $jabatan = $karyawan->jabatan ?? '-';
        $nomorRekening = $karyawan->no_rekening ?? '-';

        // Live lembur statistics (approved only)
        $lemburDate = Carbon::parse($lembur->start);

        $monthlyCountedHours = (float) LemburKaryawan::where('user_id', $lembur->user_id)
            ->where('status', 'approved')
            ->whereRaw('YEAR(start) = ? AND MONTH(start) = ?', [$lemburDate->year, $lemburDate->month])
            ->sum('counted_hours');

        $weeklyCountedHours = (float) LemburKaryawan::where('user_id', $lembur->user_id)
            ->where('status', 'approved')
            ->whereRaw('YEARWEEK(start, 1) = YEARWEEK(?, 1)', [$lemburDate->format('Y-m-d')])
            ->sum('counted_hours');

        $formatHours = function (float $hours): string {
            $totalMinutes = (int) round($hours * 60);
            $h = intdiv($totalMinutes, 60);
            $m = $totalMinutes % 60;
            return $h . ' jam ' . $m . ' menit';
        };

        return response()->json([
            'success' => true,
            'data' => [
                'id'                => $lembur->id,
                'client'            => $lembur->client->nama ?? '-',
                'karyawan'          => $karyawan ? $karyawan->first_name . ' ' . $karyawan->last_name : '-',
                'empid'             => $empId,
                'jabatan'           => $jabatan,
                'rekening'          => $nomorRekening,
                'type'              => ucfirst($lembur->type),
                'tanggal'           => date('d/m/Y', strtotime($lembur->start)),
                'start_time'        => date('H:i', strtotime($lembur->start)),
                'end_time'          => $lembur->end ? date('H:i', strtotime($lembur->end)) : '-',
                'durasi'            => $durasi,
                'overtime_pay'      => $lembur->overtime_pay ? 'Rp ' . number_format($lembur->overtime_pay, 0, ',', '.') : '-',
                'status'            => ucfirst($lembur->status),
                'status_at'         => in_array($lembur->status, ['approved', 'rejected']) && $lembur->status_at
                    ? date('d/m/Y H:i', strtotime($lembur->status_at))
                    : null,
                'status_by_name'    => in_array($lembur->status, ['approved', 'rejected']) && $lembur->statusBy
                    ? $lembur->statusBy->name
                    : null,
                'alasan'            => $lembur->alasan ?? '-',
                'start_photo'       => $startPhotoUrl,
                'end_photo'         => $endPhotoUrl,
                'check_in_location' => $lembur->checkInLocation ? [
                    'latitude'  => $lembur->checkInLocation->latitude,
                    'longitude' => $lembur->checkInLocation->longitude,
                    'address'   => $lembur->checkInLocation->address,
                ] : null,
                'check_out_location' => $lembur->checkOutLocation ? [
                    'latitude'  => $lembur->checkOutLocation->latitude,
                    'longitude' => $lembur->checkOutLocation->longitude,
                    'address'   => $lembur->checkOutLocation->address,
                ] : null,
                'monthly_counted_hours' => $formatHours($monthlyCountedHours),
                'weekly_counted_hours'  => $formatHours($weeklyCountedHours),
                'monthly_period'        => $lemburDate->translatedFormat('F Y'),
                'weekly_period'         => 'Minggu ke-' . $lemburDate->weekOfYear . ' ' . $lemburDate->year,
            ]
        ]);
    }

    /**
     * Approve lembur request
     */
    public function approve($id)
    {
        $lembur = LemburKaryawan::findOrFail($id);

        // Check if user has access to this lembur data
        $user = Auth::user();
        if ($user->id_client && $lembur->client_id != $user->id_client) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        if ($lembur->status !== 'waiting_approval') {
            return response()->json([
                'success' => false,
                'message' => 'Lembur sudah diproses sebelumnya'
            ]);
        }

        $payrollBaseUrl = rtrim(config('services.payroll.api_url'));
        
        $subscriptionKey = (string) config('services.payroll.subscription_key', env('SUBSCRIPTION_KEY'));
        
        if (!$payrollBaseUrl || !$subscriptionKey) {
            return response()->json([
                'success' => false,
                'message' => 'Konfigurasi Payroll API atau Subscription Key belum diatur'
            ], 500);
        }

        $endpoint = 'api/data-lembur/approve/' . $id;
        
        $headers = $this->subscriptionCipher->buildHeaders($subscriptionKey, 'POST', $endpoint);
        
        if (empty($headers['X-Subscription-Encrypted'])) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengenkripsi subscription payload'
            ], 500);
        }

        try {
            $response = Http::timeout(30)
                ->acceptJson()
                ->withHeaders($headers)
                ->post($payrollBaseUrl . $endpoint, ['status_by' => Auth::id(),'status_from' => 'client']);
            
            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => $response->json('message') ?? 'Gagal approve lembur ke Payroll API'
                ], $response->status());
            }

            $lembur->status = 'approved';
            $lembur->save();

            return response()->json([
                'success' => true,
                'message' => $response->json('message') ?? 'Lembur berhasil di-approve',
                'data' => $response->json('data')
            ]);
        } catch (\Throwable $e) {
            Log::error('Approve lembur payroll API gagal', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghubungi Payroll API'
            ], 500);
        }
    }

    /**
     * Reject lembur request
     */
    public function reject($id)
    {
        $lembur = LemburKaryawan::findOrFail($id);

        // Check if user has access to this lembur data
        $user = Auth::user();
        if ($user->id_client && $lembur->client_id != $user->id_client) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        if ($lembur->status !== 'waiting_approval') {
            return response()->json([
                'success' => false,
                'message' => 'Lembur sudah diproses sebelumnya'
            ]);
        }

        $payrollBaseUrl = config('services.payroll.api_url');
        $subscriptionKey = (string) config('services.payroll.subscription_key', env('SUBSCRIPTION_KEY'));
        
        if (!$payrollBaseUrl || !$subscriptionKey) {
            return response()->json([
                'success' => false,
                'message' => 'Konfigurasi Payroll API atau Subscription Key belum diatur'
            ], 500);
        }

        $endpoint = 'api/data-lembur/reject/' . $id;
        $headers = $this->subscriptionCipher->buildHeaders($subscriptionKey, 'POST', $endpoint);
        
        if (empty($headers['X-Subscription-Encrypted'])) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengenkripsi subscription payload'
            ], 500);
        }

        try {
            $response = Http::timeout(30)
                ->acceptJson()
                ->withHeaders($headers)
                ->post($payrollBaseUrl . $endpoint, ['status_by' => Auth::id(),'status_from' => 'client']);

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => $response->json('message') ?? 'Gagal reject lembur ke Payroll API'
                ], $response->status());
            }

            $lembur->status = 'rejected';
            $lembur->save();

            return response()->json([
                'success' => true,
                'message' => $response->json('message') ?? 'Lembur berhasil di-reject',
                'data' => $response->json('data')
            ]);
        } catch (\Throwable $e) {
            Log::error('Reject lembur payroll API gagal', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghubungi Payroll API'
            ], 500);
        }
    }
}
