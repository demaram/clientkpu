<?php

namespace Tests\Feature\Services;

use App\Services\DashboardStatsService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Covers DashboardStatsService::totalPayByPekerjaanSeries() — the dashboard
 * line chart that splits the recap layer's paid overtime per jenis pekerjaan.
 *
 * The domain tables live in the shared legacy database and have no migrations
 * in this repo, so each test builds the five tables it needs on the in-memory
 * sqlite connection (see phpunit.xml / .env.testing). That is also why the
 * reconciliation test compares against a plain SUM(total_pay) rather than
 * calling totalPaySeries(), which uses MySQL-only DATE_FORMAT().
 */
class DashboardStatsServicePekerjaanSeriesTest extends TestCase
{
    private const CLIENT_ID = 7;

    private int $nextLemburId = 1;

    private int $nextRekapId = 1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createDomainTables();
        $this->seedPekerjaanAndProjects();
    }

    protected function tearDown(): void
    {
        foreach (['lembur_rekap_items', 'lembur_rekap', 'lembur_karyawan_project', 'project', 'master_pekerjaan'] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    /**
     * The five tables the query joins, reduced to the columns it touches.
     */
    private function createDomainTables(): void
    {
        Schema::create('master_pekerjaan', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->string('nama')->nullable();
        });

        Schema::create('project', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->integer('master_pekerjaan_id')->nullable();
            $table->string('nama')->nullable();
        });

        Schema::create('lembur_karyawan_project', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->integer('project_id')->nullable();
        });

        Schema::create('lembur_rekap', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->integer('client_id');
            $table->string('type');
            $table->string('status');
            $table->date('period_start');
            $table->decimal('total_pay', 15, 2)->default(0);
        });

        Schema::create('lembur_rekap_items', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('lembur_rekap_id');
            $table->integer('lembur_id');
            $table->decimal('overtime_pay', 15, 2)->default(0);
        });
    }

    /**
     * Two pekerjaan, with "Driver" deliberately split across two projects so the
     * collapse-projects-into-one-series behaviour is exercised.
     */
    private function seedPekerjaanAndProjects(): void
    {
        DB::table('master_pekerjaan')->insert([
            ['id' => 1, 'nama' => 'Driver'],
            ['id' => 2, 'nama' => 'Office Boy'],
        ]);

        DB::table('project')->insert([
            ['id' => 10, 'master_pekerjaan_id' => 1, 'nama' => 'Driver Direktur'],
            ['id' => 11, 'master_pekerjaan_id' => 1, 'nama' => 'Driver Operasional'],
            ['id' => 12, 'master_pekerjaan_id' => 2, 'nama' => 'OB Gedung A'],
            ['id' => 13, 'master_pekerjaan_id' => 999, 'nama' => 'Project pekerjaan hilang'],
            ['id' => 14, 'master_pekerjaan_id' => null, 'nama' => 'Project tanpa pekerjaan'],
        ]);
    }

    /**
     * Create an approved rekap for the given month with one item per amount.
     *
     * @param  array<int, array{project_id: int|null, pay: float}>  $items
     */
    private function seedRekap(
        Carbon $month,
        array $items,
        int $clientId = self::CLIENT_ID,
        string $type = 'lembur',
        string $status = 'approved'
    ): void {
        $rekapId = $this->nextRekapId++;

        DB::table('lembur_rekap')->insert([
            'id'           => $rekapId,
            'client_id'    => $clientId,
            'type'         => $type,
            'status'       => $status,
            'period_start' => $month->copy()->startOfMonth()->toDateString(),
            'total_pay'    => array_sum(array_column($items, 'pay')),
        ]);

        foreach ($items as $item) {
            $lemburId = $this->nextLemburId++;

            DB::table('lembur_karyawan_project')->insert([
                'id'         => $lemburId,
                'project_id' => $item['project_id'],
            ]);

            DB::table('lembur_rekap_items')->insert([
                'lembur_rekap_id' => $rekapId,
                'lembur_id'       => $lemburId,
                'overtime_pay'    => $item['pay'],
            ]);
        }
    }

    private function service(): DashboardStatsService
    {
        return app(DashboardStatsService::class);
    }

    /**
     * @return array<string, float[]>
     */
    private function valuesByLabel(array $series): array
    {
        $out = [];

        foreach ($series['datasets'] as $dataset) {
            $out[$dataset['label']] = $dataset['values'];
        }

        return $out;
    }

    public function test_splits_pay_per_pekerjaan_and_merges_projects_sharing_one(): void
    {
        $thisMonth = Carbon::now()->startOfMonth();

        $this->seedRekap($thisMonth, [
            ['project_id' => 10, 'pay' => 1000],   // Driver Direktur
            ['project_id' => 11, 'pay' => 500],    // Driver Operasional
            ['project_id' => 12, 'pay' => 250],    // Office Boy
        ]);

        $series = $this->service()->totalPayByPekerjaanSeries([self::CLIENT_ID]);
        $values = $this->valuesByLabel($series);

        $this->assertSame(['Driver', 'Office Boy'], array_keys($values));
        $this->assertSame(1500.0, end($values['Driver']));
        $this->assertSame(250.0, end($values['Office Boy']));
    }

    public function test_covers_twelve_months_and_fills_empty_months_with_zero(): void
    {
        $thisMonth = Carbon::now()->startOfMonth();

        $this->seedRekap($thisMonth, [['project_id' => 10, 'pay' => 1000]]);
        $this->seedRekap($thisMonth->copy()->subMonths(3), [['project_id' => 10, 'pay' => 400]]);

        $series = $this->service()->totalPayByPekerjaanSeries([self::CLIENT_ID]);
        $driver = $this->valuesByLabel($series)['Driver'];

        $this->assertCount(12, $series['labels']);
        $this->assertCount(12, $driver);
        $this->assertSame(1000.0, $driver[11]);
        $this->assertSame(400.0, $driver[8]);
        $this->assertSame(0.0, $driver[9]);
        $this->assertSame(0.0, $driver[0]);
        $this->assertSame($thisMonth->format('M Y'), $series['labels'][11]);
    }

    public function test_unresolvable_pekerjaan_lands_in_one_unknown_series_pinned_last(): void
    {
        $thisMonth = Carbon::now()->startOfMonth();

        $this->seedRekap($thisMonth, [
            ['project_id' => 10, 'pay' => 100],     // Driver
            ['project_id' => 13, 'pay' => 20],      // master_pekerjaan_id dangling
            ['project_id' => 14, 'pay' => 5],       // project without pekerjaan
            ['project_id' => null, 'pay' => 3],     // lembur without project
            ['project_id' => 555, 'pay' => 2],      // project row deleted
        ]);

        $series = $this->service()->totalPayByPekerjaanSeries([self::CLIENT_ID]);
        $labels = array_column($series['datasets'], 'label');

        $this->assertSame(['Driver', DashboardStatsService::UNKNOWN_PEKERJAAN], $labels);

        $unknown = end($series['datasets']);
        $this->assertTrue($unknown['is_unknown']);
        $this->assertSame(30.0, end($unknown['values']));
    }

    public function test_datasets_are_ordered_by_twelve_month_total_descending(): void
    {
        $thisMonth = Carbon::now()->startOfMonth();

        // Office Boy leads this month, but Driver wins across the window.
        $this->seedRekap($thisMonth, [
            ['project_id' => 10, 'pay' => 100],
            ['project_id' => 12, 'pay' => 900],
        ]);
        $this->seedRekap($thisMonth->copy()->subMonths(2), [
            ['project_id' => 10, 'pay' => 5000],
        ]);

        $series = $this->service()->totalPayByPekerjaanSeries([self::CLIENT_ID]);

        $this->assertSame(['Driver', 'Office Boy'], array_column($series['datasets'], 'label'));
    }

    public function test_series_total_reconciles_with_rekap_header_total_pay(): void
    {
        $thisMonth = Carbon::now()->startOfMonth();

        $this->seedRekap($thisMonth, [
            ['project_id' => 10, 'pay' => 1000],
            ['project_id' => 12, 'pay' => 250],
            ['project_id' => null, 'pay' => 75],
        ]);
        $this->seedRekap($thisMonth->copy()->subMonths(5), [
            ['project_id' => 11, 'pay' => 640],
        ]);

        $series = $this->service()->totalPayByPekerjaanSeries([self::CLIENT_ID]);

        $charted = 0.0;
        foreach ($series['datasets'] as $dataset) {
            $charted += array_sum($dataset['values']);
        }

        $headerTotal = (float) DB::table('lembur_rekap')
            ->where('client_id', self::CLIENT_ID)
            ->where('type', 'lembur')
            ->where('status', 'approved')
            ->sum('total_pay');

        $this->assertSame(1965.0, $headerTotal);
        $this->assertSame($headerTotal, $charted);
    }

    public function test_ignores_other_clients_other_types_and_unapproved_rekap(): void
    {
        $thisMonth = Carbon::now()->startOfMonth();

        $this->seedRekap($thisMonth, [['project_id' => 10, 'pay' => 100]]);
        $this->seedRekap($thisMonth, [['project_id' => 10, 'pay' => 999]], clientId: 99);
        $this->seedRekap($thisMonth, [['project_id' => 10, 'pay' => 888]], type: 'piket');
        $this->seedRekap($thisMonth, [['project_id' => 10, 'pay' => 777]], status: 'pending');

        $series = $this->service()->totalPayByPekerjaanSeries([self::CLIENT_ID]);
        $driver = $this->valuesByLabel($series)['Driver'];

        $this->assertSame(100.0, array_sum($driver));
    }

    public function test_excludes_rekap_outside_the_trailing_twelve_month_window(): void
    {
        $thisMonth = Carbon::now()->startOfMonth();

        $this->seedRekap($thisMonth->copy()->subMonths(11), [['project_id' => 10, 'pay' => 300]]);
        $this->seedRekap($thisMonth->copy()->subMonths(12), [['project_id' => 10, 'pay' => 999]]);

        $series = $this->service()->totalPayByPekerjaanSeries([self::CLIENT_ID]);
        $driver = $this->valuesByLabel($series)['Driver'];

        $this->assertSame(300.0, $driver[0]);
        $this->assertSame(300.0, array_sum($driver));
    }

    public function test_returns_no_datasets_when_there_is_nothing_to_plot(): void
    {
        $series = $this->service()->totalPayByPekerjaanSeries([self::CLIENT_ID]);

        $this->assertSame([], $series['datasets']);
        $this->assertCount(12, $series['labels']);
    }
}
