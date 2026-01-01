<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $table = 'project';

    protected $fillable = [];

    public function spk()
    {
         return $this->belongsTo('App\Models\Spk');
    }
    public function pekerjaan()
    {
         return $this->belongsTo('App\Models\MasterPekerjaan','master_pekerjaan_id');
    }

    public function karyawan()
    {
         return $this->hasMany('App\Models\Karyawan')->orderBy('first_name');;
    }

    public function projectcost()
    {
         return $this->hasMany('App\Models\Cost');
    }

    public function history()
    {
         return $this->hasMany('App\Models\History','project_id');
    }

    public function masterLembur()
    {
         return $this->hasOne('App\Models\MasterLembur', 'project_id');
    }
}
