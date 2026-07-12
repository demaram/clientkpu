<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Assignment karyawan ke sebuah LemburApprovalConfig. Dikelola dari sisi
 * payroll (menu Setup Karyawan); clientkpu hanya membaca tabel ini untuk
 * menentukan visibility lembur/piket yang belum di-checkout (approval_config_id
 * masih NULL karena baru di-resolve saat checkout, bukan saat check-in).
 *
 * @see development/features/lembur/lembur_existing_flow.md (Section 10)
 */
class LemburApprovalConfigAssignment extends Model
{
    protected $table = 'lembur_approval_config_assignments';

    protected $fillable = [
        'lembur_approval_config_id',
        'karyawan_id',
        'client_id',
        'is_active',
        'assigned_by',
        'assigned_at',
        'unassigned_at',
    ];

    protected $casts = [
        'is_active'     => 'boolean',
        'assigned_at'   => 'datetime',
        'unassigned_at' => 'datetime',
    ];

    public function config()
    {
        return $this->belongsTo(LemburApprovalConfig::class, 'lembur_approval_config_id');
    }

    public function karyawan()
    {
        return $this->belongsTo(User::class, 'karyawan_id');
    }
}
