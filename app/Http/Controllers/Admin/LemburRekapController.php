<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LemburApprovalConfig;
use App\Models\LemburApprovalConfigStep;
use App\Models\LemburKaryawan;
use App\Models\LemburRekap;
use App\Models\LemburRekapItem;
use App\Services\LemburKaryawanDetailService;
use App\Services\LemburKaryawanWorkflowService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Recap (rekap) of approved overtime records for a client, grouped by month.
 *
 * Handles type='lembur' recaps. Extended by PiketRekapController (type='piket')
 * so the two recaps stay independent per (client_id, period_start, type) —
 * previously a single recap silently mixed lembur and piket totals together.
 * Both the view folder and route name follow the same "rekap-{type}" naming,
 * so they're derived from $type rather than hardcoded.
 *
 * A client can also split recap duty across several recap users (different
 * lembur_approval_configs naming different recap_user_id) — lembur_rekap rows are
 * further scoped by recap_user_id (uq_client_period_type_recap_user) so each recap
 * user gets their own row per (client, period, type) instead of overwriting each
 * other's totals.
 */
class LemburRekapController extends Controller
{
    /**
     * Record type this controller recaps ('lembur' or 'piket').
     */
    protected string $type = 'lembur';

    public function __construct(
        protected LemburKaryawanWorkflowService $workflow,
        protected LemburKaryawanDetailService $detailService,
    ) {}

    /**
     * Dot-notation prefix shared by both the view folder ("admin.rekap-lembur")
     * and the route name group ("admin.rekap-lembur.*").
     */
    protected function prefix(): string
    {
        return "admin.rekap-{$this->type}";
    }

    /**
     * Abort with 403 if the logged-in user is not a recap_user_id on any config.
     *
     * A client can have several active configs, and different configs can name
     * different recap_user_id — so this returns every config owned by the logged-in
     * user, not just one. Callers use it both for client_id and to scope
     * LemburKaryawan queries to approval_config_id IN (owned config ids), so a
     * recap user only ever sees TAD covered by their own config(s) — never
     * Unassigned records or records under a config owned by someone else.
     *
     * @return \Illuminate\Support\Collection<int, LemburApprovalConfig>
     */
    private function ensureRecapAccess(): \Illuminate\Support\Collection
    {
        $configs = LemburApprovalConfig::where('recap_user_id', Auth::id())->get(['id', 'client_id']);

        if ($configs->isEmpty()) {
            abort(403, "Anda tidak memiliki akses rekap {$this->type}");
        }

        return $configs;
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
        $configs  = $this->ensureRecapAccess();
        $clientId = $configs->first()->client_id;

        $rekaps = LemburRekap::where('client_id', $clientId)
            ->where('type', $this->type)
            ->where('recap_user_id', Auth::id())
            ->orderByDesc('period_start')
            ->get();

        // Pass list of Y-m strings for months that already have an approved rekap,
        // so the JS month-picker modal can warn before redirecting.
        $approvedMonths = $rekaps
            ->where('status', 'approved')
            ->map(fn ($r) => Carbon::parse($r->period_start)->format('Y-m'))
            ->values()
            ->toArray();

        return view($this->prefix() . '.index', compact('rekaps', 'approvedMonths', 'configs'))
            ->with('clientId', $clientId);
    }

