<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserClientAccess extends Model
{
    protected $table = 'user_client_access';

    protected $fillable = [
        'user_id',
        'client_id',
        'granted_by',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function grantedBy()
    {
        return $this->belongsTo(User::class, 'granted_by');
    }
}
