<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sppd;
use App\Services\DashboardStatsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class DashboardController extends Controller
{
    /**
     * Show the admin dashboard.
     *
     * Lembur and Piket status counts are scoped to rows the logged-in user
     * can actually approve (same rule as LemburDatatable/PiketDatatable) and
     * to the selected month/year filter (defaults to the current month).
     * These status cards are hidden entirely for recap users, who approve
     * nothing and only care about the total-pay charts below.
     *
     * Four monthly charts always cover a fixed trailing 12-month window,
     * independent of the month/year filter, and are each shown only to users
     * relevant to that layer: step-1 approvers, step-2 approvers, and (the
     * two total-pay charts) recap users.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Services\DashboardStatsService  $stats
     * @return \Illuminate\View\View
     */
    public function index(Request $request, DashboardStatsService $stats)
    {
        $user      = Auth::user();
        $clientIds = $user?->accessibleClientIds() ?? [];

        [$month, $periodStart, $periodEnd] = $this->resolvePeriod($request);

        $lemburCounts = $stats->statusCounts('lembur', $clientIds, $user, $periodStart, $periodEnd);
        $piketCounts  = $stats->statusCounts('piket', $clientIds, $user, $periodStart, $periodEnd);

        // --- Layer 1 / Layer 2 charts (step approvers only) ---
        $isStep1Approver = $stats->isApproverAtStep(1, $clientIds, $user);
        $chartLayer1Data = $isStep1Approver ? $stats->layerSubmissionSeries(1, $clientIds, $user) : null;

        $isStep2Approver = $stats->isApproverAtStep(2, $clientIds, $user);
        $chartLayer2Data = $isStep2Approver ? $stats->layerSubmissionSeries(2, $clientIds, $user) : null;

        // --- Layer 3 charts: total pay per month (recap users only) ---
        $isRecapUser     = (bool) Session::get('is_recap_user', false);
        $chartData       = ($isRecapUser && $clientIds) ? $stats->totalPaySeries($clientIds, 'lembur') : null;
        $chartPiketData  = ($isRecapUser && $clientIds) ? $stats->totalPaySeries($clientIds, 'piket') : null;

        // --- SPPD pending count (waiting_approval with client-step active) ---
        $sppdPending = 0;
        if ($clientIds) {
            $sppdPending = Sppd::whereIn('client_id', $clientIds)
                ->where('status', 'waiting_approval')
                ->whereHas('approvalConfig.steps', function ($q) {
                    $q->whereColumn('step_order', 'sppd.current_approval_step')
                      ->where('actor_type', 'client');
                })
                ->count();
        }

        return view('admin.dashboard', compact(
            'user',
            'month',
            'lemburCounts',
            'piketCounts',
            'isStep1Approver',
            'chartLayer1Data',
            'isStep2Approver',
            'chartLayer2Data',
            'isRecapUser',
            'chartData',
            'chartPiketData',
            'sppdPending'
        ));
    }

    /**
     * Parse the `month` (Y-m) request filter used to scope the status cards,
     * defaulting to and falling back to the current month — same convention
     * as LemburRekapController::form().
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array{0: string, 1: \Carbon\Carbon, 2: \Carbon\Carbon}
     */
    private function resolvePeriod(Request $request): array
    {
        $month = $request->input('month', Carbon::now()->format('Y-m'));

        try {
            $periodStart = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
            $periodEnd   = Carbon::createFromFormat('Y-m', $month)->endOfMonth();
        } catch (\Exception $e) {
            $periodStart = Carbon::now()->startOfMonth();
            $periodEnd   = Carbon::now()->endOfMonth();
            $month       = $periodStart->format('Y-m');
        }

        return [$month, $periodStart, $periodEnd];
    }
}
