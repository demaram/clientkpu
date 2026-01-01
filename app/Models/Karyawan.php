<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
class Karyawan extends Model
{
    protected $table = 'users';

    protected $fillable = ['status'];

    public function roles()
    {
         return $this->belongsToMany('App\Models\Role','role_user','role_id','user_id');
    }
    public function project()
    {
         return $this->belongsTo('App\Models\Project');
    }
    public function absence()
    {
        return $this->hasMany('App\Models\Absence','id_user');
    }
    public function history()
    {
         return $this->hasMany('App\Models\History');
    }
    public function cuti()
    {
        return $this->hasMany('App\Models\Cuti','karyawan_id');
    }
    public function ptkp()
    {
        return $this->belongsTo('App\Models\MasterPtkp','ptkp_id');
    }
    public function pinjamanpersonal()
    {
         return $this->belongsTo('App\Models\PinjamanPersonal');
    }
    public function tambahanpersonal()
    {
         return $this->belongsTo('App\Models\TambahanPersonal');
    }

    public function potongan()
    {
         return $this->belongsToMany('App\Models\Potongan','pinjaman_karyawan','karyawan_id','potongan_id');
    }

    public function tambahan()
    {
         return $this->belongsToMany('App\Models\Tambahan','tambahan_karyawan','karyawan_id','tambahan_id');
    }

    public function agama()
    {
         return $this->belongsTo('App\Models\MasterAgama','id_agama');
    }
    public function location()
    {
         return $this->belongsTo('App\Models\Location','id_location');
    }
    public function negara()
    {
         return $this->belongsTo('App\Models\MasterNegara','id_negara');
    }

    public function rekening()
    {
         return $this->belongsTo('App\Models\MasterRekening','id_rekening');
    }

    public function resign()
    {
         return $this->belongsTo('App\Models\MasterResign','resign_id');
    }

    public static function getKaryawan()
    {
         $role = Role::with('karyawan')->where('name','karyawan')->first();
         return $role->karyawan;
    }
    public static function getAllKaryawanQuery()
    {
         return Self::queryKaryawan(0);
    }

    public static function getDataKaryawanOs()
    {
         return Self::queryKaryawan(2);
    }
    public static function getDataKaryawanOrganik()
    {
         return Self::queryKaryawan(1);
    }

    public function shifts()
    {
          return $this->belongsToMany('App\Models\Shift','shift_employees','id_user','id_shift');
    }

    public function multipleLocation()
    {
          return $this->belongsToMany('App\Models\Location','karyawan_location','karyawan_id','location_id');
    }

    public function changeRequest()
    {
        return $this->hasMany('App\Models\ChangeRequestModel','group_id')->where('group', 'karyawan');
    }


    public static function queryKaryawan($tipe)    {
          $query = User::with(['role' => function($q) {
                    $q->where('name', 'karyawan');
               }])
               ->with('changeRequest')
               ->leftJoin('karyawan_project', 'users.id', '=', 'karyawan_project.karyawan_id')
               ->leftJoin('project', 'karyawan_project.project_id', '=', 'project.id')
               ->leftJoin('spk', 'project.spk_id', '=', 'spk.id')
               ->leftJoin('client', 'spk.client_id', '=', 'client.id')
               ->select('users.id', 'users.first_name', 'users.last_name', 'users.no_rekening',
                       'users.empid', 'users.is_active', 'users.is_approve', 'users.detail',
                       'users.bpjs_verification')
               ->selectRaw("GROUP_CONCAT(IF(karyawan_project.status = 1,
                          CONCAT('<b>', client.description, '</b> - ', project.nama), NULL)
                          SEPARATOR ', ') as project");

          if ($tipe == 1) {
               $query->where('users.tipe', 1);
          } elseif ($tipe == 2) {
               $query->where('users.tipe', 2);
          }

          $data = $query->groupBy('users.id')
                       ->orderByDesc('users.id')
                       ->get();
         return $data;
    }

}
