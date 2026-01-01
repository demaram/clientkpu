<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChangeRequestFlow extends Model
{
    protected $table = 'change_request_flows';
    
    protected $fillable = [
        'name',
        'tipe',
        'created_by_role'
    ];

    protected $dates = [
        'created_at',
        'updated_at'
    ];

    /**
     * Get the steps for the change request flow.
     */
    public function steps()
    {
        return $this->hasMany(ChangeRequestStep::class, 'change_request_flow_id');
    }
} 