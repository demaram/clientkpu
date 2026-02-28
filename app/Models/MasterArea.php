<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterArea extends Model
{
    protected $table = 'master_area';

    protected $fillable = ['nama','deskripsi'];

    public $timestamps = false;

}
