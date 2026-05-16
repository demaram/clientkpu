<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LemburApprovalLog extends Model
{
    public $timestamps = false;

    protected $table = 'lembur_approval_logs';

    protected $fillable = [
        'lembur_id',
        'step_id',
        'step_order',
        'approver_id',
        'status',
        'notes',
        'acted_at',
        'acted_from',
    ];

    protected $casts = [
        'acted_at' => 'datetime',
    ];

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function step()
    {
        return $this->belongsTo(LemburApprovalConfigStep::class, 'step_id');
    }
}
