<?php

namespace App\Http\Controllers\Admin;

use App\Datatables\LemburDatatable;
use App\Http\Controllers\Controller;
use App\Models\LemburApprovalConfig;
use App\Models\LemburKaryawan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use App\Services\LemburKaryawanDetailService;
use App\Services\LemburKaryawanWorkflowService;

class LemburController extends Controller
{
    protected LemburKaryawanWorkflowService $workflow;
    protected LemburKaryawanDetailService $detailService;

    /**
     * @param  LemburKaryawanWorkflowService  $workflow
     * @param  LemburKaryawanDetailService    $detailService
     */
    public function __construct(LemburKaryawanWorkflowService $workflow, LemburKaryawanDetailService $detailService)
    {
        $this->workflow = $workflow;
        $this->detailService = $detailService;
    }

    /**
     * Render the lembur listing page or return DataTables JSON for AJAX requests.
     *
     * Passes $showOvertimePay flag so recap users can see financial data while
     * step-only approvers cannot.
     *
     * @param  Request         $request
     * @param  LemburDatatable $lemburDatatable
     * @return \Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    public function index(Request $request, LemburDatatable $lemburDatatable)
    {
        if ($request->ajax()) {
            return $lemburDatatable->render($request);
        }

        $userId = Auth::id() ?? data_get(Session::get('user'), 'id');
        $showOvertimePay = $userId
            ? LemburApprovalConfig::where('recap_user_id', $userId)->exists()
            : false;

        return view('admin.lembur.index', compact('showOvertimePay'));
    }

    /**
     * Return full lembur detail as JSON for the detail modal, including
     * can_act flag to control Approve/Reject button visibility.
     *
     * @param  int  $id  LemburKaryawan primary key
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $data = $this->detailService->getDetail((int) $id, Auth::user());

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * Show the edit form for a lembur record.
     *
     * Guards:
     * - Client owns the lembur
     * - Status must be waiting_approval
     * - Current user must be the designated approver for this step
     * - The current step must have can_edit_data = true
     *
     * @param  int  $id  LemburKaryawan primary key
     * @return \Illuminate\View\View
     */
    public function edit(int $id)
    {
        $lembur = LemburKaryawan::with(['user', 'client'])->findOrFail($id);

        $user = Auth::user();
        $clientIds = $user->accessibleClientIds();
        if ($clientIds && !in_array($lembur->client_id, $clientIds)) {
            abort(403, 'Unauthorized access');
        }

        if ($lembur->status !== 'waiting_approval') {
            return redirect()->route('admin.lembur.index')
                ->with('error', 'Lembur ini tidak dalam status menunggu approval');
        }

        if (!$lembur->approval_config_id) {
            abort(403, 'Lembur ini tidak menggunakan konfigurasi approval bertahap');
        }

        $step = $this->workflow->getActiveStep($lembur);

        if (!$step || $step->approver_user_id != Auth::id() || !$step->can_edit_data) {
            abort(403, 'Anda tidak berwenang mengedit data lembur ini');
        }

        $startPhotoUrl = $lembur->start_photo && Storage::disk('custom_public')->exists($lembur->start_photo)
            ? Storage::disk('custom_public')->url($lembur->start_photo)
            : null;

        $endPhotoUrl = $lembur->end_photo && Storage::disk('custom_public')->exists($lembur->end_photo)
            ? Storage::disk('custom_public')->url($lembur->end_photo)
            : null;

        return view('admin.lembur.form', compact('lembur', 'startPhotoUrl', 'endPhotoUrl'))->with('act', 'edit');
    }