    /**
     * Show the rekap form for a chosen month: month picker + approved records table.
     *
     * @param  Request  $request  Expects optional `month` (Y-m format)
     * @return \Illuminate\View\View
     */
    public function form(Request $request)
    {
        $configs   = $this->ensureRecapAccess();
        $clientId  = $configs->first()->client_id;
        $configIds = $configs->pluck('id');

        $month = $request->input('month', Carbon::now()->format('Y-m'));

        try {
            $periodStart = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
            $periodEnd   = Carbon::createFromFormat('Y-m', $month)->endOfMonth();
        } catch (\Exception $e) {
            $periodStart = Carbon::now()->startOfMonth();
            $periodEnd   = Carbon::now()->endOfMonth();
            $month       = $periodStart->format('Y-m');
        }

        // Include waiting_approval alongside approved so the recap user can see
        // in-flight records before they try to approve the recap — see approve()
        // below, which blocks the whole recap while any of these are still pending.
        // 'history:id,jabatan' is eager-loaded for the Pekerjaan column (same
        // source as LemburDatatable::pekerjaan) to avoid N+1 across the month's rows.
        // 'project.pekerjaan' is eager-loaded for the Jenis Pekerjaan column —
        // same project -> master_pekerjaan chain the recap dashboard chart uses.
        $lemburs = LemburKaryawan::with(['user:id,first_name,last_name,empid', 'history:id,jabatan', 'project.pekerjaan'])
            ->where('client_id', $clientId)
            ->where('type', $this->type)
            ->whereIn('status', ['approved', 'waiting_approval'])
            ->whereIn('approval_config_id', $configIds)
            ->whereBetween('start', [$periodStart->format('Y-m-d 00:00:00'), $periodEnd->format('Y-m-d 23:59:59')])
            ->orderBy('start')
            ->get();

        // Only approved records contribute a final overtime_pay figure.
        $totalPay = $lemburs->where('status', 'approved')->sum('overtime_pay');

        // Bulk step-count lookup (one query for all configs in view) instead of
        // querying per row, so the "waiting_approval (step/total)" badge doesn't N+1.
        $stepCounts = LemburApprovalConfigStep::whereIn('lembur_approval_config_id', $configIds)
            ->selectRaw('lembur_approval_config_id, COUNT(*) as cnt')
            ->groupBy('lembur_approval_config_id')
            ->pluck('cnt', 'lembur_approval_config_id');

        $lemburs->each(function ($l) use ($stepCounts) {
            $l->total_steps = $l->approval_config_id ? ($stepCounts[$l->approval_config_id] ?? 1) : 1;
        });

        // Check existing rekap for this period (scoped to this recap user — a client can
        // split recap duty across several recap users, each with their own row)
        $existingRekap = LemburRekap::where('client_id', $clientId)
            ->where('type', $this->type)
            ->where('period_start', $periodStart->toDateString())
            ->where('recap_user_id', Auth::id())
            ->first();

        return view($this->prefix() . '.form', compact(
            'lemburs', 'totalPay', 'month', 'periodStart', 'periodEnd',
            'existingRekap', 'configs'
        ));
    }

