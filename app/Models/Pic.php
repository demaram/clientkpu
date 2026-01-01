<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pic extends Model
{
    protected $table = 'pic';

    protected $fillable = [];

    public $timestamps = false;

    public function spk()
    {
         return $this->hasMany("App\Models\Spk");
    }
    public function user()
    {
         return $this->hasMany("App\User",'pic_id');
    }
}
