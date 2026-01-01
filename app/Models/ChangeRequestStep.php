<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ChangeRequestApproval;
use App\Models\ChangeRequestFlow;

class ChangeRequestStep extends Model
{
    protected $table = 'change_request_steps';
    
    protected $fillable = [
        'change_request_flow_id',
        'step_order',
        'role',
        'step_name'
    ];

    protected $dates = [
        'created_at'
    ];

    /**
     * Get the flow that owns the step.
     */
    public function flow()
    {
        return $this->belongsTo(ChangeRequestFlow::class, 'change_request_flow_id');
    }

    /**
     * Get the approvals for the step.
     */
    public function approvals()
    {
        return $this->hasMany(ChangeRequestApproval::class, 'change_request_step_id');
    }
} 