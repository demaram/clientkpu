<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class History extends Model
{
    protected $table = 'karyawan_project';

    public $timestamps = false;

    protected $fillable = [
        'karyawan_id',
        'project_id',
        'lokasi',
        'jabatan',
        'status',
        'start_exeption',
        'end_exeption',
        'detail',
        'resign_reason',
        'resign_document',
        'resign_date'
    ];

    protected $dates = [
        'start_exeption',
        'end_exeption',
        'resign_date'
    ];

    public function karyawan()
    {
         return $this->belongsTo('App\Models\Karyawan');
    }
    public function project()
    {
         return $this->belongsTo('App\Models\Project');
    }
    public function resign()
    {
         return $this->belongsTo('App\Models\MasterResign');
    }

    public function absenos()
    {
         return $this->hasMany('App\Models\AbsenOs','karyawan_project_id');
    }
}
