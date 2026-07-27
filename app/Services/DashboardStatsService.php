<?php

namespace App\Services;

use App\Models\LemburApprovalConfigStep;
use App\Models\LemburKaryawan;
use App\Models\LemburRekap;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Aggregates the numbers shown on the client dashboard: per-status lembur/piket
 * counts scoped to what the logged-in user can actually approve (mirroring
 * LemburDatatable/PiketDatatable), and the three monthly charts (approval-layer
 * submission volume for step 1/2, total pay for the recap layer).
 */
class DashboardStatsService
{
    public function __construct(private LemburApprovalVisibilityService $visibility)
    {
    }

    /**
     * Count lembur/piket rows by status for the given period, scoped to rows
     * the user is allowed to see (same rule as the datatables).
     *
     * @param  string  $type  'lembur' or 'piket'
     * @param  int[]  $clientIds
     * @return array{pending:int, waiting_approval:int, approved:int, rejected:int}
     */
    public function statusCounts(string $type, array $clientIds, User $user, Carbon $periodStart, Carbon $periodEnd): array
    {
        $counts = LemburKaryawan::query()
            ->where('type', $type)
            ->when($clientIds, fn ($q) => $q->whereIn('client_id', $clientIds))
            ->where(function ($q) use ($user) {
                $this->visibility->apply($q, $user);
            })
            ->whereBetween('start', [$periodStart, $periodEnd])
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'pending'          => (int) ($counts['pending'] ?? 0),
            'waiting_approval' => (int) ($counts['waiting_approval'] ?? 0),
            'approved'         => (int) ($counts['approved'] ?? 0),
            'rejected'         => (int) ($counts['rejected'] ?? 0),
        ];
    }

    /**
     * Whether the user is configured as the approver for the given
     * `step_order` ("layer") in any of their accessible clients' approval
     * configs — used to decide whether that layer's chart is shown at all.
     *
     * @param  int[]  $clientIds
     */
    public function isApproverAtStep(int $stepOrder, array $clientIds, User $user): bool
    {
        if (empty($clientIds)) {
            return false;
        }

        return LemburApprovalConfigStep::where('step_order', $stepOrder)
            ->where('approver_user_id', $user->id)
            ->whereHas('config', fn ($q) => $q->whereIn('client_id', $clientIds))
            ->exists();
    }

    /**
     * Monthly count of lembur submissions (any status) routed through the
     * given approval step ("layer"), for the trailing 12 months.
     *
     * @param  int[]  $clientIds
     * @return array{labels: string[], values: float[]}
     */
    public function layerSubmissionSeries(int $stepOrder, array $clientIds, User $user): array
    {
        $months = $this->trailingMonths();

        $counts = LemburKaryawan::query()
            ->where('type', 'lembur')
            ->whereIn('client_id', $clientIds)
            ->whereHas('approvalConfig.steps', function ($q) use ($stepOrder, $user) {
                $q->where('step_order', $stepOrder)->where('approver_user_id', $user->id);
            })
            ->whereBetween('start', [$this->periodStartFor($months[0]), $this->periodEndFor(end($months))])
            ->selectRaw("DATE_FORMAT(start, '%Y-%m') as month, COUNT(*) as total")
            ->groupBy('month')
            ->pluck('total', 'month');

        return $this->buildSeries($months, $counts);
    }

    /**
     * Monthly SUM(total_pay) of approved lembur rekap records for the
     * trailing 12 months (the "recap layer" chart).
     *
     * @param  int[]  $clientIds
     * @return array{labels: string[], values: float[]}
     */
    public function totalPaySeries(array $clientIds): array
    {
        $months = $this->trailingMonths();

        $totals = LemburRekap::query()
            ->where('type', 'lembur')
            ->whereIn('client_id', $clientIds)
            ->where('status', 'approved')
            ->whereBetween('period_start', [$this->periodStartFor($months[0]), $this->periodEndFor(end($months))])
            ->selectRaw("DATE_FORMAT(period_start, '%Y-%m') as month, SUM(total_pay) as total_pay")
            ->groupBy('month')
            ->pluck('total_pay', 'month');

        return $this->buildSeries($months, $totals);
    }

    /**
     * Ordered list of the trailing N months in 'Y-m' format, oldest first.
     *
     * @return string[]
     */
    private function trailingMonths(int $count = 12): array
    {
        $months = [];

        for ($i = $count - 1; $i >= 0; $i--) {
            $months[] = now()->subMonths($i)->format('Y-m');
        }

        return $months;
    }

    private function periodStartFor(string $month): Carbon
    {
        return Carbon::createFromFormat('Y-m', $month)->startOfMonth();
    }

    private function periodEndFor(string $month): Carbon
    {
        return Carbon::createFromFormat('Y-m', $month)->endOfMonth();
    }

    /**
     * Turn a {month => value} lookup into Chart.js-ready labels/values,
     * defaulting missing months to 0.
     *
     * @param  string[]  $months
     * @return array{labels: string[], values: float[]}
     */
    private function buildSeries(array $months, Collection $valuesByMonth): array
    {
        return [
            'labels' => array_map(fn ($m) => Carbon::createFromFormat('Y-m', $m)->format('M Y'), $months),
            'values' => array_map(fn ($m) => (float) ($valuesByMonth[$m] ?? 0), $months),
        ];
    }
}
