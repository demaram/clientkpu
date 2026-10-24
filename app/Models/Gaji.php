<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Gaji extends Model
{
    use SoftDeletes;

    protected $table = 'gaji';

}
