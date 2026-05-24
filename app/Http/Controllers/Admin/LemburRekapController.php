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
    /**
     * Abort with 403 if the logged-in user is not a recap_user_id on any config.
     * Returns the matching config so callers can read client_id without a second query.
     *
     * @return LemburApprovalConfig
     */
    private function ensureRecapAccess(): LemburApprovalConfig
    {
        $config = LemburApprovalConfig::where('recap_user_id', Auth::id())->first();

        if (!$config) {
            abort(403, 'Anda tidak memiliki akses rekap lembur');
        }

        return $config;
    }

    /**
     * Show the historical rekap list for this client.
     *
     * Passes $approvedMonths (Y-m strings) to the view so the Add Rekap modal
     * can warn the user client-side when a month already has an approved rekap.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $config   = $this->ensureRecapAccess();
        $clientId = $config->client_id;

        $rekaps = LemburRekap::where('client_id', $clientId)
            ->orderByDesc('period_start')
            ->get();

        // Pass list of Y-m strings for months that already have an approved rekap,
        // so the JS month-picker modal can warn before redirecting.
        $approvedMonths = $rekaps
            ->where('status', 'approved')
            ->map(fn ($r) => Carbon::parse($r->period_start)->format('Y-m'))
            ->values()
            ->toArray();

        return view('admin.rekap-lembur.index', compact('rekaps', 'approvedMonths', 'config'))
            ->with('clientId', $clientId);
    }

    /**
     * Show the rekap form for a chosen month: month picker + approved lembur table.
     *
     * @param  Request  $request  Expects optional `month` (Y-m format)
     * @return \Illuminate\View\View
     */
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

    /**
     * Upsert a lembur_rekap record with status=approved and bulk-insert its items.
     *
     * Re-rekap replaces existing items via delete + bulk insert (not individual updates).
     *
     * @param  Request  $request  Expects `month` (Y-m format)
     * @return \Illuminate\Http\RedirectResponse
     */
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

    /**
     * Upsert a lembur_rekap record with status=rejected and clear its items.
     *
     * @param  Request  $request  Expects `month` (Y-m format)
     * @return \Illuminate\Http\RedirectResponse
     */
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
