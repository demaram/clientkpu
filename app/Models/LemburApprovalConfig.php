<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LemburApprovalConfig extends Model
{
    protected $table = 'lembur_approval_configs';

    protected $fillable = [
        'client_id',
        'project_id',
        'recap_user_id',
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function steps()
    {
        return $this->hasMany(LemburApprovalConfigStep::class, 'lembur_approval_config_id')
                    ->orderBy('step_order');
    }

    public function recapUser()
    {
        return $this->belongsTo(User::class, 'recap_user_id');
    }
}
