<?php

namespace App\Http\Controllers\Admin;

use App\Datatables\PiketDatatable;
use App\Http\Controllers\Controller;
use App\Models\LemburKaryawan;
use App\Services\LemburKaryawanWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PiketController extends Controller
{
    protected LemburKaryawanWorkflowService $workflow;

    /**
     * @param  LemburKaryawanWorkflowService  $workflow
     */
    public function __construct(LemburKaryawanWorkflowService $workflow)
    {
        $this->workflow = $workflow;
    }

    /**
     * Render the piket listing page or return DataTables JSON for AJAX requests.
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

        return view('admin.piket.index');
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
        $piket = LemburKaryawan::with(['user', 'client', 'checkInLocation', 'checkOutLocation', 'statusBy', 'editedBy', 'approvalLogs'])
            ->findOrFail($id);

        // Check if user has access to this Piket data
        $user = Auth::user()->load('areas');
        $clientIds = $user->accessibleClientIds();
        if ($clientIds && !in_array($piket->client_id, $clientIds)) {
            abort(403, 'Unauthorized access');
        }

        // Generate photo URLs using custom_public disk
        $startPhotoUrl = null;
        if ($piket->start_photo) {
            if (Storage::disk('custom_public')->exists($piket->start_photo)) {
                $startPhotoUrl = Storage::disk('custom_public')->url($piket->start_photo);
            }
        }

        $endPhotoUrl = null;
        if ($piket->end_photo) {
            if (Storage::disk('custom_public')->exists($piket->end_photo)) {
                $endPhotoUrl = Storage::disk('custom_public')->url($piket->end_photo);
            }
        }

        // Calculate duration
        $durasi = '-';
        if ($piket->start && $piket->end) {
            $startTime = strtotime($piket->start);
            $endTime = strtotime($piket->end);
            $durasiDetik = $endTime - $startTime;

            $hours = floor($durasiDetik / 3600);
            $minutes = floor(($durasiDetik % 3600) / 60);

            $durasi = $hours . ' jam ' . $minutes . ' menit';
        }

        // Get employee details
        $karyawan = $piket->user;
        $empId = $karyawan->emp_id ?? '-';
        $jabatan = $karyawan->jabatan ?? '-';
        $nomorRekening = $karyawan->no_rekening ?? '-';

        // Step progress info
        $stepProgress = null;
        if ($piket->approval_config_id) {
            $totalStepsCount = \App\Models\LemburApprovalConfigStep::where('lembur_approval_config_id', $piket->approval_config_id)->count();
            $stepProgress = $piket->current_approval_step . '/' . $totalStepsCount;
        }

        // Most recent rejection log
        $rejectionLog = $piket->approvalLogs->where('status', 'rejected')->sortByDesc('step_order')->first();

        // Determine if current user can act on this piket
        $canAct = false;
        $step   = null;
        if ($piket->status === 'waiting_approval') {
            if ($piket->approval_config_id) {
                $step   = $this->workflow->getActiveStep($piket);
                $canAct = $step && $step->approver_user_id == Auth::id();
            } else {
                $canAct = true;
            }
        }

        $canEdit = $canAct && $piket->approval_config_id && $step && $step->can_edit_data;

        return response()->json([
            'success' => true,
            'data' => [
                'id'                => $piket->id,
                'client'            => $piket->client->nama ?? '-',
                'karyawan'          => $karyawan ? $karyawan->first_name . ' ' . $karyawan->last_name : '-',
                'empid'             => $empId,
                'jabatan'           => $jabatan,
                'rekening'          => $nomorRekening,
                'type'              => ucfirst($piket->type),
                'tanggal'           => date('d/m/Y', strtotime($piket->start)),
                'start_time'        => date('H:i', strtotime($piket->start)),
                'end_time'          => $piket->end ? date('H:i', strtotime($piket->end)) : '-',
                'durasi'            => $durasi,
                'overtime_pay'      => $piket->overtime_pay ? 'Rp ' . number_format($piket->overtime_pay, 0, ',', '.') : '-',
                'status'            => ucfirst($piket->status),
                'status_at'         => in_array($piket->status, ['approved', 'rejected']) && $piket->status_at
                    ? date('d/m/Y H:i', strtotime($piket->status_at))
                    : null,
                'status_by_name'    => in_array($piket->status, ['approved', 'rejected']) && $piket->statusBy
                    ? $piket->statusBy->name
                    : null,
                'status_from'      => in_array($piket->status, ['approved', 'rejected']) && $piket->status_from
                    ? ucfirst($piket->status_from)
                    : null,
                'edited_by_name'    => $piket->edited_by && $piket->editedBy
                    ? $piket->editedBy->name
                    : null,
                'edited_at'         => $piket->edited_at
                    ? date('d/m/Y H:i', strtotime($piket->edited_at))
                    : null,
                'alasan'            => $piket->alasan ?? '-',
                'start_photo'       => $startPhotoUrl,
                'end_photo'         => $endPhotoUrl,
                'check_in_location' => $piket->checkInLocation ? [
                    'latitude'  => $piket->checkInLocation->latitude,
                    'longitude' => $piket->checkInLocation->longitude,
                    'address'   => $piket->checkInLocation->address,
                ] : null,
                'check_out_location' => $piket->checkOutLocation ? [
                    'latitude'  => $piket->checkOutLocation->latitude,
                    'longitude' => $piket->checkOutLocation->longitude,
                    'address'   => $piket->checkOutLocation->address,
                ] : null,
                'step_progress'   => $stepProgress,
                'can_act'         => $canAct,
                'can_edit'        => $canEdit,
                'rejection_notes' => $piket->status === 'rejected'
                    ? ($rejectionLog ? ($rejectionLog->notes ?? '-') : '-')
                    : null,
            ]
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

        // Step-aware validation: check if it's this user's turn
        if ($piket->approval_config_id) {
            $step = $this->workflow->getActiveStep($piket);

            if ($step && $step->approver_user_id != Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bukan giliran Anda untuk approve piket ini'
                ], 403);
            }
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

        // Step-aware validation
        if ($piket->approval_config_id) {
            $step = $this->workflow->getActiveStep($piket);

            if ($step && $step->approver_user_id != Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bukan giliran Anda untuk reject piket ini'
                ], 403);
            }
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
