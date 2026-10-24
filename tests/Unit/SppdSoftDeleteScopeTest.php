<?php

namespace Tests\Unit;

use App\Models\Sppd;
use App\Models\SppdAttachment;
use App\Models\SppdCost;
use App\Models\SppdApprovalLog;
use Tests\TestCase;

/**
 * Fondasi soft delete SPPD (ADR 0003, dirilis terpisah dari data kritikal lain):
 * model SPPD menyembunyikan baris ber-deleted_at.
 */
class SppdSoftDeleteScopeTest extends TestCase
{
    /**
     * @return array<string, array{0: class-string}>
     */
    public static function models(): array
    {
        $classes = [
            Sppd::class, SppdCost::class, SppdAttachment::class,
            SppdApprovalLog::class,
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
