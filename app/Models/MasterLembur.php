<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterLembur extends Model
{
    protected $table = 'master_lembur';

    protected $fillable = [
        'project_id',
        'client_id',
        'nilai_lembur',
        'nilai_piket',
        'max_jam_lembur',
        'max_jam_lembur_harian_kerja',
        'max_jam_lembur_harian_libur',
        'max_jam_lembur_mingguan',
        'max_jam_lembur_bulanan',
        'pembagi_lembur',
        'upah_lembur_perjam',
        'upah_piket_perhari',
        'status',
        'jam_mulai_lembur',
        'max_upah_lembur_perbulan',
        'is_tunj_tetap',
        'is_tunj_tidak_tetap',
        'created_at',
        'updated_at',
        'created_by',
    ];

    public $timestamps = true;

    /**
     * Relasi ke tabel Project
     */
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }

    /**
     * Relasi ke tabel Client
     */
    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id', 'id');
    }

    /**
     * Relasi ke tabel User (pembuat)
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}
