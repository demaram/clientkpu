<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Traits\LaravelEntrustUserTrait;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;
    use LaravelEntrustUserTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'photo',
        'company',
        'phone',
        'occupation',
        'last_login',
        'description',
        'pic_id',
        'empid',
        'tipe',
        'status',
        'join_date',
        'first_name',
        'last_name',
        'nik',
        'nkk',
        'npwp',
        'upload_kk',
        'upload_ktp',
        'tanggungan',
        'tanggungan_td',
        'lamaran',
        'status_menikah',
        'jk',
        'tmpt_lahir',
        'tgl_lahir',
        'alamat_ktp',
        'alamat_domisili',
        'rt',
        'rw',
        'kode_pos',
        'ptkp_id',
        'is_active',
        'resign_date',
        'is_approve',
        'nama_akun',
        'no_rekening',
        'kota_bank',
        'alamat_bank',
        'nama_pihak',
        'alamat_pihak',
        'hubungan_pihak',
        'nomor_pihak',
        'nm_ayah',
        'tgl_lahir_ayah',
        'nm_ibu',
        'tgl_lahir_ibu',
        'pasangan',
        'anak1',
        'anak2',
        'anak3',
        'bpjs_kes',
        'bpjs_ket',
        'bpjs_pen',
        'pendidikan',
        'id_rekening',
        'resign_id',
        'id_pendidikan',
        'id_agama',
        'id_negara',
        'id_approver',
        'detail',
        'detail_resign',
        'resign_document',
        'is_card',
        'celana',
        'baju',
        'sepatu',
        'is_tetap',
        'remember_token',
        'created_at',
        'updated_at',
        'id_location',
        'lock_location',
        'lock_device',
        'offline_online',
        'upload_sim',
        'bpjs_verification',
        'privy_id',
        'privy_registered_at',
        'id_client',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function role()
    {
        return $this->belongsToMany('App\Models\Role','role_user')->withPivot('role_id','user_id'); //,'assigned_roles'
    }

    public function areas()
    {
        return $this->belongsToMany('App\Models\MasterArea', 'user_area', 'user_id', 'area_id');
    }

    public function client()
    {
        return $this->belongsTo('App\Models\Client', 'id_client');
    }
}
