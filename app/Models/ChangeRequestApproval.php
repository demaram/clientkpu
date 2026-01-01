<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChangeRequestApproval extends Model
{
    protected $table = 'change_request_approvals';
    
    protected $fillable = [
        'change_request_id',
        'change_request_step_id',
        'status',
        'approver_id',
        'notes'
    ];

    protected $dates = [
        'approved_at',
        'responded_at'
    ];

    public $timestamps = false;

    /**
     * Get the change request that owns the approval.
     */
    public function changeRequest()
    {
        return $this->belongsTo(ChangeRequest::class, 'change_request_id');
    }

    /**
     * Get the step that owns the approval.
     */
    public function step()
    {
        return $this->belongsTo(ChangeRequestStep::class, 'change_request_step_id');
    }

    /**
     * Get the approver user.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
} 