<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PkwtFormat extends Model
{
    use HasFactory;

    protected $table = 'pkwt_formats';

    protected $fillable = [
        'nama',
        'template_file',
        'preview_pdf_file',
        'description',
        'is_active',
        'signature_x',
        'signature_y',
        'signature_width',
        'signature_height',
        'signature_page',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the contracts using this format
     */
    public function contracts()
    {
        return $this->hasMany(PkwtContract::class, 'pkwt_format_id');
    }

    /**
     * Get active formats only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the user who created this format
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this format
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get template file path
     */
    public function getTemplatePathAttribute()
    {
        return storage_path('app/public/pkwt_templates/' . $this->template_file);
    }

    /**
     * Get template file URL
     */
    public function getTemplateUrlAttribute()
    {
        return asset('storage/pkwt_templates/' . $this->template_file);
    }

    /**
     * Get the contracts using this format
     */
    public function pkwtContracts()
    {
        return $this->hasMany(PkwtContract::class, 'pkwt_format_id');
    }

    /**
     * Get the contracts using this format
     */
    public static function getAvailableVariables()
    {
        return [
            // Data Karyawan
            'nama_lengkap' => [
                'label' => 'Nama Lengkap',
                'description' => 'Nama lengkap karyawan sesuai KTP',
                'required' => true
            ],
            'nik' => [
                'label' => 'NIK',
                'description' => 'Nomor Induk Kependudukan',
                'required' => true
            ],
            'usia' => [
                'label' => 'Usia',
                'description' => 'Usia karyawan dalam tahun',
                'required' => true
            ],
            'tempat_lahir' => [
                'label' => 'Tempat Lahir',
                'description' => 'Tempat lahir karyawan',
                'required' => true
            ],
            'tanggal_lahir' => [
                'label' => 'Tanggal Lahir',
                'description' => 'Tanggal lahir karyawan (format: DD/MM/YYYY)',
                'required' => true
            ],
            'jenis_kelamin' => [
                'label' => 'Jenis Kelamin',
                'description' => 'Jenis kelamin karyawan (Laki-laki/Perempuan)',
                'options' => ['Laki-laki', 'Perempuan'],
                'required' => true
            ],

            'no_ktp' => [
                'label' => 'Nomor KTP',
                'description' => 'Nomor KTP karyawan',
                'required' => true
            ],
            'no_kk' => [
                'label' => 'Nomor KK',
                'description' => 'Nomor Kartu Keluarga',
                'required' => true
            ],
            'alamat_ktp' => [
                'label' => 'Alamat KTP',
                'description' => 'Alamat sesuai KTP',
                'required' => true
            ],
            'alamat_domisili' => [
                'label' => 'Alamat Domisili',
                'description' => 'Alamat tempat tinggal saat ini',
                'required' => false
            ],

            'no_telp' => [
                'label' => 'Nomor Telepon',
                'description' => 'Nomor telepon/handphone',
                'required' => true
            ],
            'pendidikan_terakhir' => [
                'label' => 'Pendidikan Terakhir',
                'description' => 'Tingkat pendidikan terakhir',
                'options' => ['SD', 'SMP', 'SMA', 'D3', 'S1', 'S2', 'S3'],
                'required' => true
            ],
            'npwp' => [
                'label' => 'NPWP',
                'description' => 'Nomor Pokok Wajib Pajak',
                'required' => false
            ],
            'bpjs_kesehatan' => [
                'label' => 'BPJS Kesehatan',
                'description' => 'Nomor BPJS Kesehatan',
                'required' => false
            ],
            'bpjs_ketenagakerjaan' => [
                'label' => 'BPJS Ketenagakerjaan',
                'description' => 'Nomor BPJS Ketenagakerjaan',
                'required' => false
            ],
            'bpjs_pensiun' => [
                'label' => 'BPJS Pensiun',
                'description' => 'Nomor BPJS Pensiun',
                'required' => false
            ],


            // Data Pekerjaan
            'jabatan' => [
                'label' => 'Jabatan',
                'description' => 'Jabatan/posisi karyawan',
                'required' => true
            ],
            // Data Project/Client
            'nama_project' => [
                'label' => 'Nama Project',
                'description' => 'Nama project tempat ditempatkan',
                'required' => true
            ],
            'vendor_project' => [
                'label' => 'Vendor Project',
                'description' => 'Nama vendor pemberi project',
                'required' => true
            ],
            'nomor_spk' => [
                'label' => 'Nomor SPK',
                'description' => 'Nomor Surat Perintah Kerja',
                'required' => true
            ],
            'nama_client' => [
                'label' => 'Nama Client',
                'description' => 'Nama client pemberi project',
                'required' => true
            ],
            'alamat_client' => [
                'label' => 'Alamat Client',
                'description' => 'Alamat client',
                'required' => false
            ],
            'lokasi_project' => [
                'label' => 'Lokasi Project',
                'description' => 'Lokasi project tempat bekerja',
                'required' => true
            ],

            // Data PKWT
            'nomor_pkwt' => [
                'label' => 'Nomor PKWT',
                'description' => 'Nomor Perjanjian Kerja Waktu Tertentu',
                'required' => true
            ],
            'tanggal_spk' => [
                'label' => 'Tanggal SPK',
                'description' => 'Tanggal SPK diterbitkan',
                'required' => true
            ],
            'tanggal_pkwt' => [
                'label' => 'Tanggal PKWT',
                'description' => 'Tanggal pembuatan PKWT',
                'required' => true
            ],
            'tanggal_mulai_pkwt' => [
                'label' => 'Tanggal Mulai PKWT',
                'description' => 'Tanggal mulai berlakunya PKWT',
                'required' => true
            ],
            'tanggal_selesai_pkwt' => [
                'label' => 'Tanggal Selesai PKWT',
                'description' => 'Tanggal berakhirnya PKWT',
                'required' => true
            ],
            'tanggal_mulai_proyek' => [
                'label' => 'Tanggal Mulai Proyek',
                'description' => 'Tanggal mulai proyek berjalan',
                'required' => false
            ],
            'tanggal_selesai_proyek' => [
                'label' => 'Tanggal Selesai Proyek',
                'description' => 'Tanggal berakhirnya proyek',
                'required' => false
            ],
            'tanggal_pkwt_text' => [
                'label' => 'Tanggal PKWT (terbilang)',
                'description' => 'Contoh: Senin tanggal 19, bulan Mei, Tahun 2025',
                'required' => false
            ],

            // Data Upah
            'list_upah' => [
                'label' => 'List Upah',
                'description' => 'Daftar komponen upah karyawan <br>
                    Untuk menampilkan upah per baris, sesuaikan dengan masterdata upah
                    dengan format lowercase dan underscore (misal: <code>${gaji_pokok}</code>, <code>${tunjangan_transport}</code>, <code>${tunjangan_makan}</code>)
                ',
                'required' => true
            ]
        ];
    }

    /**
     * Get the contracts using this format
     */
    public static function getVariablePlaceholders()
    {
        $variables = self::getAvailableVariables();
        $placeholders = [];

        foreach ($variables as $key => $variable) {
            $placeholders[$key] = '${' . $key . '}';
        }

        return $placeholders;
    }

    /**
     * Get the contracts using this format
     */
    public function replaceVariables($data)
    {
        $content = $this->getTemplateContent();
        $variables = self::getAvailableVariables();

        foreach ($variables as $key => $variable) {
            $placeholder = '{{' . $key . '}}';
            $value = $data[$key] ?? '';

            // Format nilai sesuai tipe
            switch ($variable['type']) {
                case 'date':
                    if ($value) {
                        $value = date('d/m/Y', strtotime($value));
                    }
                    break;
                case 'number':
                    if ($value) {
                        $value = number_format($value, 0, ',', '.');
                    }
                    break;
            }

            $content = str_replace($placeholder, $value, $content);
        }

        return $content;
    }

    /**
     * Get the contracts using this format
     */
    public function getTemplateContent()
    {
        if ($this->template_file && file_exists(storage_path('app/templates/' . $this->template_file))) {
            return file_get_contents(storage_path('app/templates/' . $this->template_file));
        }

        return '';
    }



    /**
     * Get preview PDF URL
     */
    public function getPreviewPdfUrlAttribute()
    {
        if ($this->preview_pdf_file) {
            return asset('storage/pkwtformat/' . $this->preview_pdf_file);
        }
        return null;
    }

    /**
     * Get signature coordinates as array
     */
    public function getSignatureCoordinatesAttribute()
    {
        return [
            'x' => $this->signature_x,
            'y' => $this->signature_y,
            'width' => $this->signature_width,
            'height' => $this->signature_height,
            'page' => $this->signature_page
        ];
    }
}
