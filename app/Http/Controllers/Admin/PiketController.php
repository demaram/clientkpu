<?php

namespace App\Http\Controllers\Admin;

use App\Datatables\PiketDatatable;
use App\Http\Controllers\Controller;
use App\Models\LemburApprovalConfig;
use App\Models\LemburKaryawan;
use App\Services\LemburKaryawanDetailService;
use App\Services\LemburKaryawanWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;

class PiketController extends Controller
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
     * Render the piket listing page or return DataTables JSON for AJAX requests.
     *
     * Passes $showOvertimePay flag so recap users can see financial data while
     * step-only approvers cannot.
     *
     * @param  Request        $request
     * @param  PiketDatatable $datatable
     * @return \Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    public function index(Request $request, PiketDatatable $datatable)
    {
        if ($request->ajax()) {
            return $datatable->render($request);
        }

        $userId = Auth::id() ?? data_get(Session::get('user'), 'id');
        $showOvertimePay = $userId
            ? LemburApprovalConfig::where('recap_user_id', $userId)->exists()
            : false;

        return view('admin.piket.index', compact('showOvertimePay'));
    }

    /**
     * Return full piket detail as JSON for the detail modal, including
     * can_act flag to control Approve/Reject button visibility.
     *
     * @param  int  $id  LemburKaryawan primary key (type=piket)
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
     * Show the edit form for a piket record.
     *
     * Guards:
     * - Client owns the piket
     * - Status must be waiting_approval
     * - Current user must be the designated approver for this step
     * - The current step must have can_edit_data = true
     *
     * @param  int  $id  LemburKaryawan primary key (type=piket)
     * @return \Illuminate\View\View
     */
    public function edit(int $id)
    {
        $piket = LemburKaryawan::with(['user', 'client'])->findOrFail($id);

        $user = Auth::user();
        $clientIds = $user->accessibleClientIds();
        if ($clientIds && !in_array($piket->client_id, $clientIds)) {
            abort(403, 'Unauthorized access');
        }

        if ($piket->status !== 'waiting_approval') {
            return redirect()->route('admin.piket.index')
                ->with('error', 'Piket ini tidak dalam status menunggu approval');
        }

        if (!$piket->approval_config_id) {
            abort(403, 'Piket ini tidak menggunakan konfigurasi approval bertahap');
        }

        $step = $this->workflow->getActiveStep($piket);

        if (!$step || $step->approver_user_id != Auth::id() || !$step->can_edit_data) {
            abort(403, 'Anda tidak berwenang mengedit data piket ini');
        }

        $startPhotoUrl = $piket->start_photo && Storage::disk('custom_public')->exists($piket->start_photo)
            ? Storage::disk('custom_public')->url($piket->start_photo)
            : null;

        $endPhotoUrl = $piket->end_photo && Storage::disk('custom_public')->exists($piket->end_photo)
            ? Storage::disk('custom_public')->url($piket->end_photo)
            : null;

        return view('admin.piket.form', compact('piket', 'startPhotoUrl', 'endPhotoUrl'))->with('act', 'edit');
    }

    /**
     * Proxy an edit (PUT) action to the Payroll API with encrypted subscription headers.
     *
     * Applies the same access + can_edit_data guards as edit().
     * Stores any uploaded photos directly to the shared custom_public disk, then sends
     * the resulting file paths to payroll PUT /api/data-piket/{id}.
     * Photos are only accepted when the existing field is null.
     * Stored files are deleted if the API call fails (rollback).
     *
     * @param  Request  $request  Must include start and end (datetime-local format); optionally start_photo/end_photo
     * @param  int      $id       LemburKaryawan primary key (type=piket)
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

        $piket = LemburKaryawan::findOrFail($id);

        $user = Auth::user();
        $clientIds = $user->accessibleClientIds();
        if ($clientIds && !in_array($piket->client_id, $clientIds)) {
            abort(403, 'Unauthorized access');
        }

        if ($piket->status !== 'waiting_approval') {
            return back()->withInput()->with('error', 'Piket ini tidak dalam status menunggu approval');
        }

        if (!$piket->approval_config_id) {
            abort(403, 'Piket ini tidak menggunakan konfigurasi approval bertahap');
        }

        $step = $this->workflow->getActiveStep($piket);

        if (!$step || $step->approver_user_id != Auth::id() || !$step->can_edit_data) {
            abort(403, 'Anda tidak berwenang mengedit data piket ini');
        }

        // Store photos only when the existing field is null
        $storedStartPhoto = null;
        $storedEndPhoto   = null;

        if ($request->hasFile('start_photo') && !$piket->start_photo) {
            $storedStartPhoto = $this->workflow->storePhoto($request->file('start_photo'), $piket->user_id, 'lembur_in');
        }

        if ($request->hasFile('end_photo') && !$piket->end_photo) {
            $storedEndPhoto = $this->workflow->storePhoto($request->file('end_photo'), $piket->user_id, 'lembur_out');
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

        $result = $this->workflow->proxyUpdate('piket', $id, $payload);

        if (!$result['success']) {
            $this->workflow->rollbackPhotos($storedStartPhoto, $storedEndPhoto);
            return back()->withInput()
                ->with('error', $result['body']['message'] ?? 'Gagal memperbarui data piket');
        }

        return redirect()->route('admin.piket.index')
            ->with('success', 'Data piket berhasil diperbarui');
    }

    /**
     * Proxy an approve action to the Payroll API with encrypted subscription headers.
     *
     * Validates step ownership before forwarding — only the designated approver
     * for the current step may approve (mirrors LemburController::approve).
     *
     * @param  int  $id  LemburKaryawan primary key (type=piket)
     * @return \Illuminate\Http\JsonResponse
     */
    public function approve($id)
    {
        $piket = LemburKaryawan::findOrFail($id);

        // Check if user has access to this Piket data
        $user = Auth::user();
        $clientIds = $user->accessibleClientIds();
        if ($clientIds && !in_array($piket->client_id, $clientIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        if ($piket->status !== 'waiting_approval') {
            return response()->json([
                'success' => false,
                'message' => 'Piket sudah diproses sebelumnya'
            ]);
        }

        // Unassigned piket (no active Assignment) has no defined approver — block
        // rather than silently allowing any client user with access to act on it.
        if (!$piket->approval_config_id) {
            return response()->json([
                'success' => false,
                'message' => 'Piket ini tidak menggunakan konfigurasi approval bertahap'
            ], 403);
        }

        // Step-aware validation: check if it's this user's turn
        $step = $this->workflow->getActiveStep($piket);

        if ($step && $step->approver_user_id != Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Bukan giliran Anda untuk approve piket ini'
            ], 403);
        }

        $result = $this->workflow->proxyApprove('piket', $id, Auth::id());

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['body']['message'] ?? 'Gagal approve piket ke Payroll API'
            ], $result['status']);
        }

        return response()->json([
            'success'      => true,
            'message'      => $result['body']['message'] ?? 'Piket berhasil di-approve',
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
     * @param  Request  $request
     * @param  int      $id  LemburKaryawan primary key (type=piket)
     * @return \Illuminate\Http\JsonResponse
     */
    public function reject(Request $request, $id)
    {
        $piket = LemburKaryawan::findOrFail($id);

        // Check if user has access to this Piket data
        $user = Auth::user();
        $clientIds = $user->accessibleClientIds();
        if ($clientIds && !in_array($piket->client_id, $clientIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        if ($piket->status !== 'waiting_approval') {
            return response()->json([
                'success' => false,
                'message' => 'Piket sudah diproses sebelumnya'
            ]);
        }

        // Unassigned piket (no active Assignment) has no defined approver — block
        // rather than silently allowing any client user with access to act on it.
        if (!$piket->approval_config_id) {
            return response()->json([
                'success' => false,
                'message' => 'Piket ini tidak menggunakan konfigurasi approval bertahap'
            ], 403);
        }

        // Step-aware validation
        $step = $this->workflow->getActiveStep($piket);

        if ($step && $step->approver_user_id != Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Bukan giliran Anda untuk reject piket ini'
            ], 403);
        }

        $result = $this->workflow->proxyReject('piket', $id, Auth::id(), $request->input('notes'));

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['body']['message'] ?? 'Gagal reject piket ke Payroll API'
            ], $result['status']);
        }

        return response()->json([
            'success' => true,
            'message' => $result['body']['message'] ?? 'Piket berhasil di-reject',
            'status'  => $result['body']['status'] ?? null,
        ]);
    }
}
