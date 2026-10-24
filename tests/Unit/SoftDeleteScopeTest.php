<?php

namespace Tests\Unit;

use App\Models\AbsenOs;
use App\Models\Cuti;
use App\Models\Gaji;
use App\Models\History;
use App\Models\Karyawan;
use App\Models\LemburApprovalLog;
use App\Models\LemburKaryawan;
use App\Models\LemburLocation;
use App\Models\PkwtContract;
use App\Models\Project;
use App\Models\Spk;
use App\Models\User;
use Tests\TestCase;

/**
 * Data Terhapus (ADR 0003) dibuat di payroll; clientkpu hanya membaca, jadi
 * setiap model tabel kritikal wajib menyembunyikan baris ber-deleted_at.
 */
class SoftDeleteScopeTest extends TestCase
{
    /**
     * @return array<string, array{0: class-string}>
     */
    public static function models(): array
    {
        $classes = [
            User::class, Karyawan::class, History::class, Spk::class, Project::class,
            LemburKaryawan::class, LemburLocation::class, LemburApprovalLog::class,
            PkwtContract::class, Cuti::class, AbsenOs::class, Gaji::class,
        ];

        return array_combine(array_map('class_basename', $classes), array_map(fn ($c) => [$c], $classes));
    }

    /**
     * @dataProvider models
     * @param  class-string  $class
     * @return void
     */
    public function test_query_bawaan_menyembunyikan_data_terhapus(string $class): void
    {
        $sql = (new $class)->newQuery()->toSql();

        $this->assertStringContainsString('deleted_at', $sql, $class . ' belum memakai SoftDeletes.');
        $this->assertStringContainsString('is null', $sql, $class);
    }
}
