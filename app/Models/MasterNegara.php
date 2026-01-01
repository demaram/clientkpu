<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterNegara extends Model
{
    protected $table = 'master_negara';

    protected $fillable = ['nama','kode'];

    public $timestamps = false;

    public function karyawan()
    {
         return $this->hasMany('App\Models\Karyawan');
    }
}
