<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Spk extends Model
{
    protected $table = 'spk';

    protected $fillable = [];

    public function pic()
    {
         return $this->belongsTo('App\Models\Pic');
    }
    public function client()
    {
         return $this->belongsTo('App\Models\Client');
    }
    public function project()
    {
         return $this->hasMany('App\Models\Project');
    }

    public function parent()
    {
         return $this->belongsTo('App\Models\Spk', 'header_id');
    }
}
