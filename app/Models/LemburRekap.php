<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LemburRekap extends Model
{
    protected $table = 'lembur_rekap';

    protected $fillable = [
        'client_id',
        'recap_user_id',
        'period_start',
        'period_end',
        'total_lembur',
        'total_pay',
        'status',
        'notes',
        'actioned_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end'   => 'date',
        'actioned_at'  => 'datetime',
        'total_pay'    => 'decimal:2',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function recapUser()
    {
        return $this->belongsTo(User::class, 'recap_user_id');
    }

    public function items()
    {
        return $this->hasMany(LemburRekapItem::class, 'lembur_rekap_id');
    }
}
