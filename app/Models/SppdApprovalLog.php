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
}
