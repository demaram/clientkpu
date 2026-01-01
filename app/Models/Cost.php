<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cost extends Model
{
    protected $table = 'cost';

    protected $fillable = [
        'project_id',
        'upah_id',
        'tambahan',
        'pengurangan',
        'tanggung_pt',
        'tanggung_kw',
    ];

    public $timestamps = false;

    public function upah()
    {
         return $this->belongsTo("App\Models\Upah");
    }

    public function project()
    {
         return $this->belongsTo("App\Models\Project");
    }
}
