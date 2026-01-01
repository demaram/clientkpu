<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $table = 'locations';

    protected $fillable = ['lat','lon','radius','name','description','address'];

    public $timestamps = false;

    public function karyawan()
    {
         return $this->hasMany('App\Models\Karyawan','id_location');
    }
}
