<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announce extends Model
{
    protected $table = 'announces';
    protected $fillable = ['title', 'subtitle', 'status'];
}
