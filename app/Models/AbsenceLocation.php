<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AbsenceLocation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'id_absence', 'lat', 'lon', 'type'
    ];

    protected $casts = [
        'lat' => 'double',
        'lon' => 'double',
    ];

    public function absence()
    {
        return $this->belongsTo(Absence::class, 'id_absence');
    }
}
