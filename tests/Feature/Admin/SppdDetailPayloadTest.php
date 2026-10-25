<?php

namespace Tests\Feature\Admin;

use App\Http\Controllers\Admin\SppdController;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * What the approver's SPPD detail modal receives: lokasi, keterangan, uraian biaya, lampiran and
 * the approval log with step names, read from the real column names of the SPPD tables.
 */
class SppdDetailPayloadTest extends TestCase
{
    private const CLIENT_ID = 3;

    /**
     * Build the domain tables and sign in as a client admin.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->dropTables();

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
        Schema::create('karyawan_project', function (Blueprint $t) {
            $t->integer('id')->primary();
            $t->string('jabatan')->nullable();
            $t->softDeletes();
        });
        Schema::create('sppd', function (Blueprint $t) {
            $t->integer('id')->primary();
            $t->integer('user_id');
            $t->integer('karyawan_project_id')->nullable();
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

        $this->actingAs(User::forceCreate(['id' => 900, 'first_name' => 'Admin', 'id_client' => self::CLIENT_ID]));

        DB::table('users')->insert(['id' => 349, 'first_name' => 'WIYONO', 'empid' => '05192570', 'id_client' => self::CLIENT_ID]);
        DB::table('sppd_approval_configs')->insert(['id' => 1]);
        DB::table('sppd_approval_config_steps')->insert([
            ['id' => 4, 'sppd_approval_config_id' => 1, 'step_order' => 1, 'step_name' => 'approval pic', 'actor_type' => 'payroll'],
            ['id' => 5, 'sppd_approval_config_id' => 1, 'step_order' => 2, 'step_name' => 'approval client', 'actor_type' => 'client'],
        ]);
        DB::table('sppd')->insert([
            'id' => 9, 'user_id' => 349, 'client_id' => self::CLIENT_ID, 'approval_config_id' => 1, 'current_approval_step' => 1,
            'kode' => 'SPPD2510260001', 'tanggal_berangkat' => '2026-10-05', 'tanggal_kembali' => '2026-10-07', 'durasi' => 3,
            'lokasi_penugasan' => 'Bandung', 'deskripsi' => 'Instalasi perangkat', 'status' => 'waiting_approval',
            'created_at' => now(), 'updated_at' => now(),
        ]);
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
            'sppd', 'karyawan_project', 'user_client_access', 'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }

    /**
     * The modal payload for SPPD 9.
     *
     * @return array<string, mixed>
     */
    private function detail(): array
    {
        return app(SppdController::class)->show(9)->getData(true)['data'];
    }

    /**
     * Lokasi comes from lokasi_penugasan and Keterangan from deskripsi, not a dash.
     *
     * @return void
     */
    public function test_lokasi_and_keterangan_come_from_their_real_columns(): void
    {
        $data = $this->detail();

        $this->assertSame('Bandung', $data['lokasi']);
        $this->assertSame('Instalasi perangkat', $data['keterangan']);
        $this->assertSame('SPPD2510260001', $data['kode']);
    }

    /**
     * An SPPD without lokasi or deskripsi shows a dash.
     *
     * @return void
     */
    public function test_empty_lokasi_and_keterangan_show_a_dash(): void
    {
        DB::table('sppd')->where('id', 9)->update(['lokasi_penugasan' => null, 'deskripsi' => '']);

        $data = $this->detail();

        $this->assertSame('-', $data['lokasi']);
        $this->assertSame('-', $data['keterangan']);
    }

    /**
     * Uraian is the cost line's name, and the attachment shows its original file name.
     *
     * @return void
     */
    public function test_uraian_and_attachment_name_come_from_their_real_columns(): void
    {
        DB::table('sppd_costs')->insert(['id' => 1, 'sppd_id' => 9, 'nama' => 'Uang makan', 'nominal' => 70000, 'hari' => 3, 'subtotal' => 210000, 'diterima_pegawai' => true]);
        DB::table('sppd_attachments')->insert(['id' => 1, 'sppd_id' => 9, 'jenis' => 'memo_dinas', 'file_path' => null, 'original_name' => 'memo.pdf']);

        $data = $this->detail();

        $this->assertSame('Uang makan', $data['costs'][0]['uraian']);
        $this->assertSame('memo.pdf', $data['attachments'][0]['file_name']);
    }

