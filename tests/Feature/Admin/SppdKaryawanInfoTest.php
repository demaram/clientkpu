<?php

namespace Tests\Feature\Admin;

use App\Datatables\SppdDatatable;
use App\Http\Controllers\Admin\SppdController;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Covers what the client admin sees for an SPPD: who the karyawan is (name, empid,
 * jabatan) and the Durasi Perjalanan.
 *
 * The SPPD tables belong to the shared legacy database and have no migrations in this
 * repo, so each test builds the tables it needs, reduced to the columns in play, on the
 * in-memory sqlite connection (see phpunit.xml / .env.testing). The SPPD row links its
 * karyawan through `user_id`, like the real `sppd` table.
 */
class SppdKaryawanInfoTest extends TestCase
{
    private const CLIENT_ID = 3;

    private User $admin;

    /**
     * Build the domain tables and sign in as a client admin of {@see self::CLIENT_ID}.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->createDomainTables();

        $this->admin = User::forceCreate([
            'id'         => 900,
            'first_name' => 'Admin',
            'last_name'  => 'Client',
            'id_client'  => self::CLIENT_ID,
        ]);

        $this->actingAs($this->admin);
    }

    /**
     * Drop the domain tables created for the test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        foreach ([
            'sppd_approval_logs', 'sppd_attachments', 'sppd_costs', 'sppd_approval_config_steps',
            'sppd_approval_configs', 'sppd', 'karyawan_project', 'client', 'user_client_access', 'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    /**
     * The tables the SPPD listing and detail read, reduced to the columns they touch.
     *
     * @return void
     */
    private function createDomainTables(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('empid')->nullable();
            $table->integer('id_client')->nullable();
            $table->timestamps();
        });

        Schema::create('user_client_access', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->integer('client_id');
        });

        Schema::create('client', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->string('nama')->nullable();
        });

        Schema::create('karyawan_project', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->integer('karyawan_id')->nullable();
            $table->string('jabatan')->nullable();
        });

        Schema::create('sppd', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->integer('user_id');
            $table->integer('karyawan_project_id')->nullable();
            $table->integer('client_id');
            $table->integer('approval_config_id')->nullable();
            $table->integer('current_approval_step')->default(1);
            $table->string('kode');
            $table->date('tanggal_berangkat');
            $table->date('tanggal_kembali');
            $table->integer('durasi');
            $table->string('status')->default('waiting_approval');
            $table->timestamps();
        });

        Schema::create('sppd_approval_configs', function (Blueprint $table) {
            $table->integer('id')->primary();
        });

        Schema::create('sppd_approval_config_steps', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->integer('sppd_approval_config_id');
            $table->integer('step_order');
            $table->string('actor_type')->nullable();
        });

        Schema::create('sppd_costs', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->integer('sppd_id');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->boolean('diterima_pegawai')->default(false);
        });

        Schema::create('sppd_attachments', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->integer('sppd_id');
        });

        Schema::create('sppd_approval_logs', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->integer('sppd_id');
            $table->integer('step_order')->nullable();
            $table->string('status')->nullable();
        });
    }

    /**
     * Insert a karyawan plus one SPPD of theirs, returning the SPPD id.
     *
     * @param  string  $firstName  The karyawan's first name
     * @param  string  $empid  The karyawan's empid
     * @param  array<string, mixed>  $sppd  Overrides for the sppd row
     * @param  string|null  $jabatan  Jabatan on the karyawan's project placement; null = no placement
     * @return int
     */
    private function seedSppdFor(string $firstName, string $empid, array $sppd = [], ?string $jabatan = null): int
    {
        $userId = 100 + (int) DB::table('users')->count();

        DB::table('users')->insert([
            'id' => $userId, 'first_name' => $firstName, 'last_name' => null, 'empid' => $empid, 'id_client' => self::CLIENT_ID,
        ]);
        DB::table('client')->insertOrIgnore(['id' => self::CLIENT_ID, 'nama' => 'PT Client']);

        $penempatanId = null;
        if ($jabatan !== null) {
            $penempatanId = $userId;
            DB::table('karyawan_project')->insert(['id' => $penempatanId, 'karyawan_id' => $userId, 'jabatan' => $jabatan]);
        }

        $sppdId = $userId;
        DB::table('sppd')->insert(array_merge([
            'id'                  => $sppdId,
            'user_id'             => $userId,
            'karyawan_project_id' => $penempatanId,
            'client_id'           => self::CLIENT_ID,
            'kode'                => 'SPPD' . $sppdId,
            'tanggal_berangkat'   => '2026-09-27',
            'tanggal_kembali'     => '2026-09-30',
            'durasi'              => 4,
            'created_at'          => now(),
            'updated_at'          => now(),
        ], $sppd));

        return $sppdId;
    }

    /**
     * Render the SPPD listing the way the DataTable ajax call does and return its rows.
     *
     * @return array<int, array<string, mixed>>
     */
    private function listingRows(): array
    {
        $request = Request::create('/admin/sppd', 'GET', ['draw' => 1, 'start' => 0, 'length' => 10]);
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        $this->app->instance('request', $request);

        return app(SppdDatatable::class)->render($request)->getData(true)['data'];
    }

    /**
     * The listing shows who filed each SPPD: the karyawan's name and empid, not a dash.
     *
     * @return void
     */
    public function test_listing_shows_karyawan_name_and_empid(): void
    {
        $this->seedSppdFor('WIYONO', '05192570');

        $rows = $this->listingRows();

        $this->assertCount(1, $rows);
        $this->assertSame('WIYONO', $rows[0]['karyawan']);
        $this->assertSame('05192570', $rows[0]['empid']);
    }

    /**
     * The listing shows the stored Durasi Perjalanan (4 for 27-30 Sep).
     *
     * @return void
     */
    public function test_listing_shows_durasi_from_the_durasi_column(): void
    {
        $this->seedSppdFor('WIYONO', '05192570', ['durasi' => 4]);

        $this->assertSame('4 hari', $this->listingRows()[0]['durasi']);
    }

    /**
     * The durasi column stays the source of truth: an old row (1-3 Sep stored as 2) is not silently recomputed to 3.
     *
     * @return void
     */
    public function test_listing_does_not_recompute_durasi_from_the_dates(): void
    {
        // Data lama: 1-3 Sep tersimpan 2 (hitungan lama, tanpa backfill). Kolom tetap sumber kebenaran.
        $this->seedSppdFor('DODI', '01170336', [
            'tanggal_berangkat' => '2026-09-01',
            'tanggal_kembali'   => '2026-09-03',
            'durasi'            => 2,
        ]);

        $this->assertSame('2 hari', $this->listingRows()[0]['durasi']);
    }

    /**
     * The detail modal shows name and empid, and the jabatan from the karyawan's project placement.
     *
     * @return void
     */
    public function test_detail_shows_karyawan_name_empid_and_jabatan(): void
    {
        $id = $this->seedSppdFor('WIYONO', '05192570', [], 'Driver Direktur');

        $data = app(SppdController::class)->show($id)->getData(true)['data'];

        $this->assertSame('WIYONO', $data['karyawan']);
        $this->assertSame('05192570', $data['empid']);
        $this->assertSame('Driver Direktur', $data['jabatan']);
    }

    /**
     * An SPPD without a project placement shows a dash for jabatan instead of failing.
     *
     * @return void
     */
    public function test_detail_shows_a_dash_for_jabatan_without_a_placement(): void
    {
        $id = $this->seedSppdFor('WIYONO', '05192570');

        $data = app(SppdController::class)->show($id)->getData(true)['data'];

        $this->assertSame('-', $data['jabatan']);
    }
}
