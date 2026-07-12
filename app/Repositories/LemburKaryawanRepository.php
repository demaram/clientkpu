<?php

namespace App\Repositories;

use App\Models\LemburApprovalConfigAssignment;
use App\Models\LemburApprovalConfigStep;
use App\Models\LemburKaryawan;

/**
 * Data access for LemburKaryawan records, shared by LemburController (type=lembur)
 * and PiketController (type=piket) since both operate on the same table.
 */
class LemburKaryawanRepository
{
    /**
     * Fetch a record with all relations needed for the detail modal.
     *
     * @param  int  $id
     * @return LemburKaryawan
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findDetail(int $id): LemburKaryawan
    {
        return LemburKaryawan::with(['user', 'client', 'checkInLocation', 'checkOutLocation', 'statusBy', 'editedBy', 'approvalLogs'])
            ->findOrFail($id);
    }

    /**
     * Whether the given user is the approver for any step in an approval config
     * (used for detail-view visibility — not restricted to the current active step).
     *
     * @param  int  $approvalConfigId
     * @param  int  $userId
     * @return bool
     */
    public function isApproverInConfig(int $approvalConfigId, int $userId): bool
    {
        return LemburApprovalConfigStep::where('lembur_approval_config_id', $approvalConfigId)
            ->where('approver_user_id', $userId)
            ->exists();
    }

    /**
     * Whether the karyawan has an active approval-config assignment for the client
     * whose config includes the given user as an approver step.
     *
     * @param  int  $karyawanId
     * @param  int  $clientId
     * @param  int  $userId
     * @return bool
     */
    public function hasActiveAssignmentForApprover(int $karyawanId, int $clientId, int $userId): bool
    {
        return LemburApprovalConfigAssignment::where('karyawan_id', $karyawanId)
            ->where('client_id', $clientId)
            ->where('is_active', true)
            ->whereHas('config.steps', function ($stepQuery) use ($userId) {
                $stepQuery->where('approver_user_id', $userId);
            })
            ->exists();
    }

    /**
     * Total number of steps configured for an approval config.
     *
     * @param  int  $approvalConfigId
     * @return int
     */
    public function countSteps(int $approvalConfigId): int
    {
        return LemburApprovalConfigStep::where('lembur_approval_config_id', $approvalConfigId)->count();
    }

    /**
     * Sum of approved counted_hours for a user within the given month/year (lembur only).
     *
     * @param  int  $userId
     * @param  int  $year
     * @param  int  $month
     * @return float
     */
    public function sumApprovedCountedHoursForMonth(int $userId, int $year, int $month): float
    {
        return (float) LemburKaryawan::where('user_id', $userId)
            ->where('status', 'approved')
            ->whereRaw('YEAR(start) = ? AND MONTH(start) = ?', [$year, $month])
            ->sum('counted_hours');
    }

    /**
     * Sum of approved counted_hours for a user within the ISO week containing $date (lembur only).
     *
     * @param  int     $userId
     * @param  string  $date  Y-m-d
     * @return float
     */
    public function sumApprovedCountedHoursForWeek(int $userId, string $date): float
    {
        return (float) LemburKaryawan::where('user_id', $userId)
            ->where('status', 'approved')
            ->whereRaw('YEARWEEK(start, 1) = YEARWEEK(?, 1)', [$date])
            ->sum('counted_hours');
    }
}
