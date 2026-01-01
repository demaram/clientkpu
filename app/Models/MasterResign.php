<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterResign extends Model
{
    protected $table = 'master_resign';

    protected $fillable = ['nama'];

    public $timestamps = false;

    public function history()
    {
         return $this->hasMany('App\Models\History');
    }

}
