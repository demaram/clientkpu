<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $table = 'company';

    protected $fillable = [];

    public $timestamps = false;

    public function rekening()
    {
        return $this->belongsTo('App\Models\MasterRekening','rekening_id');
    }
}
