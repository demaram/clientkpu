<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterTambahan extends Model
{
    protected $table = 'master_tambahan';

    protected $fillable = ['nama','kode','detail'];

    public $timestamps = false;

    public function tambahan()
    {
         return $this->hasMany('App\Models\Potongan');
    }
}
