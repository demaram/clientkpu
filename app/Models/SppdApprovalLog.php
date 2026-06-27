<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SppdApprovalLog extends Model
{
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
}
