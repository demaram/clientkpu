<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AbsenOs extends Model
{
    protected $table = 'absen_os';

    protected $fillable = ['bulan','karyawan_project_id','masuk'];

    public $timestamps = false;

    public function history()
    {
         return $this->belongsTo('App\Models\History','karyawan_project_id');
    }
}
