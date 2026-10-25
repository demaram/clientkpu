<?php

namespace Tests\Feature\Admin;

use App\Datatables\SppdReportDatatable;
use App\Http\Controllers\Admin\SppdReportController;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * Report SPPD for the Perekap Lembur: which SPPD a recap user sees (karyawan currently assigned to
 * one of their Konfigurasi Lembur), the cost columns and footer totals, and that it stays read-only.
 *
 * The tables belong to the shared legacy database and have no migrations in this repo, so each
 * test builds them, reduced to the columns in play, on the in-memory sqlite connection.
 */
class SppdReportTest extends TestCase
{
    private const CLIENT_ID = 3;
    private const RECAP_ID  = 6273;
    private const OTHER_RECAP_ID = 6274;

    /**
     * Build the domain tables and sign in as the Perekap Lembur with one config (id 15, whole client).
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->dropTables();
        $this->createDomainTables();

        foreach ([self::RECAP_ID => 'Layer', self::OTHER_RECAP_ID => 'Other'] as $id => $name) {
            User::forceCreate(['id' => $id, 'first_name' => $name, 'last_name' => 'Recap', 'id_client' => self::CLIENT_ID]);
        }

        DB::table('lembur_approval_configs')->insert(['id' => 15, 'client_id' => self::CLIENT_ID, 'project_id' => null, 'recap_user_id' => self::RECAP_ID]);

        $this->actingAs(User::find(self::RECAP_ID));
    }

    /**
     * Drop the tables created for the test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->dropTables();

        parent::tearDown();
    }

    /**
     * @return void
     */
    private function dropTables(): void
    {
        foreach ([
            'sppd_approval_logs', 'sppd_attachments', 'sppd_costs', 'sppd_approval_config_steps', 'sppd_approval_configs',
            'sppd', 'karyawan_project', 'lembur_approval_config_assignments', 'lembur_approval_configs',
            'client', 'user_client_access', 'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }

    /**
     * @return void
     */
    private function createDomainTables(): void
    {
        Schema::create('users', function (Blueprint $t) {
            $t->integer('id')->primary();
            $t->string('first_name')->nullable();
            $t->string('last_name')->nullable();
            $t->string('empid')->nullable();
            $t->integer('id_client')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('user_client_access', function (Blueprint $t) {
            $t->increments('id');
            $t->integer('user_id');
            $t->integer('client_id');
        });
        Schema::create('client', function (Blueprint $t) {
            $t->integer('id')->primary();
            $t->string('nama')->nullable();
        });
        Schema::create('karyawan_project', function (Blueprint $t) {
            $t->integer('id')->primary();
            $t->string('jabatan')->nullable();
            $t->softDeletes();
        });
        Schema::create('lembur_approval_configs', function (Blueprint $t) {
            $t->integer('id')->primary();
            $t->integer('client_id');
            $t->integer('project_id')->nullable();
            $t->integer('recap_user_id')->nullable();
        });
        Schema::create('lembur_approval_config_assignments', function (Blueprint $t) {
            $t->increments('id');
            $t->integer('lembur_approval_config_id');
            $t->integer('karyawan_id');
            $t->integer('client_id');
            $t->boolean('is_active')->default(true);
        });
        Schema::create('sppd', function (Blueprint $t) {
            $t->integer('id')->primary();
            $t->integer('user_id');
            $t->integer('karyawan_project_id')->nullable();
            $t->integer('project_id')->nullable();
            $t->integer('client_id');
            $t->integer('approval_config_id')->nullable();
            $t->integer('current_approval_step')->default(1);
            $t->string('kode');
            $t->date('tanggal_berangkat');
            $t->date('tanggal_kembali');
            $t->integer('durasi');
            $t->string('lokasi_penugasan')->nullable();
            $t->text('deskripsi')->nullable();
            $t->string('status')->default('approved');
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('sppd_approval_configs', function (Blueprint $t) {
            $t->integer('id')->primary();
        });
        Schema::create('sppd_approval_config_steps', function (Blueprint $t) {
            $t->integer('id')->primary();
            $t->integer('sppd_approval_config_id');
            $t->integer('step_order');
            $t->string('step_name')->nullable();
            $t->string('actor_type')->nullable();
        });
        Schema::create('sppd_costs', function (Blueprint $t) {
            $t->integer('id')->primary();
            $t->integer('sppd_id');
            $t->string('nama')->nullable();
            $t->decimal('nominal', 15, 2)->default(0);
            $t->integer('hari')->nullable();
            $t->decimal('subtotal', 15, 2)->default(0);
            $t->boolean('diterima_pegawai')->default(false);
            $t->softDeletes();
        });
        Schema::create('sppd_attachments', function (Blueprint $t) {
            $t->integer('id')->primary();
            $t->integer('sppd_id');
            $t->string('jenis')->nullable();
            $t->string('file_path')->nullable();
            $t->string('original_name')->nullable();
            $t->softDeletes();
        });
        Schema::create('sppd_approval_logs', function (Blueprint $t) {
            $t->integer('id')->primary();
            $t->integer('sppd_id');
            $t->integer('step_id')->nullable();
            $t->integer('step_order')->nullable();
            $t->string('status')->nullable();
            $t->text('notes')->nullable();
            $t->dateTime('acted_at')->nullable();
            $t->string('acted_from')->nullable();
            $t->softDeletes();
        });
    }

    /**
     * Seed a karyawan with one SPPD, and optionally an assignment to a config.
     *
     * @param  int  $id  Used for the karyawan id and the SPPD id
     * @param  int|null  $configId  Config to assign the karyawan to; null = unassigned
     * @param  array<string, mixed>  $sppd  Overrides for the sppd row
     * @param  bool  $assignmentActive
     * @return int The SPPD id
     */
    private function seedSppd(int $id, ?int $configId, array $sppd = [], bool $assignmentActive = true): int
    {
        DB::table('users')->insert(['id' => $id, 'first_name' => "Karyawan$id", 'last_name' => null, 'empid' => "E$id", 'id_client' => self::CLIENT_ID]);
        DB::table('client')->insertOrIgnore(['id' => self::CLIENT_ID, 'nama' => 'PT Client']);

        if ($configId !== null) {
            DB::table('lembur_approval_config_assignments')->insert([
                'lembur_approval_config_id' => $configId, 'karyawan_id' => $id, 'client_id' => self::CLIENT_ID, 'is_active' => $assignmentActive,
            ]);
        }

        DB::table('sppd')->insert(array_merge([
            'id' => $id, 'user_id' => $id, 'client_id' => self::CLIENT_ID, 'kode' => "SPPD$id",
            'tanggal_berangkat' => '2026-10-05', 'tanggal_kembali' => '2026-10-07', 'durasi' => 3,
            'lokasi_penugasan' => 'Bandung', 'status' => 'approved', 'created_at' => now(), 'updated_at' => now(),
        ], $sppd));

        return $id;
    }

    /**
     * Add a cost line to an SPPD.
     *
     * @param  int  $sppdId
     * @param  int  $subtotal
     * @param  bool  $diterimaPegawai
     * @return void
     */
    private function addCost(int $sppdId, int $subtotal, bool $diterimaPegawai): void
    {
        DB::table('sppd_costs')->insert([
            'id' => (int) DB::table('sppd_costs')->count() + 1, 'sppd_id' => $sppdId, 'nama' => 'Biaya',
            'nominal' => $subtotal, 'subtotal' => $subtotal, 'diterima_pegawai' => $diterimaPegawai,
        ]);
    }

    /**
     * Render the report the way the DataTable ajax call does.
     *
     * @param  array<string, mixed>  $filters  Extra request parameters (status_filter, nama_filter, ...)
     * @return array<string, mixed> The decoded response: data rows plus totals
     */
    private function report(array $filters = []): array
    {
        $request = Request::create('/admin/report-sppd', 'GET', array_merge(['draw' => 1, 'start' => 0, 'length' => 50], $filters));
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        $this->app->instance('request', $request);

        return app(SppdReportDatatable::class)->render($request)->getData(true);
    }

    /**
     * The kodes in the report, in the order shown.
     *
     * @param  array<string, mixed>  $filters
     * @return string[]
     */
    private function kodes(array $filters = []): array
    {
        return array_map(fn ($row) => strip_tags($row['kode']), $this->report($filters)['data']);
    }

    /**
     * Karyawan currently assigned to the recap user's config show up; unassigned ones do not.
     *
     * @return void
     */
    public function test_shows_sppd_of_karyawan_assigned_to_the_recap_users_config_only(): void
    {
        $this->seedSppd(8, 15);
        $this->seedSppd(6, null);

        $this->assertSame(['SPPD8'], $this->kodes());
    }

    /**
     * A karyawan whose assignment ended is no longer covered: their SPPD leaves the report (agreed trade-off).
     *
     * @return void
     */
    public function test_inactive_assignment_is_not_covered(): void
    {
        $this->seedSppd(8, 15, [], false);

        $this->assertSame([], $this->kodes());
    }

    /**
     * A karyawan assigned to someone else's config is not covered.
     *
     * @return void
     */
    public function test_assignment_to_another_recap_users_config_is_not_covered(): void
    {
        DB::table('lembur_approval_configs')->insert(['id' => 16, 'client_id' => self::CLIENT_ID, 'project_id' => null, 'recap_user_id' => self::OTHER_RECAP_ID]);
        $this->seedSppd(8, 16);

        $this->assertSame([], $this->kodes());
    }

    /**
     * A config limited to one project only covers SPPD filed under that project.
     *
     * @return void
     */
    public function test_project_limited_config_covers_only_that_projects_sppd(): void
    {
        DB::table('lembur_approval_configs')->where('id', 15)->update(['project_id' => 20]);
        $this->seedSppd(8, 15, ['project_id' => 20]);
        $this->seedSppd(9, 15, ['project_id' => 21]);

        $this->assertSame(['SPPD8'], $this->kodes());
    }

    /**
     * Every status is shown; the status filter narrows it.
     *
     * @return void
     */
    public function test_status_filter_narrows_the_report(): void
    {
        $this->seedSppd(8, 15, ['status' => 'approved']);
        $this->seedSppd(9, 15, ['status' => 'waiting_approval']);
        $this->seedSppd(10, 15, ['status' => 'rejected']);

        $this->assertEqualsCanonicalizing(['SPPD8', 'SPPD9', 'SPPD10'], $this->kodes());
        $this->assertSame(['SPPD9'], $this->kodes(['status_filter' => 'waiting_approval']));
    }

    /**
     * Per row: Total Biaya, Total Diterima Pegawai and Total Diterima Perusahaan (the difference).
     *
     * @return void
     */
    public function test_row_shows_the_three_cost_totals(): void
    {
        $id = $this->seedSppd(8, 15);
        $this->addCost($id, 700000, false);
        $this->addCost($id, 400000, true);

        $row = $this->report()['data'][0];

        $this->assertSame('Rp 1.100.000', $row['total_biaya']);
        $this->assertSame('Rp 400.000', $row['total_diterima_pegawai']);
        $this->assertSame('Rp 700.000', $row['total_diterima_perusahaan']);
    }

    /**
     * The footer covers every row matching the filters, not only the current page.
     *
     * @return void
     */
    public function test_footer_totals_cover_all_filtered_rows_not_just_the_page(): void
    {
        foreach ([8, 9, 10] as $id) {
            $this->seedSppd($id, 15);
            $this->addCost($id, 100000, false);
            $this->addCost($id, 50000, true);
        }
        $this->seedSppd(11, 15, ['status' => 'rejected']);
        $this->addCost(11, 999999, false);

        $report = $this->report(['length' => 1, 'status_filter' => 'approved']);

        $this->assertCount(1, $report['data']);
        $this->assertSame('Rp 450.000', $report['totals']['total_biaya']);
        $this->assertSame('Rp 150.000', $report['totals']['total_diterima_pegawai']);
        $this->assertSame('Rp 300.000', $report['totals']['total_diterima_perusahaan']);
    }

    /**
     * Deleted cost lines (Data Terhapus) do not count.
     *
     * @return void
     */
    public function test_deleted_cost_lines_are_not_counted(): void
    {
        $id = $this->seedSppd(8, 15);
        $this->addCost($id, 100000, false);
        $this->addCost($id, 500000, false);
        DB::table('sppd_costs')->where('subtotal', 500000)->update(['deleted_at' => now()]);

        $report = $this->report();

        $this->assertSame('Rp 100.000', $report['data'][0]['total_biaya']);
        $this->assertSame('Rp 100.000', $report['totals']['total_biaya']);
    }

    /**
     * Someone who is not a Perekap Lembur gets 403 on the report and on the detail.
     *
     * @return void
     */
    public function test_user_who_is_not_a_recap_user_is_forbidden(): void
    {
        User::forceCreate(['id' => 7000, 'first_name' => 'Approver', 'id_client' => self::CLIENT_ID]);
        $this->actingAs(User::find(7000));
        $id = $this->seedSppd(8, 15);

        foreach ([
            fn () => app(SppdReportController::class)->index(Request::create('/admin/report-sppd'), app(SppdReportDatatable::class)),
            fn () => app(SppdReportController::class)->show($id),
        ] as $call) {
            try {
                $call();
                $this->fail('Expected a 403.');
            } catch (HttpException $e) {
                $this->assertSame(403, $e->getStatusCode());
            }
        }
    }

    /**
     * The detail only opens for SPPD the recap user covers; another karyawan's id is not found.
     *
     * @return void
     */
    public function test_detail_of_an_sppd_outside_the_recap_users_scope_is_not_found(): void
    {
        $id = $this->seedSppd(6, null);

        $this->expectException(ModelNotFoundException::class);

        app(SppdReportController::class)->show($id);
    }

    /**
     * The report detail is read-only: even while the SPPD waits on a client step, no approve/reject is offered.
     *
     * @return void
     */
    public function test_detail_never_offers_approve_or_reject(): void
    {
        DB::table('sppd_approval_configs')->insert(['id' => 1]);
        DB::table('sppd_approval_config_steps')->insert(['id' => 5, 'sppd_approval_config_id' => 1, 'step_order' => 1, 'step_name' => 'approval client', 'actor_type' => 'client']);
        $id = $this->seedSppd(8, 15, ['status' => 'waiting_approval', 'approval_config_id' => 1, 'current_approval_step' => 1]);

        $data = app(SppdReportController::class)->show($id)->getData(true)['data'];

        $this->assertFalse($data['can_act']);
        $this->assertSame('1/1', $data['step_progress']);
    }
}
