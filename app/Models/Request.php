<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChangeRequest extends Model
{
    use HasFactory;

    protected $table = 'requests';

    public $timestamps = true;

    protected $fillable = [
        'tipe',
        'status',
        'changes_json',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'changes_json' => 'array',
    ];
} 