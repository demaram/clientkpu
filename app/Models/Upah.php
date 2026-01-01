<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Upah extends Model
{
    protected $table = 'upah';

    protected $fillable = [];

    public $timestamps = false;

    public function cost()
    {
         return $this->hasMany('App\Models\Cost');
    }
}
