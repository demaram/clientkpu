<?php

namespace App\Services;

use App\Models\LemburApprovalConfigStep;
use App\Models\LemburKaryawan;
use App\Models\LemburRekap;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Aggregates the numbers shown on the client dashboard: per-status lembur/piket
 * counts scoped to what the logged-in user can actually approve (mirroring
 * LemburDatatable/PiketDatatable), and the monthly charts (approval-layer
 * submission volume for step 1/2, total pay for the recap layer, and that same
 * total pay broken down per jenis pekerjaan).
 */
class DashboardStatsService
{
    /**
     * Label used for recap rows whose project -> master_pekerjaan chain cannot
     * be resolved, so that the per-pekerjaan series always add up to the same
     * grand total as totalPaySeries().
     */
    public const UNKNOWN_PEKERJAAN = 'Tidak Diketahui';

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
     * Monthly SUM(total_pay) of approved lembur/piket rekap records for the
     * trailing 12 months (the "recap layer" charts).
     *
     * @param  int[]  $clientIds
     * @param  string  $type  'lembur' or 'piket'
     * @return array{labels: string[], values: float[]}
     */
    public function totalPaySeries(array $clientIds, string $type = 'lembur'): array
    {
        $months = $this->trailingMonths();

        $totals = LemburRekap::query()
            ->where('type', $type)
            ->whereIn('client_id', $clientIds)
            ->where('status', 'approved')
            ->whereBetween('period_start', [$this->periodStartFor($months[0]), $this->periodEndFor(end($months))])
            ->selectRaw("DATE_FORMAT(period_start, '%Y-%m') as month, SUM(total_pay) as total_pay")
            ->groupBy('month')
            ->pluck('total_pay', 'month');

        return $this->buildSeries($months, $totals);
    }

    /**
     * The same trailing-12-month pay total as totalPaySeries(), but split into
     * one series per jenis pekerjaan (OB, Driver, Admin, ...).
     *
     * `lembur_rekap.total_pay` carries no pekerjaan dimension, so this walks
     * down to the item level instead: lembur_rekap -> lembur_rekap_items ->
     * lembur_karyawan_project -> project -> master_pekerjaan. Both sides are
     * written from the same collection by LemburRekapController::approve(),
     * so the sum of every series here reconciles with totalPaySeries().
     *
     * Projects sharing a master_pekerjaan collapse into one series (a client
     * may run "Driver Direktur" and "Driver Operasional" as separate projects).
     * Items whose pekerjaan cannot be resolved are kept under
     * self::UNKNOWN_PEKERJAAN rather than dropped, so a broken project link
     * shows up on the chart instead of silently shrinking the total.
     *
     * Months are bucketed on the raw `period_start` column — always the first
     * of the month, per approve() — instead of DATE_FORMAT(), which keeps the
     * query portable to the sqlite connection the test suite runs on.
     *
     * @param  int[]  $clientIds
     * @param  string  $type  'lembur' or 'piket'
     * @return array{labels: string[], datasets: array<int, array{label: string, values: float[], is_unknown: bool}>}
     */
    public function totalPayByPekerjaanSeries(array $clientIds, string $type = 'lembur'): array
    {
        $months = $this->trailingMonths();

        $rows = DB::table('lembur_rekap as r')
            ->join('lembur_rekap_items as i', 'i.lembur_rekap_id', '=', 'r.id')
            ->join('lembur_karyawan_project as l', 'l.id', '=', 'i.lembur_id')
            ->leftJoin('project as p', 'p.id', '=', 'l.project_id')
            ->leftJoin('master_pekerjaan as mp', 'mp.id', '=', 'p.master_pekerjaan_id')
            ->where('r.type', $type)
            ->where('r.status', 'approved')
            ->whereIn('r.client_id', $clientIds)
            // Bound as date strings, not Carbon datetimes: `period_start` is a DATE
            // column, and a '...-01 00:00:00' binding drops the oldest month under
            // sqlite's string comparison (MySQL casts it, sqlite does not).
            ->whereBetween('r.period_start', [
                $this->periodStartFor($months[0])->toDateString(),
                $this->periodEndFor(end($months))->toDateString(),
            ])
            ->groupBy('mp.nama', 'r.period_start')
            ->select('mp.nama as pekerjaan', 'r.period_start as period_start')
            ->selectRaw('SUM(i.overtime_pay) as total_pay')
            ->get();

        return $this->buildMultiSeries($months, $rows);
    }

