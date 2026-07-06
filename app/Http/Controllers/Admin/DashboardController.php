<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LemburKaryawan;
use App\Models\LemburRekap;
use App\Models\Sppd;
use App\Models\SppdApprovalConfigStep;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class DashboardController extends Controller
{

    /**
     * Show the admin dashboard.
     *
     * For all users: displays user biodata and lembur status counts.
     * For recap users: additionally renders a monthly bar chart showing
     * SUM(total_pay) of approved lembur_rekap records over the last 12 months,
     * scoped to the logged-in user's client_id.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $user      = Auth::user();
        $clientIds = $user?->accessibleClientIds() ?? [];

        // --- Lembur status counts (all users) ---
        $statusCounts = LemburKaryawan::query()
            ->where('type', 'lembur')
            ->when($clientIds, function ($query) use ($clientIds) {
                return $query->whereIn('client_id', $clientIds);
            })
            ->selectRaw("status, COUNT(*) as total")
            ->groupBy('status')
            ->pluck('total', 'status');

        $lemburCounts = [
            'pending'          => (int) ($statusCounts['pending'] ?? 0),
            'waiting_approval' => (int) ($statusCounts['waiting_approval'] ?? 0),
            'approved'         => (int) ($statusCounts['approved'] ?? 0),
            'rejected'         => (int) ($statusCounts['rejected'] ?? 0),
        ];

        // --- Chart data (recap users only) ---
        $isRecapUser = (bool) Session::get('is_recap_user', false);
        $chartData   = null;

        if ($isRecapUser && $clientIds) {
            $start = now()->subMonths(11)->startOfMonth();
            $end   = now()->endOfMonth();

            // Build ordered 12-month label array: oldest → newest
            $months = [];
            for ($i = 11; $i >= 0; $i--) {
                $months[] = now()->subMonths($i)->format('Y-m');
            }

            // Aggregate approved rekap totals per calendar month
            $rekapByMonth = LemburRekap::whereIn('client_id', $clientIds)
                ->where('status', 'approved')
                ->whereBetween('period_start', [$start, $end])
                ->selectRaw("DATE_FORMAT(period_start, '%Y-%m') as month, SUM(total_pay) as total_pay")
                ->groupBy('month')
                ->pluck('total_pay', 'month');

            $chartData = [
                'labels' => array_map(
                    fn ($m) => Carbon::createFromFormat('Y-m', $m)->format('M Y'),
                    $months
                ),
                'values' => array_map(
                    fn ($m) => (float) ($rekapByMonth[$m] ?? 0),
                    $months
                ),
            ];
        }

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

        return view('admin.dashboard', compact('user', 'lemburCounts', 'isRecapUser', 'chartData', 'sppdPending'));
    }
}
