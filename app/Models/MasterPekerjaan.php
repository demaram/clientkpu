<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterPekerjaan extends Model
{
    protected $table = 'master_pekerjaan';

    protected $fillable = ['nama','detail','kode'];

    public $timestamps = false;

    public function project()
    {
         return $this->hasMany('App\Models\Project');
    }
}