    /**
     * Ordered list of the trailing N months in 'Y-m' format, oldest first.
     *
     * Anchored to the first of the month before stepping back, because
     * Carbon's subMonths() overflows (31 Aug minus 6 months lands in March,
     * not February) and would otherwise duplicate or skip a month whenever the
     * dashboard is opened on the 29th-31st.
     *
     * @return string[]
     */
    private function trailingMonths(int $count = 12): array
    {
        $months = [];

        for ($i = $count - 1; $i >= 0; $i--) {
            $months[] = now()->startOfMonth()->subMonths($i)->format('Y-m');
        }

        return $months;
    }

    /**
     * First moment of the given 'Y-m' month.
     *
     * Parsed as an explicit 'Y-m-d' so the missing day is never filled in from
     * "today" (see trailingMonths() for the overflow this avoids).
     */
    private function periodStartFor(string $month): Carbon
    {
        return Carbon::createFromFormat('Y-m-d', $month . '-01')->startOfDay();
    }

    /**
     * Last moment of the given 'Y-m' month.
     */
    private function periodEndFor(string $month): Carbon
    {
        return Carbon::createFromFormat('Y-m-d', $month . '-01')->endOfMonth();
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
            'labels' => $this->monthLabels($months),
            'values' => array_map(fn ($m) => (float) ($valuesByMonth[$m] ?? 0), $months),
        ];
    }

    /**
     * Turn grouped {pekerjaan, period_start, total_pay} rows into Chart.js-ready
     * labels plus one dataset per pekerjaan, filling months without a rekap
     * with 0.
     *
     * Datasets are ordered by their 12-month total, largest first, so the
     * biggest spender always takes the first (most contrasting) palette colour
     * and reads at the top of the legend. The unresolved bucket is pinned last
     * and flagged with `is_unknown` so the view can grey it out without
     * string-matching the label.
     *
     * @param  string[]  $months
     * @param  \Illuminate\Support\Collection<int, \stdClass>  $rows
     * @return array{labels: string[], datasets: array<int, array{label: string, values: float[], is_unknown: bool}>}
     */
    private function buildMultiSeries(array $months, Collection $rows): array
    {
        $totalsByPekerjaan = [];

        foreach ($rows as $row) {
            $label = ($row->pekerjaan === null || $row->pekerjaan === '')
                ? self::UNKNOWN_PEKERJAAN
                : $row->pekerjaan;

            $month = Carbon::parse($row->period_start)->format('Y-m');

            $totalsByPekerjaan[$label][$month] = ($totalsByPekerjaan[$label][$month] ?? 0) + (float) $row->total_pay;
        }

        $datasets = [];

        foreach ($totalsByPekerjaan as $label => $valuesByMonth) {
            $values = array_map(fn ($m) => (float) ($valuesByMonth[$m] ?? 0), $months);

            $datasets[] = [
                'label'      => (string) $label,
                'values'     => $values,
                'is_unknown' => $label === self::UNKNOWN_PEKERJAAN,
                'total'      => array_sum($values),
            ];
        }

        usort($datasets, function (array $a, array $b) {
            if ($a['is_unknown'] !== $b['is_unknown']) {
                return $a['is_unknown'] ? 1 : -1;
            }

            return $b['total'] <=> $a['total'];
        });

        return [
            'labels'   => $this->monthLabels($months),
            'datasets' => array_map(fn (array $d) => [
                'label'      => $d['label'],
                'values'     => $d['values'],
                'is_unknown' => $d['is_unknown'],
            ], $datasets),
        ];
    }

    /**
     * Human-readable 'M Y' axis labels for a list of 'Y-m' months.
     *
     * @param  string[]  $months
     * @return string[]
     */
    private function monthLabels(array $months): array
    {
        return array_map(fn ($m) => $this->periodStartFor($m)->format('M Y'), $months);
    }
}
