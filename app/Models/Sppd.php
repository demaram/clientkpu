<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sppd extends Model
{
    protected $table = 'sppds';

    protected $guarded = [];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function karyawan()
    {
        return $this->belongsTo(User::class, 'karyawan_id');
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
        return $this->hasMany(SppdApprovalLog::class, 'sppd_id');
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
