<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SppdApprovalConfigStep extends Model
{
    protected $table = 'sppd_approval_config_steps';

    protected $guarded = [];

    protected $casts = [
        'can_edit_data' => 'boolean',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function config()
    {
        return $this->belongsTo(SppdApprovalConfig::class, 'sppd_approval_config_id');
    }
}
