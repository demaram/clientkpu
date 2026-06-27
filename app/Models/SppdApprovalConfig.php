<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SppdApprovalConfig extends Model
{
    protected $table = 'sppd_approval_configs';

    protected $guarded = [];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function steps()
    {
        return $this->hasMany(SppdApprovalConfigStep::class, 'sppd_approval_config_id')->orderBy('step_order');
    }
}
