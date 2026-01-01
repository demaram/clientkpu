<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\ChangeRequestModel as ChangeRequest;

class ChangeRequestLog extends Model
{
    protected $table = 'change_request_logs';
    protected $fillable = [
        'change_request_id',
        'status',
        'notes',
        'created_at',
        'created_by'
    ];
    public $timestamps = false;

    public function changeRequest()
    {
        return $this->belongsTo(ChangeRequest::class, 'change_request_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

