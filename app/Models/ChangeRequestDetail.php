<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChangeRequestDetail extends Model
{
    protected $table = 'change_request_details';

    public $timestamps = false;
    
    protected $fillable = [
        'change_request_id',
        'field_name',
        'old_value',
        'new_value'
    ];

    /**
     * Get the change request that owns the detail.
     */
    public function changeRequest()
    {
        return $this->belongsTo(ChangeRequest::class, 'change_request_id');
    }
}

