<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LemburApprovalConfig;
use App\Models\LemburKaryawan;
use App\Models\LemburRekap;
use App\Models\LemburRekapItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LemburRekapController extends Controller
{
    private function ensureRecapAccess(): LemburApprovalConfig
    {
        $config = LemburApprovalConfig::where('recap_user_id', Auth::id())->first();

        if (!$config) {
            abort(403, 'Anda tidak memiliki akses rekap lembur');
        }

        return $config;
    }

    public function index()
    {
        $config   = $this->ensureRecapAccess();
        $clientId = $config->client_id;

        $rekaps = LemburRekap::where('client_id', $clientId)
            ->orderByDesc('period_start')
            ->get();

        $thisMonth        = Carbon::now()->startOfMonth()->toDateString();
        $hasApprovedThisMonth = LemburRekap::where('client_id', $clientId)
            ->where('period_start', $thisMonth)
            ->where('status', 'approved')
            ->exists();

        return view('admin.rekap-lembur.index', compact('rekaps', 'hasApprovedThisMonth', 'config'))
            ->with('clientId', $clientId);
    }

    public function form(Request $request)
    {
        $config   = $this->ensureRecapAccess();
        $clientId = $config->client_id;

        $month = $request->input('month', Carbon::now()->format('Y-m'));

        try {
            $periodStart = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
            $periodEnd   = Carbon::createFromFormat('Y-m', $month)->endOfMonth();
        } catch (\Exception $e) {
            $periodStart = Carbon::now()->startOfMonth();
            $periodEnd   = Carbon::now()->endOfMonth();
            $month       = $periodStart->format('Y-m');
        }

        $lemburs = LemburKaryawan::with(['user:id,first_name,last_name,empid'])
            ->where('client_id', $clientId)
            ->where('status', 'approved')
            ->whereBetween('start', [$periodStart->format('Y-m-d 00:00:00'), $periodEnd->format('Y-m-d 23:59:59')])
            ->orderBy('start')
            ->get();

        $totalPay = $lemburs->sum('overtime_pay');

        // Check existing rekap for this period
        $existingRekap = LemburRekap::where('client_id', $clientId)
            ->where('period_start', $periodStart->toDateString())
            ->first();

        return view('admin.rekap-lembur.form', compact(
            'lemburs', 'totalPay', 'month', 'periodStart', 'periodEnd',
            'existingRekap', 'config'
        ));
    }

    public function approve(Request $request)
    {
        $config   = $this->ensureRecapAccess();
        $clientId = $config->client_id;

        $month = $request->input('month');

        try {
            $periodStart = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
            $periodEnd   = Carbon::createFromFormat('Y-m', $month)->endOfMonth();
        } catch (\Exception $e) {
            return back()->with('error', 'Periode tidak valid');
        }

        $lemburs = LemburKaryawan::where('client_id', $clientId)
            ->where('status', 'approved')
            ->whereBetween('start', [$periodStart->format('Y-m-d 00:00:00'), $periodEnd->format('Y-m-d 23:59:59')])
            ->get();

        if ($lemburs->isEmpty()) {
            return back()->with('error', 'Tidak ada data lembur yang dapat direkap untuk periode ini');
        }

        $totalPay = $lemburs->sum('overtime_pay');

        $rekap = LemburRekap::updateOrCreate(
            ['client_id' => $clientId, 'period_start' => $periodStart->toDateString()],
            [
                'recap_user_id' => Auth::id(),
                'period_end'    => $periodEnd->toDateString(),
                'total_lembur'  => $lemburs->count(),
                'total_pay'     => $totalPay,
                'status'        => 'approved',
                'actioned_at'   => now(),
            ]
        );

        $rekap->items()->delete();

        $items = $lemburs->map(fn ($l) => [
            'lembur_rekap_id' => $rekap->id,
            'lembur_id'       => $l->id,
            'overtime_pay'    => $l->overtime_pay,
            'counted_hours'   => $l->counted_hours,
            'created_at'      => now(),
            'updated_at'      => now(),
        ])->toArray();

        LemburRekapItem::insert($items);

        return redirect()->route('admin.rekap-lembur.index')
            ->with('success', 'Rekap lembur bulan ' . $periodStart->translatedFormat('F Y') . ' berhasil di-approve');
    }

    public function reject(Request $request)
    {
        $config   = $this->ensureRecapAccess();
        $clientId = $config->client_id;

        $month = $request->input('month');

        try {
            $periodStart = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
            $periodEnd   = Carbon::createFromFormat('Y-m', $month)->endOfMonth();
        } catch (\Exception $e) {
            return back()->with('error', 'Periode tidak valid');
        }

        LemburRekap::updateOrCreate(
            ['client_id' => $clientId, 'period_start' => $periodStart->toDateString()],
            [
                'recap_user_id' => Auth::id(),
                'period_end'    => $periodEnd->toDateString(),
                'total_lembur'  => 0,
                'total_pay'     => 0,
                'status'        => 'rejected',
                'actioned_at'   => now(),
            ]
        );

        // Rejected rekap has no items
        $rekap = LemburRekap::where('client_id', $clientId)
            ->where('period_start', $periodStart->toDateString())
            ->first();
        if ($rekap) {
            $rekap->items()->delete();
        }

        return redirect()->route('admin.rekap-lembur.index')
            ->with('success', 'Rekap lembur bulan ' . $periodStart->translatedFormat('F Y') . ' di-reject');
    }
}
