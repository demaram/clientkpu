<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SppdCost extends Model
{
    protected $table = 'sppd_costs';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'diterima_pegawai' => 'boolean',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function sppd()
    {
        return $this->belongsTo(Sppd::class, 'sppd_id');
    }
}
