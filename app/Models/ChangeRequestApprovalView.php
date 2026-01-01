<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChangeRequestApprovalView extends Model
{
    protected $table = 'change_request_approval_view';
    

    /**
     * Get the change request that owns the approval view.
     */
    public function changeRequest()
    {
        return $this->belongsTo(ChangeRequest::class, 'change_request_id');
    }

    /**
     * Get the approver user.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
} 