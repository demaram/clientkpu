<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Sppd extends Model
{
    use SoftDeletes;

    protected $table = 'sppd';

    protected $guarded = [];

    /**
     * The karyawan who filed this SPPD. The `sppd` table links them through `user_id`.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function karyawan()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * The karyawan's project placement this SPPD was filed under (holds their jabatan).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function karyawanProject()
    {
        return $this->belongsTo(History::class, 'karyawan_project_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function approvalConfig()
    {
        return $this->belongsTo(SppdApprovalConfig::class, 'approval_config_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function costs()
    {
        return $this->hasMany(SppdCost::class, 'sppd_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function attachments()
    {
        return $this->hasMany(SppdAttachment::class, 'sppd_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function approvalLogs()
    {
        return $this->hasMany(SppdApprovalLog::class, 'sppd_id')->orderBy('acted_at')->orderBy('id');
    }

    /**
     * Scope to the SPPD of karyawan currently assigned to a Konfigurasi Lembur whose Perekap Lembur is this user.
     *
     * A config limited to a project only covers SPPD filed under that project. Karyawan who are not assigned
     * to any config, or whose assignment ended, are not covered.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $userId  The Perekap Lembur's user id
     */
    public function scopeCoveredByRecapUser($query, int $userId)
    {
        return $query->whereExists(function ($sub) use ($userId) {
            $sub->selectRaw('1')
                ->from('lembur_approval_config_assignments as lca')
                ->join('lembur_approval_configs as lac', 'lac.id', '=', 'lca.lembur_approval_config_id')
                ->whereColumn('lca.karyawan_id', 'sppd.user_id')
                ->whereColumn('lca.client_id', 'sppd.client_id')
                ->where('lca.is_active', true)
                ->where('lac.recap_user_id', $userId)
                ->where(function ($project) {
                    $project->whereNull('lac.project_id')
                        ->orWhereColumn('lac.project_id', 'sppd.project_id');
                });
        });
    }

    /**
     * Scope to a specific client.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $clientId
     */
    public function scopeForClient($query, int $clientId)
    {
        return $query->where('client_id', $clientId);
    }
}
