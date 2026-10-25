<?php

namespace App\Services;

use App\Models\Sppd;
use App\Models\SppdApprovalLog;

/**
 * Builds the JSON payload of the SPPD detail modal, shared by the approver's e-SPPD
 * screen and the Perekap Lembur's Report SPPD.
 */
class SppdDetailPresenter
{
    /**
     * Relations the payload reads; load them before calling {@see self::present()} to avoid N+1.
     *
     * @return string[]
     */
    public static function relations(): array
    {
        return ['karyawan', 'karyawanProject', 'approvalConfig.steps', 'costs', 'attachments', 'approvalLogs.step'];
    }

    /**
     * Turn an SPPD into the detail modal payload.
     *
     * @param  Sppd  $sppd  With {@see self::relations()} loaded
     * @param  bool  $allowAct  Whether this viewer may approve/reject at all; false for read-only screens
     * @return array<string, mixed>
     */
    public function present(Sppd $sppd, bool $allowAct): array
    {
        $totalBiaya           = $sppd->costs->sum('subtotal');
        $totalDiterimaPegawai = $sppd->costs->where('diterima_pegawai', true)->sum('subtotal');
        $karyawan             = $sppd->karyawan;
        $stepNames            = $sppd->approvalConfig ? $sppd->approvalConfig->steps->pluck('step_name', 'step_order') : collect();

        $rejectionLog = $sppd->approvalLogs
            ->where('status', 'rejected')
            ->sortByDesc('step_order')
            ->first();

        return [
            'id'                     => $sppd->id,
            'kode'                   => $sppd->kode,
            'karyawan'               => $karyawan ? trim($karyawan->first_name . ' ' . $karyawan->last_name) : '-',
            'empid'                  => $karyawan?->empid ?? '-',
            'jabatan'                => $sppd->karyawanProject?->jabatan ?? '-',
            'lokasi'                 => $sppd->lokasi_penugasan ?: '-',
            'keterangan'             => $sppd->deskripsi ?: '-',
            'tanggal_berangkat'      => $sppd->tanggal_berangkat ? date('d/m/Y', strtotime($sppd->tanggal_berangkat)) : '-',
            'tanggal_kembali'        => $sppd->tanggal_kembali ? date('d/m/Y', strtotime($sppd->tanggal_kembali)) : '-',
            'status'                 => $sppd->status,
            'step_progress'          => $this->stepProgress($sppd),
            'can_act'                => $allowAct && $this->isClientStepActive($sppd),
            'rejection_notes'        => $sppd->status === 'rejected'
                ? ($rejectionLog ? ($rejectionLog->notes ?? '-') : '-')
                : null,
            'total_biaya'            => $this->rupiah($totalBiaya),
            'total_diterima_pegawai' => $this->rupiah($totalDiterimaPegawai),
            'costs'                  => $sppd->costs->map(fn ($c) => [
                'uraian'           => $c->nama,
                'nominal'          => number_format($c->nominal ?? 0, 0, ',', '.'),
                'hari'             => $c->hari,
                'subtotal'         => number_format($c->subtotal ?? 0, 0, ',', '.'),
                'diterima_pegawai' => (bool) $c->diterima_pegawai,
            ])->values(),
            'attachments'            => $sppd->attachments->map(fn ($att) => [
                'id'        => $att->id,
                'jenis'     => $att->jenis,
                'file_name' => $att->original_name,
                'url'       => $att->url,
            ])->values(),
            'approval_logs'          => $sppd->approvalLogs->map(fn ($log) => $this->presentLog($log, $stepNames))->values(),
        ];
    }

    /**
     * One row of the Log Approval table. A Pengembalian SPPD row belongs to no step, so it has neither number nor name.
     *
     * The step name comes from the step the log was written for. That link is lost when the approval config
     * is edited (its steps are recreated), so a log without it falls back to the SPPD's config step with the same number.
     *
     * @param  SppdApprovalLog  $log  With the `step` relation loaded
     * @param  \Illuminate\Support\Collection<int, string>  $stepNames  Step names of the SPPD's approval config, keyed by step number
     * @return array<string, mixed>
     */
    private function presentLog(SppdApprovalLog $log, $stepNames): array
    {
        $isReset = $log->status === SppdApprovalLog::STATUS_RESET;

        return [
            'step_order' => $isReset ? null : $log->step_order,
            'step_name'  => $isReset ? null : ($log->step?->step_name ?? $stepNames->get($log->step_order)),
            'status'     => $log->status,
            'notes'      => $log->notes,
            'acted_from' => $log->acted_from,
            'acted_at'   => $log->acted_at ? $log->acted_at->format('d/m/Y H:i') : '-',
        ];
    }

    /**
     * "current/total" progress, or null when the SPPD has no approval config.
     *
     * @param  Sppd  $sppd
     * @return string|null
     */
    private function stepProgress(Sppd $sppd): ?string
    {
        if (!$sppd->approval_config_id || !$sppd->approvalConfig) {
            return null;
        }

        return $sppd->current_approval_step . '/' . $sppd->approvalConfig->steps->count();
    }

    /**
     * Whether the SPPD is waiting on a step that belongs to the client side.
     *
     * @param  Sppd  $sppd
     * @return bool
     */
    private function isClientStepActive(Sppd $sppd): bool
    {
        if ($sppd->status !== 'waiting_approval' || !$sppd->approval_config_id || !$sppd->approvalConfig) {
            return false;
        }

        $step = $sppd->approvalConfig->steps->where('step_order', $sppd->current_approval_step)->first();

        return $step && $step->actor_type === 'client';
    }

    /**
     * Format an amount as "Rp 1.250.000".
     *
     * @param  int|float  $amount
     * @return string
     */
    private function rupiah($amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }
}
