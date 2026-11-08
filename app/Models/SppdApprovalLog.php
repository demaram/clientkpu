<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class SppdApprovalLog extends Model
{
    use SoftDeletes;

    /** Status of the log row written when an Approved SPPD is returned to Waiting Approval (Pengembalian SPPD). */
    public const STATUS_RESET = 'reset';

    protected $table = 'sppd_approval_logs';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'acted_at' => 'datetime',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function sppd()
    {
        return $this->belongsTo(Sppd::class, 'sppd_id');
    }

    /**
     * The approval step this log was written for (holds the step name). Null on a Pengembalian SPPD row.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function step()
    {
        return $this->belongsTo(SppdApprovalConfigStep::class, 'step_id');
    }

    /**
     * The user who approved, rejected or returned the SPPD (KPU staff or client user).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    /**
     * Name of the user who acted, for the Approver column: first + last name, or the account's `name`
     * when those are blank (KPU staff accounts often only fill `name`), or "-" when unknown.
     * Needs the `approver` relation loaded.
     *
     * @return string
     */
    public function approverName(): string
    {
        $user = $this->approver;

        if (!$user) {
            return '-';
        }

        $fullName = trim(trim((string) $user->first_name) . ' ' . trim((string) $user->last_name));

        return $fullName !== '' ? $fullName : (trim((string) $user->name) ?: '-');
    }

    /**
     * Asal Approval of this action: "SIP - KPU" for actions taken in SIP, "Primova - <Nama Client>" for
     * actions taken by the client in Primova. Same labels as the Log Approval in SIP - KPU.
     *
     * @param  string|null  $clientName  Name of the SPPD's client from master data client
     * @return string
     */
    public function actedFromLabel(?string $clientName): string
    {
        return match ($this->acted_from) {
            'payroll'   => 'SIP - KPU',
            'clientkpu' => $clientName ? 'Primova - ' . $clientName : 'Primova',
            null, ''    => '-',
            default     => $this->acted_from,
        };
    }
}
