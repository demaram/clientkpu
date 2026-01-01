<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TambahanPersonal extends Model
{
    protected $table = 'tambahan_personal';

    public function karyawan()
    {
         return $this->belongsTo('App\Models\Karyawan');
    }

    public function tambahan()
    {
         return $this->belongsTo('App\Models\MasterTambahan','master_tambahan_id');
    }
}
