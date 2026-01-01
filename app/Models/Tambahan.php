<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tambahan extends Model
{
    protected $table = 'tambahan';

    public function mastertambahan()
    {
         return $this->belongsTo('App\Models\MasterTambahan','master_tambahan_id');
    }

    public function karyawan()
    {
         return $this->belongsToMany('App\Models\Karyawan','tambahan_karyawan','tambahan_id','karyawan_id');
    }

}
