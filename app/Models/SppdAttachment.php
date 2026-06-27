<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class SppdAttachment extends Model
{
    protected $table = 'sppd_attachments';

    protected $guarded = [];

    protected $appends = ['url'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function sppd()
    {
        return $this->belongsTo(Sppd::class, 'sppd_id');
    }

    /**
     * Resolve URL from custom_public disk.
     *
     * @return string|null
     */
    public function getUrlAttribute(): ?string
    {
        if (!$this->file_path) {
            return null;
        }

        if (Storage::disk('custom_public')->exists($this->file_path)) {
            return Storage::disk('custom_public')->url($this->file_path);
        }

        return null;
    }
}
