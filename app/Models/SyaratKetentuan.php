<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class SyaratKetentuan extends Model
{
    protected $table = 'syarat_ketentuan';

    protected $fillable = ['nama', 'tipe', 'status', 'isi', 'created_by'];

    public $timestamps = true;

    /**
     * Relationship dengan user yang membuat
     */
    public function creator()
    {
        return $this->belongsTo('App\Models\User', 'created_by');
    }

    /**
     * Boot method untuk auto fill created_by
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (Auth::check() && !$model->created_by) {
                $model->created_by = Auth::id();
            }
        });
    }
}
