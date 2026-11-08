<?php

namespace App\Services;

use App\Models\Sppd;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * Counts for the SPPD card on the client dashboard: SPPD On Process, SPPD Waiting Approval,
 * Approved and Rejected, over the same SPPD the client's e-SPPD list shows.
 */
class SppdDashboardStatsService
{
    /**
     * Count the SPPD of the given clients that depart within the period, split into the four
     * statuses shown on the card. A waiting SPPD is On Process while its active step belongs to
     * SIP - KPU, and Waiting Approval otherwise (client step, or an SPPD without approval config,
     * which the client can approve).
     *
     * @param  int[]  $clientIds  Clients the user may access; none means nothing to count
     * @param  Carbon  $periodStart  First day of the period (by tanggal berangkat)
     * @param  Carbon  $periodEnd  Last day of the period (by tanggal berangkat)
     * @return array{on_process:int, waiting_approval:int, approved:int, rejected:int}
     */
    public function statusCounts(array $clientIds, Carbon $periodStart, Carbon $periodEnd): array
    {
        if (empty($clientIds)) {
            return ['on_process' => 0, 'waiting_approval' => 0, 'approved' => 0, 'rejected' => 0];
        }

        $byStatus = $this->inPeriod($clientIds, $periodStart, $periodEnd)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $onProcess = $this->inPeriod($clientIds, $periodStart, $periodEnd)
            ->where('status', 'waiting_approval')
            ->whereHas('approvalConfig.steps', function ($q) {
                $q->whereColumn('step_order', 'sppd.current_approval_step')
                  ->where('actor_type', 'payroll');
            })
            ->count();

        return [
            'on_process'       => $onProcess,
            'waiting_approval' => (int) ($byStatus['waiting_approval'] ?? 0) - $onProcess,
            'approved'         => (int) ($byStatus['approved'] ?? 0),
            'rejected'         => (int) ($byStatus['rejected'] ?? 0),
        ];
    }

    /**
     * SPPD of the given clients whose tanggal berangkat falls within the period.
     *
     * @param  int[]  $clientIds
     * @param  Carbon  $periodStart
     * @param  Carbon  $periodEnd
     * @return Builder
     */
    private function inPeriod(array $clientIds, Carbon $periodStart, Carbon $periodEnd): Builder
    {
        return Sppd::query()
            ->whereIn('client_id', $clientIds)
            ->whereBetween('tanggal_berangkat', [$periodStart->toDateString(), $periodEnd->toDateString()]);
    }
}
