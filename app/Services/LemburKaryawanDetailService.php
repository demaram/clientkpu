<?php

namespace App\Services;

use App\Models\LemburKaryawan;
use App\Models\User;
use App\Repositories\LemburKaryawanRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Builds the detail payload shown in the Lembur/Piket detail modals, shared by
 * LemburController::show() and PiketController::show() since both operate on
 * the same LemburKaryawan model (distinguished by `type`) with identical rules.
 */
class LemburKaryawanDetailService
{
    public function __construct(
        private readonly LemburKaryawanRepository $repository,
        private readonly LemburKaryawanWorkflowService $workflow,
    ) {
    }

    /**
     * Build the full detail array for the detail modal, enforcing the same
     * visibility rules as the corresponding Datatable's applyApprovalVisibility()
     * so this JSON endpoint can't be used to bypass the list's row filtering.
     *
     * @param  int   $id       LemburKaryawan primary key
     * @param  User  $authUser Currently authenticated user
     * @return array
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException  403 when unauthorized
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getDetail(int $id, User $authUser): array
    {
        $record = $this->repository->findDetail($id);

        $this->assertClientAccessible($record, $authUser);
        $this->assertVisibleToApprover($record, $authUser);

        [$startPhotoUrl, $endPhotoUrl] = $this->resolvePhotoUrls($record);
        $karyawan = $record->user;

        $stepProgress = $record->approval_config_id
            ? $record->current_approval_step . '/' . $this->repository->countSteps($record->approval_config_id)
            : null;

        $rejectionLog = $record->approvalLogs->where('status', 'rejected')->sortByDesc('step_order')->first();

        [$canAct, $canEdit] = $this->resolveActionPermissions($record, $authUser);

        $data = [
            'id'                => $record->id,
            'client'            => $record->client->nama ?? '-',
            'karyawan'          => $karyawan ? $karyawan->first_name . ' ' . $karyawan->last_name : '-',
            'empid'             => $karyawan->emp_id ?? '-',
            'jabatan'           => $karyawan->jabatan ?? '-',
            'rekening'          => $karyawan->no_rekening ?? '-',
            'type'              => ucfirst($record->type),
            'tanggal'           => date('d/m/Y', strtotime($record->start)),
            'start_time'        => date('H:i', strtotime($record->start)),
            'end_time'          => $record->end ? date('H:i', strtotime($record->end)) : '-',
            'durasi'            => $this->formatDuration($record),
            'overtime_pay'      => $record->overtime_pay ? 'Rp ' . number_format($record->overtime_pay, 0, ',', '.') : '-',
            'status'            => ucfirst($record->status),
            'status_at'         => in_array($record->status, ['approved', 'rejected']) && $record->status_at
                ? date('d/m/Y H:i', strtotime($record->status_at))
                : null,
            'status_by_name'    => in_array($record->status, ['approved', 'rejected']) && $record->statusBy
                ? $record->statusBy->name
                : null,
            'status_from'      => in_array($record->status, ['approved', 'rejected']) && $record->status_from
                ? ucfirst($record->status_from)
                : null,
            'edited_by_name'    => $record->edited_by && $record->editedBy
                ? $record->editedBy->name
                : null,
            'edited_at'         => $record->edited_at
                ? date('d/m/Y H:i', strtotime($record->edited_at))
                : null,
            'alasan'            => $record->alasan ?? '-',
            'start_photo'       => $startPhotoUrl,
            'end_photo'         => $endPhotoUrl,
            'check_in_location' => $record->checkInLocation ? [
                'latitude'  => $record->checkInLocation->latitude,
                'longitude' => $record->checkInLocation->longitude,
                'address'   => $record->checkInLocation->address,
            ] : null,
            'check_out_location' => $record->checkOutLocation ? [
                'latitude'  => $record->checkOutLocation->latitude,
                'longitude' => $record->checkOutLocation->longitude,
                'address'   => $record->checkOutLocation->address,
            ] : null,
            'step_progress'   => $stepProgress,
            'can_act'         => $canAct,
            'can_edit'        => $canEdit,
            'rejection_notes' => $record->status === 'rejected'
                ? ($rejectionLog ? ($rejectionLog->notes ?? '-') : '-')
                : null,
        ];

        // Live monthly/weekly overtime-hour stats only apply to lembur, not piket.
        if ($record->type === 'lembur') {
            $data = array_merge($data, $this->lemburHourStats($record));
        }

        return $data;
    }

    /**
     * Abort 403 if the record's client isn't in the user's accessible client list.
     */
    private function assertClientAccessible(LemburKaryawan $record, User $authUser): void
    {
        $clientIds = $authUser->accessibleClientIds();
        if ($clientIds && !in_array($record->client_id, $clientIds)) {
            abort(403, 'Unauthorized access');
        }
    }

