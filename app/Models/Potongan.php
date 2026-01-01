<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Karyawan;

class Potongan extends Model
{
    protected $table = 'pinjaman';

    public function masterpinjaman()
    {
         return $this->belongsTo('App\Models\MasterPinjaman','master_pinjaman_id');
    }

    public function karyawan()
    {
        return $this->belongsToMany(Karyawan::class, 'pinjaman_karyawan', 'potongan_id', 'karyawan_id');
    }
}
