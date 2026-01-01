<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PinjamanPersonal extends Model
{
    protected $table = 'pinjaman_personal';

    public function karyawan()
    {
         return $this->belongsTo('App\Models\Karyawan');
    }

    public function pinjaman()
    {
         return $this->belongsTo('App\Models\MasterPinjaman','master_pinjaman_id');
    }
}