    /**
     * Abort 403 unless the user is a valid approver for this record — either a step
     * in the already-resolved approval config, or (for a still-pending record with
     * no approval_config_id yet) a step in the karyawan's active Assignment config.
     */
    private function assertVisibleToApprover(LemburKaryawan $record, User $authUser): void
    {
        if ($record->approval_config_id) {
            if (!$this->repository->isApproverInConfig($record->approval_config_id, $authUser->id)) {
                abort(403, 'Unauthorized access');
            }
            return;
        }

        if (!$this->repository->hasActiveAssignmentForApprover($record->user_id, $record->client_id, $authUser->id)) {
            abort(403, 'Unauthorized access');
        }
    }

    /**
     * Determine whether the current user can act on (approve/reject) and/or edit
     * this record, based on whether it's waiting approval and whose turn it is.
     *
     * @return array{0: bool, 1: bool}  [canAct, canEdit]
     */
    private function resolveActionPermissions(LemburKaryawan $record, User $authUser): array
    {
        $canAct = false;
        $step = null;

        if ($record->status === 'waiting_approval') {
            if ($record->approval_config_id) {
                $step = $this->workflow->getActiveStep($record);
                $canAct = $step && $step->approver_user_id == $authUser->id;
            } else {
                $canAct = true;
            }
        }

        $canEdit = $canAct && $record->approval_config_id && $step && $step->can_edit_data;

        return [$canAct, $canEdit];
    }

    /**
     * Resolve public URLs for start/end photos stored on the custom_public disk.
     *
     * @return array{0: ?string, 1: ?string}  [startPhotoUrl, endPhotoUrl]
     */
    private function resolvePhotoUrls(LemburKaryawan $record): array
    {
        $startPhotoUrl = $record->start_photo && Storage::disk('custom_public')->exists($record->start_photo)
            ? Storage::disk('custom_public')->url($record->start_photo)
            : null;

        $endPhotoUrl = $record->end_photo && Storage::disk('custom_public')->exists($record->end_photo)
            ? Storage::disk('custom_public')->url($record->end_photo)
            : null;

        return [$startPhotoUrl, $endPhotoUrl];
    }

    /**
     * Human-readable "X jam Y menit" duration between start and end, or "-" if
     * either is missing.
     */
    private function formatDuration(LemburKaryawan $record): string
    {
        if (!$record->start || !$record->end) {
            return '-';
        }

        $durasiDetik = strtotime($record->end) - strtotime($record->start);
        $hours = floor($durasiDetik / 3600);
        $minutes = floor(($durasiDetik % 3600) / 60);

        return $hours . ' jam ' . $minutes . ' menit';
    }

    /**
     * Live monthly/weekly approved-overtime-hour stats for the record's karyawan,
     * anchored to the record's own start date.
     */
    private function lemburHourStats(LemburKaryawan $record): array
    {
        $lemburDate = Carbon::parse($record->start);

        $monthlyCountedHours = $this->repository->sumApprovedCountedHoursForMonth(
            $record->user_id,
            $lemburDate->year,
            $lemburDate->month
        );

        $weeklyCountedHours = $this->repository->sumApprovedCountedHoursForWeek(
            $record->user_id,
            $lemburDate->format('Y-m-d')
        );

        return [
            'monthly_counted_hours' => $this->formatHours($monthlyCountedHours),
            'weekly_counted_hours'  => $this->formatHours($weeklyCountedHours),
            'monthly_period'        => $lemburDate->translatedFormat('F Y'),
            'weekly_period'         => 'Minggu ke-' . $lemburDate->weekOfYear . ' ' . $lemburDate->year,
        ];
    }

    /**
     * Format a float hour count as "H jam M menit".
     */
    private function formatHours(float $hours): string
    {
        $totalMinutes = (int) round($hours * 60);
        $h = intdiv($totalMinutes, 60);
        $m = $totalMinutes % 60;

        return $h . ' jam ' . $m . ' menit';
    }
}
