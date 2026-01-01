<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/** @package App\Models */
class PkwtContract extends Model
{
    use HasFactory;

    protected $table = 'pkwt_contracts';

    protected $fillable = [
        'pkwt_format_id',
        'history_id',
        'spk_id',
        'karyawan_id',
        'project_id',
        'tanggal_pkwt',
        'tanggal_mulai',
        'tanggal_selesai',
        'gaji_pokok',
        'periode_gaji',
        'tunjangan',
        'catatan',
        'pdf_file',
        'privy_reference_number',
        'privy_document_token',
        'privy_status',
        'privy_signed_document',
        'privy_unsigned_document',
        'privy_recipients',
        'privy_owner',
        'privy_block_reason',
        'privy_reject_reasons',
        'privy_error_message',
        'privy_channel_id',
        'privy_info',
        'privy_message',
        'privy_signing_url',
        'privy_uploaded_at',
        'privy_completed_at',
        'status',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'tanggal_pkwt' => 'date',
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'gaji_pokok' => 'decimal:2',
        'tunjangan' => 'decimal:2',
        'privy_uploaded_at' => 'datetime',
        'privy_completed_at' => 'datetime',
    ];

    /**
     * @var \Illuminate\Filesystem\FilesystemAdapter
     */
    private $disk;

    public function __construct()
    {
        $this->disk = Storage::disk('custom_public');
    }

    /**
     * Get the PKWT format
     */
    public function pkwtFormat()
    {
        return $this->belongsTo(PkwtFormat::class);
    }

    /**
     * Get the history record
     */
    public function history()
    {
        return $this->belongsTo(History::class);
    }

    /**
     * Get the SPK
     */
    public function spk()
    {
        return $this->belongsTo(Spk::class);
    }

    /**
     * Get the karyawan
     */
    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class);
    }

    /**
     * Get the project
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the user who created this contract
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this contract
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get PDF file path
     */
    public function getPdfPathAttribute()
    {
        return storage_path('app/pkwt_contracts/' . $this->pdf_file);
    }

    /**
     * Get PDF file URL
     */
    public function getPdfUrlAttribute()
    {
        return asset('storage/pkwt_contracts/' . $this->pdf_file);
    }

    /**
     * Scope for active contracts
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for expired contracts
     */
    public function scopeExpired($query)
    {
        return $query->where('tanggal_selesai', '<', now());
    }

    /**
     * Scope for active contracts
     */
    public function scopeValid($query)
    {
        return $query->where('tanggal_selesai', '>=', now());
    }

    /**
     * Check if contract has been uploaded to Privy
     */
    public function hasPrivyUpload()
    {
        return !empty($this->privy_reference_number) && !empty($this->privy_document_token);
    }

    /**
     * Check if contract is signed via Privy
     */
    public function isPrivySigned()
    {
        return $this->privy_status === 'completed';
    }

    /**
     * Check if contract can be uploaded to Privy
     */
    public function canUploadToPrivy()
    {
        return !empty($this->pdf_file) && !$this->hasPrivyUpload() && file_exists($this->disk->path('pkwt_contracts/' . $this->pdf_file));
    }

    /**
     * Check if contract can be signed to Privy
     */
    public function canSignedToPrivy()
    {
        return $this->hasPrivyUpload() && in_array($this->privy_status, ['pending', 'uploaded']);
    }

    /**
     * Get Privy status label
     */
    public function getPrivyStatusLabelAttribute()
    {
        $labels = [
            'pending' => 'Menunggu',
            'uploaded' => 'Telah Diupload',
            'processing' => 'Sedang Diproses',
            'completed' => 'Selesai Ditandatangani',
            'rejected' => 'Ditolak',
            'blocked' => 'Diblokir',
            'link_expired' => 'Link Kadaluarsa',
            'revoked' => 'Dibatalkan',
            'process_emeterai'  => 'Proses e-Meterai'
        ];

        return $labels[$this->privy_status] ?? 'Tidak Diketahui';
    }

    /**
     * Get Privy status badge class
     */
    public function getPrivyStatusBadgeAttribute()
    {
        $badges = [
            'pending' => 'label-warning',
            'uploaded' => 'label-info',
            'processing' => 'label-primary',
            'completed' => 'label-success',
            'rejected' => 'label-danger',
            'blocked' => 'label-danger',
            'link_expired' => 'label-warning',
            'revoked' => 'label-default'
        ];

        return $badges[$this->privy_status] ?? 'label-default';
    }
}