    /**
     * Display the detail page for a single overtime recap, grouped by employee.
     * Requires recap_user_id access — same gate as form/approve.
     *
     * @param  int  $id  LemburRekap primary key
     * @return \Illuminate\View\View
     */
    public function detail(int $id)
    {
        $configs  = $this->ensureRecapAccess();
        $clientId = $configs->first()->client_id;

        // Scope to this client + type + recap_user_id to prevent cross-client/cross-type/
        // cross-recap-user ID enumeration (a client can have several recap users, each only
        // allowed to see their own rekap rows)
        $rekap = LemburRekap::with(['client', 'recapUser'])
            ->where('client_id', $clientId)
            ->where('type', $this->type)
            ->where('recap_user_id', Auth::id())
            ->findOrFail($id);

        $items = LemburRekapItem::with(['lembur.user'])
            ->where('lembur_rekap_id', $id)
            ->get()
            ->sortBy('lembur.start');

        $grouped = $items->groupBy(fn ($item) => $item->lembur?->user_id);

        return view($this->prefix() . '.detail', compact('rekap', 'grouped'));
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
        $configs   = $this->ensureRecapAccess();
        $clientId  = $configs->first()->client_id;
        $configIds = $configs->pluck('id');

        $month = $request->input('month');

        try {
            $periodStart = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
            $periodEnd   = Carbon::createFromFormat('Y-m', $month)->endOfMonth();
        } catch (\Exception $e) {
            return back()->with('error', 'Periode tidak valid');
        }

        $lemburs = LemburKaryawan::with(['user:id,first_name,last_name,empid'])
            ->where('client_id', $clientId)
            ->where('type', $this->type)
            ->whereIn('status', ['approved', 'waiting_approval'])
            ->whereIn('approval_config_id', $configIds)
            ->whereBetween('start', [$periodStart->format('Y-m-d 00:00:00'), $periodEnd->format('Y-m-d 23:59:59')])
            ->get();

        if ($lemburs->isEmpty()) {
            return back()->with('error', "Tidak ada data {$this->type} yang dapat direkap untuk periode ini");
        }

        // Block the whole recap while any record in the period is still mid-flight —
        // approving now would silently exclude it and understate total_pay.
        $pending = $lemburs->where('status', 'waiting_approval');

        if ($pending->isNotEmpty()) {
            $pendingList = $pending->values()->map(fn ($l) => [
                'kode' => $l->kode,
                'nama' => $l->user ? trim($l->user->first_name . ' ' . $l->user->last_name) : '-',
            ])->toArray();

            return back()
                ->with('error', "Approve rekap {$this->type} gagal — masih ada data yang belum di-approve/reject sepenuhnya.")
                ->with('pending_list', $pendingList);
        }

        $approvedLemburs = $lemburs->where('status', 'approved');

        $totalPay = $approvedLemburs->sum('overtime_pay');

        $rekap = LemburRekap::updateOrCreate(
            [
                'client_id'     => $clientId,
                'period_start'  => $periodStart->toDateString(),
                'type'          => $this->type,
                'recap_user_id' => Auth::id(),
            ],
            [
                'period_end'   => $periodEnd->toDateString(),
                'total_lembur' => $approvedLemburs->count(),
                'total_pay'    => $totalPay,
                'status'       => 'approved',
                'actioned_at'  => now(),
            ]
        );

        $rekap->items()->delete();

        $items = $approvedLemburs->map(fn ($l) => [
            'lembur_rekap_id' => $rekap->id,
            'lembur_id'       => $l->id,
            'overtime_pay'    => $l->overtime_pay,
            'counted_hours'   => $l->counted_hours,
            'created_at'      => now(),
            'updated_at'      => now(),
        ])->values()->toArray();

        LemburRekapItem::insert($items);

        return redirect()->route($this->prefix() . '.index')
            ->with('success', "Rekap {$this->type} bulan " . $periodStart->translatedFormat('F Y') . ' berhasil di-approve');
    }

    /**
     * Reopen an approved record back to waiting_approval at its first approval
     * step ("Request Update" button on the rekap form). Only approved records
     * owned by this recap_user's config(s) are eligible.
     *
     * Delegates the actual status transition to payroll via LemburApprovalService::
     * requestUpdate() (proxied through LemburKaryawanWorkflowService), the same
     * way approve/reject of individual records already do — payroll stays the
     * single owner of approval-state transitions and their audit log.
     *
     * @param  Request  $request  Expects `lembur_id`, `reason` (required), `month` (Y-m, for redirect back)
     * @return \Illuminate\Http\RedirectResponse
     */
    public function requestUpdate(Request $request)
    {
        $request->validate([
            'lembur_id' => 'required|integer',
            'reason'    => 'required|string|max:1000',
        ]);

        $configs   = $this->ensureRecapAccess();
        $configIds = $configs->pluck('id');
        $month     = $request->input('month');

        $lembur = LemburKaryawan::where('type', $this->type)
            ->whereIn('approval_config_id', $configIds)
            ->find($request->input('lembur_id'));

        if (!$lembur) {
            return redirect()->route($this->prefix() . '.form', ['month' => $month])
                ->with('error', "Data {$this->type} tidak ditemukan atau bukan bagian dari rekap Anda");
        }

        if ($lembur->status !== 'approved') {
            return redirect()->route($this->prefix() . '.form', ['month' => $month])
                ->with('error', "Data {$this->type} ini tidak dalam status approved");
        }

        $result = $this->workflow->proxyRequestUpdate($this->type, $lembur->id, Auth::id(), $request->input('reason'));

        if (!$result['success']) {
            return redirect()->route($this->prefix() . '.form', ['month' => $month])
                ->with('error', $result['body']['message'] ?? "Gagal request update {$this->type}");
        }

        return redirect()->route($this->prefix() . '.form', ['month' => $month])
            ->with('success', "Request update {$this->type} berhasil dikirim, menunggu approval ulang dari step pertama");
    }

