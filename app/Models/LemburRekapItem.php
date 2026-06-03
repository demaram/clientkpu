<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LemburRekapItem extends Model
{
    protected $table = 'lembur_rekap_items';

    protected $fillable = [
        'lembur_rekap_id',
        'lembur_id',
        'overtime_pay',
        'counted_hours',
    ];

    protected $casts = [
        'overtime_pay'  => 'decimal:2',
        'counted_hours' => 'decimal:2',
    ];

    public function rekap()
    {
        return $this->belongsTo(LemburRekap::class, 'lembur_rekap_id');
    }

    public function lembur()
    {
        return $this->belongsTo(LemburKaryawan::class, 'lembur_id');
    }
}
