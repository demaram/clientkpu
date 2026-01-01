<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cuti extends Model
{
    protected $table = 'cuti_karyawan';

    protected $fillable = ['karyawan_id','start','end','tipe','alasan'];

    public $timestamps = false;

    public function karyawan()
    {
         return $this->belongsTo('App\Models\Karyawan','karyawan_id');
    }

     public function approved()
     {
          return $this->belongsTo('App\User','approved_user_id');
     }
}
