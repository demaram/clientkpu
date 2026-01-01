<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterRekening extends Model
{
    protected $table = 'master_rekening';

    protected $fillable = ['nama','kode'];

    public $timestamps = false;

    public function company()
    {
         return $this->hasMany('App\Models\Company');
    }
}
