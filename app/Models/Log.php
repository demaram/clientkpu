<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Log extends Model
{
    protected $table = 'log_user';

    public $timestamps = false;


    public function user()
    {
         return $this->belongsTo(User::class);
    }
}