    /**
     * The approval log shows the step name taken from the step the log was written for.
     *
     * @return void
     */
    public function test_approval_log_shows_the_step_name(): void
    {
        DB::table('sppd_approval_logs')->insert(['id' => 1, 'sppd_id' => 9, 'step_id' => 4, 'step_order' => 1, 'status' => 'approved', 'acted_at' => '2026-10-25 20:05:11', 'acted_from' => 'payroll']);

        $log = $this->detail()['approval_logs'][0];

        $this->assertSame(1, $log['step_order']);
        $this->assertSame('approval pic', $log['step_name']);
    }

    /**
     * Editing an approval config recreates its steps and the old logs lose their step link; the name is then
     * found by step number in the SPPD's current config.
     *
     * @return void
     */
    public function test_log_without_a_step_link_falls_back_to_the_config_step_with_the_same_number(): void
    {
        DB::table('sppd_approval_logs')->insert([
            ['id' => 1, 'sppd_id' => 9, 'step_id' => null, 'step_order' => 1, 'status' => 'approved', 'notes' => null, 'acted_at' => '2026-10-25 20:05:00', 'acted_from' => 'payroll'],
            ['id' => 2, 'sppd_id' => 9, 'step_id' => null, 'step_order' => 2, 'status' => 'approved', 'notes' => null, 'acted_at' => '2026-10-25 20:06:00', 'acted_from' => 'clientkpu'],
        ]);

        $logs = $this->detail()['approval_logs'];

        $this->assertSame(['approval pic', 'approval client'], array_column($logs, 'step_name'));
    }

    /**
     * A Pengembalian SPPD row belongs to no step, and rounds read in the order they happened rather than by step number.
     *
     * @return void
     */
    public function test_reset_row_has_no_step_and_logs_are_in_time_order(): void
    {
        DB::table('sppd_approval_logs')->insert([
            ['id' => 1, 'sppd_id' => 9, 'step_id' => 4, 'step_order' => 1, 'status' => 'approved', 'notes' => null, 'acted_at' => '2026-10-25 20:05:00', 'acted_from' => 'payroll'],
            ['id' => 2, 'sppd_id' => 9, 'step_id' => 5, 'step_order' => 2, 'status' => 'approved', 'notes' => null, 'acted_at' => '2026-10-25 20:06:00', 'acted_from' => 'clientkpu'],
            ['id' => 3, 'sppd_id' => 9, 'step_id' => null, 'step_order' => 0, 'status' => 'reset', 'notes' => 'Nominal salah', 'acted_at' => '2026-10-26 09:00:00', 'acted_from' => 'payroll'],
            ['id' => 4, 'sppd_id' => 9, 'step_id' => 4, 'step_order' => 1, 'status' => 'approved', 'notes' => null, 'acted_at' => '2026-10-26 09:30:00', 'acted_from' => 'payroll'],
        ]);

        $logs = $this->detail()['approval_logs'];

        $this->assertSame(['approved', 'approved', 'reset', 'approved'], array_column($logs, 'status'));
        $this->assertNull($logs[2]['step_order']);
        $this->assertNull($logs[2]['step_name']);
        $this->assertSame('Nominal salah', $logs[2]['notes']);
    }

    /**
     * The approver's modal still offers approve/reject when the active step is a client step.
     *
     * @return void
     */
    public function test_approver_detail_offers_actions_on_a_client_step(): void
    {
        DB::table('sppd')->where('id', 9)->update(['current_approval_step' => 2]);

        $data = $this->detail();

        $this->assertTrue($data['can_act']);
        $this->assertSame('2/2', $data['step_progress']);
    }
}
