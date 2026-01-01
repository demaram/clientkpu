<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LemburKaryawan extends Model
{
    protected $table = 'lembur_karyawan_project';

    protected $fillable = [
        'user_id',
        'karyawan_project_id',
        'project_id',
        'client_id',
        'spk_id',
        'type',
        'alasan',
        'start',
        'end',
        'start_photo',
        'end_photo',
        'status',
        'overtime_pay',
        'created_at',
        'updated_at'
    ];

    public function history()
    {
         return $this->belongsTo('App\Models\History','karyawan_project_id');
    }

    public function user()
    {
          return $this->belongsTo('App\Models\Karyawan','user_id');
    }

    public function client()
    {
          return $this->belongsTo('App\Models\Client','client_id');
    }

    /**
     * Get lembur locations for this lembur record
     */
    public function locations()
    {
        return $this->hasMany('App\Models\LemburLocation', 'id_lembur');
    }

    /**
     * Get check-in location
     */
    public function checkInLocation()
    {
        return $this->hasOne('App\Models\LemburLocation', 'id_lembur')->where('type', 'in');
    }

    /**
     * Get check-out location
     */
    public function checkOutLocation()
    {
        return $this->hasOne('App\Models\LemburLocation', 'id_lembur')->where('type', 'out');
    }
}
