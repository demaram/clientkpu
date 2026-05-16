<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LemburApprovalConfigStep extends Model
{
    protected $table = 'lembur_approval_config_steps';

    protected $fillable = [
        'lembur_approval_config_id',
        'step_order',
        'step_name',
        'approver_user_id',
        'jabatan',
        'jabatan_level',
        'can_override_client',
    ];

    protected $casts = [
        'can_override_client' => 'boolean',
    ];

    public function config()
    {
        return $this->belongsTo(LemburApprovalConfig::class, 'lembur_approval_config_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_user_id');
    }
}