    /**
     * AJAX JSON detail for the "Detail" button on the rekap form's table.
     *
     * Access is scoped to configs owned by this recap_user (ensureRecapAccess()),
     * not step-approver visibility like admin.lembur.show — a recap_user isn't
     * necessarily an approver on the record's config. See
     * LemburKaryawanDetailService::getDetailForRecapUser().
     *
     * @param  int  $id  LemburKaryawan primary key
     * @return \Illuminate\Http\JsonResponse
     */
    public function detailAjax(int $id)
    {
        $configs   = $this->ensureRecapAccess();
        $configIds = $configs->pluck('id');

        $data = $this->detailService->getDetailForRecapUser($id, Auth::user(), $configIds);

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * Reject a single record directly from its row action button on the rekap
     * form — mirrors LemburController::reject()/PiketController::reject() (same
     * step-approver validation, same payroll proxy), just entered from the rekap
     * page instead of the Lembur/Piket page.
     *
     * Allowed for both 'waiting_approval' and 'approved' rows — a recap_user can
     * reject a record that already went through full approval, not just one
     * still mid-flight (payroll's LemburApprovalService::reject() accepts both).
     *
     * Only usable while this period's rekap hasn't been approved yet — once
     * approved, further changes should go through the normal Lembur/Piket
     * approver page instead of this shortcut.
     *
     * @param  Request  $request  Expects `lembur_id`, `notes` (required reason)
     * @return \Illuminate\Http\JsonResponse
     */
    public function rejectRecord(Request $request)
    {
        $request->validate([
            'lembur_id' => 'required|integer',
            'notes'     => 'required|string|max:1000',
        ]);

        $configs   = $this->ensureRecapAccess();
        $configIds = $configs->pluck('id');

        $lembur = LemburKaryawan::where('type', $this->type)
            ->whereIn('approval_config_id', $configIds)
            ->find($request->input('lembur_id'));

        if (!$lembur) {
            return response()->json([
                'success' => false,
                'message' => "Data {$this->type} tidak ditemukan atau bukan bagian dari rekap Anda",
            ], 404);
        }

        if (!in_array($lembur->status, ['waiting_approval', 'approved'], true)) {
            return response()->json([
                'success' => false,
                'message' => "Data {$this->type} ini tidak dalam status yang dapat di-reject",
            ]);
        }

        $periodStart = Carbon::parse($lembur->start)->startOfMonth()->toDateString();

        $existingRekap = LemburRekap::where('client_id', $lembur->client_id)
            ->where('type', $this->type)
            ->where('period_start', $periodStart)
            ->where('recap_user_id', Auth::id())
            ->first();

        if ($existingRekap && $existingRekap->status === 'approved') {
            return response()->json([
                'success' => false,
                'message' => "Rekap {$this->type} periode ini sudah di-approve, tidak bisa reject data lagi",
            ], 403);
        }

        // skip_actor_check=true: a recap_user is not necessarily the current step's
        // approver, so payroll's step-ownership check doesn't apply to this action.
        $result = $this->workflow->proxyReject($this->type, $lembur->id, Auth::id(), $request->input('notes'), true);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['body']['message'] ?? "Gagal reject {$this->type}",
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => $result['body']['message'] ?? "Data {$this->type} berhasil di-reject",
        ]);
    }
}
