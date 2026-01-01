<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterAgama extends Model
{
    protected $table = 'master_agama';

    protected $fillable = ['nama','kode'];

    public $timestamps = false;

    public function karyawan()
    {
         return $this->hasMany('App\Models\Karyawan');
    }
}
