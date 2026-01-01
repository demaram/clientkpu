<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\LemburKaryawan;

class LemburLocation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'id_absence', 'lat', 'lon', 'type'
    ];

    protected $casts = [
        'lat' => 'double',
        'lon' => 'double',
    ];

    public function lembur()
    {
        return $this->belongsTo(LemburKaryawan::class, 'id_lembur');
    }
}