    /**
     * Proxy an edit (PUT) action to the Payroll API with encrypted subscription headers.
     *
     * Applies the same access + can_edit_data guards as edit().
     * Stores any uploaded photos directly to the shared custom_public disk, then sends
     * the resulting file paths to payroll PUT /api/data-lembur/{id}.
     * Photos are only accepted when the existing field is null.
     * Stored files are deleted if the API call fails (rollback).
     *
     * @param  Request  $request  Must include start and end (datetime-local format); optionally start_photo/end_photo
     * @param  int      $id       LemburKaryawan primary key
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, int $id)
    {
        $request->validate([
            'start'       => ['required', 'date_format:Y-m-d\TH:i'],
            'end'         => ['required', 'date_format:Y-m-d\TH:i', 'after:start'],
            'start_photo' => ['nullable', 'image', 'max:5120'],
            'end_photo'   => ['nullable', 'image', 'max:5120'],
        ]);

        $lembur = LemburKaryawan::findOrFail($id);

        $user = Auth::user();
        $clientIds = $user->accessibleClientIds();
        if ($clientIds && !in_array($lembur->client_id, $clientIds)) {
            abort(403, 'Unauthorized access');
        }

        if ($lembur->status !== 'waiting_approval') {
            return back()->withInput()->with('error', 'Lembur ini tidak dalam status menunggu approval');
        }

        if (!$lembur->approval_config_id) {
            abort(403, 'Lembur ini tidak menggunakan konfigurasi approval bertahap');
        }

        $step = $this->workflow->getActiveStep($lembur);

        if (!$step || $step->approver_user_id != Auth::id() || !$step->can_edit_data) {
            abort(403, 'Anda tidak berwenang mengedit data lembur ini');
        }

        // Store photos only when the existing field is null
        $storedStartPhoto = null;
        $storedEndPhoto   = null;

        if ($request->hasFile('start_photo') && !$lembur->start_photo) {
            $storedStartPhoto = $this->workflow->storePhoto($request->file('start_photo'), $lembur->user_id, 'lembur_in');
        }

        if ($request->hasFile('end_photo') && !$lembur->end_photo) {
            $storedEndPhoto = $this->workflow->storePhoto($request->file('end_photo'), $lembur->user_id, 'lembur_out');
        }

        // Convert datetime-local (Y-m-d\TH:i) to MySQL datetime (Y-m-d H:i:s)
        $startFormatted = date('Y-m-d H:i:s', strtotime($request->input('start')));
        $endFormatted   = date('Y-m-d H:i:s', strtotime($request->input('end')));

        $payload = [
            'start'     => $startFormatted,
            'end'       => $endFormatted,
            'status_by' => Auth::id(),
        ];

        if ($storedStartPhoto) {
            $payload['start_photo'] = $storedStartPhoto;
        }

        if ($storedEndPhoto) {
            $payload['end_photo'] = $storedEndPhoto;
        }

        $result = $this->workflow->proxyUpdate('lembur', $id, $payload);

        if (!$result['success']) {
            $this->workflow->rollbackPhotos($storedStartPhoto, $storedEndPhoto);
            return back()->withInput()
                ->with('error', $result['body']['message'] ?? 'Gagal memperbarui data lembur');
        }

        return redirect()->route('admin.lembur.index')
            ->with('success', 'Data lembur berhasil diperbarui');
    }

    /**
     * Proxy an approve action to the Payroll API with encrypted subscription headers.
     *
     * Validates step ownership before forwarding. Returns the Payroll API's
     * current_step and total_steps in the response so the client can update
     * the status label without reloading.
     *
     * @param  int  $id  LemburKaryawan primary key
     * @return \Illuminate\Http\JsonResponse
     */
    public function approve($id)
    {
        $lembur = LemburKaryawan::findOrFail($id);

        // Check if user has access to this lembur data
        $user = Auth::user();
        $clientIds = $user->accessibleClientIds();
        if ($clientIds && !in_array($lembur->client_id, $clientIds)) {
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

        // Unassigned lembur (no active Assignment) has no defined approver — block
        // rather than silently allowing any client user with access to act on it.
        if (!$lembur->approval_config_id) {
            return response()->json([
                'success' => false,
                'message' => 'Lembur ini tidak menggunakan konfigurasi approval bertahap'
            ], 403);
        }

        // Step-aware validation: check if it's this user's turn
        $step = $this->workflow->getActiveStep($lembur);

        if ($step && $step->approver_user_id != Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Bukan giliran Anda untuk approve lembur ini'
            ], 403);
        }

        $result = $this->workflow->proxyApprove('lembur', $id, Auth::id());

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['body']['message'] ?? 'Gagal approve lembur ke Payroll API'
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => $result['body']['message'] ?? 'Lembur berhasil di-approve',
            'status'       => $result['body']['status'] ?? null,
            'current_step' => $result['body']['current_step'] ?? null,
            'total_steps'  => $result['body']['total_steps'] ?? null,
        ]);
    }

    /**
     * Proxy a reject action to the Payroll API with encrypted subscription headers.
     *
     * Validates step ownership before forwarding.
     *
     * @param  int  $id  LemburKaryawan primary key
     * @return \Illuminate\Http\JsonResponse
     */
    public function reject(Request $request, $id)
    {
        $lembur = LemburKaryawan::findOrFail($id);

        // Check if user has access to this lembur data
        $user = Auth::user();
        $clientIds = $user->accessibleClientIds();
        if ($clientIds && !in_array($lembur->client_id, $clientIds)) {
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

        // Unassigned lembur (no active Assignment) has no defined approver — block
        // rather than silently allowing any client user with access to act on it.
        if (!$lembur->approval_config_id) {
            return response()->json([
                'success' => false,
                'message' => 'Lembur ini tidak menggunakan konfigurasi approval bertahap'
            ], 403);
        }

        // Step-aware validation
        $step = $this->workflow->getActiveStep($lembur);

        if ($step && $step->approver_user_id != Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Bukan giliran Anda untuk reject lembur ini'
            ], 403);
        }

        $result = $this->workflow->proxyReject('lembur', $id, Auth::id(), $request->input('notes'));

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['body']['message'] ?? 'Gagal reject lembur ke Payroll API'
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => $result['body']['message'] ?? 'Lembur berhasil di-reject',
            'status'  => $result['body']['status'] ?? null,
        ]);
    }
}
