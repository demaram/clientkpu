<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Client extends Model
{
    protected $table = 'client';

    protected $fillable = [];

    public $timestamps = false;

    public function spk($value='')
    {
        return $this->hasMany('App\Models\Spk');
    }

    public function users()
    {
        return $this->hasMany(User::class,'id_client');
    }
}
