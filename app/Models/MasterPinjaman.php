<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterPinjaman extends Model
{
    protected $table = 'master_pinjaman';

    protected $fillable = ['nama','kode','detail'];

    public $timestamps = false;

    public function potongan()
    {
         return $this->hasMany('App\Models\Potongan');
    }
    public function personal()
    {
         return $this->hasMany('App\Models\PinjamanPersonal');
    }
}
