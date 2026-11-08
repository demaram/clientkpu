<?php

namespace Tests\Feature\Services;

use App\Services\SppdDashboardStatsService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The SPPD card on the client dashboard: SPPD On Process (waiting on a SIP - KPU step),
 * SPPD Waiting Approval (waiting on a client step or without approval config), Approved
 * and Rejected, for the user's clients and by tanggal berangkat in the selected month.
 */
class SppdDashboardStatsServiceTest extends TestCase
{
    private const CLIENT_ID = 3;

    private int $nextId = 1;

    /**
     * Build the SPPD tables with one config: step 1 SIP - KPU (payroll), step 2 client.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->dropTables();

        Schema::create('sppd', function (Blueprint $t) {
            $t->integer('id')->primary();
            $t->integer('client_id');
            $t->integer('approval_config_id')->nullable();
            $t->integer('current_approval_step')->default(1);
            $t->date('tanggal_berangkat');
            $t->string('status');
            $t->softDeletes();
        });
        Schema::create('sppd_approval_configs', function (Blueprint $t) {
            $t->integer('id')->primary();
        });
        Schema::create('sppd_approval_config_steps', function (Blueprint $t) {
            $t->integer('id')->primary();
            $t->integer('sppd_approval_config_id');
            $t->integer('step_order');
            $t->string('actor_type');
        });

        DB::table('sppd_approval_configs')->insert(['id' => 1]);
        DB::table('sppd_approval_config_steps')->insert([
            ['id' => 1, 'sppd_approval_config_id' => 1, 'step_order' => 1, 'actor_type' => 'payroll'],
            ['id' => 2, 'sppd_approval_config_id' => 1, 'step_order' => 2, 'actor_type' => 'client'],
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
        foreach (['sppd_approval_config_steps', 'sppd_approval_configs', 'sppd'] as $table) {
            Schema::dropIfExists($table);
        }
    }

    /**
     * Insert one SPPD.
     *
     * @param  string  $status
     * @param  int  $step  current_approval_step
     * @param  string  $berangkat  tanggal berangkat (Y-m-d)
     * @param  array<string, mixed>  $extra  Column overrides
     * @return void
     */
    private function sppd(string $status, int $step = 1, string $berangkat = '2026-10-10', array $extra = []): void
    {
        DB::table('sppd')->insert(array_merge([
            'id' => $this->nextId++, 'client_id' => self::CLIENT_ID, 'approval_config_id' => 1,
            'current_approval_step' => $step, 'tanggal_berangkat' => $berangkat, 'status' => $status,
        ], $extra));
    }

    /**
     * Counts for October 2026.
     *
     * @param  int[]  $clientIds
     * @return array<string, int>
     */
    private function counts(array $clientIds = [self::CLIENT_ID]): array
    {
        return app(SppdDashboardStatsService::class)->statusCounts(
            $clientIds,
            Carbon::parse('2026-10-01')->startOfMonth(),
            Carbon::parse('2026-10-01')->endOfMonth()
        );
    }

    /**
     * Waiting on the PIC step is On Process; waiting on the client step is Waiting Approval.
     *
     * @return void
     */
    public function test_waiting_splits_by_the_owner_of_the_active_step(): void
    {
        $this->sppd('waiting_approval', 1);
        $this->sppd('waiting_approval', 2);
        $this->sppd('waiting_approval', 2);
        $this->sppd('approved', 2);
        $this->sppd('rejected', 1);

        $this->assertSame(
            ['on_process' => 1, 'waiting_approval' => 2, 'approved' => 1, 'rejected' => 1],
            $this->counts()
        );
    }

    /**
     * An SPPD without approval config can be approved by the client, so it is Waiting Approval.
     *
     * @return void
     */
    public function test_waiting_without_config_counts_as_waiting_approval(): void
    {
        $this->sppd('waiting_approval', 1, '2026-10-10', ['approval_config_id' => null]);

        $this->assertSame(1, $this->counts()['waiting_approval']);
        $this->assertSame(0, $this->counts()['on_process']);
    }

    /**
     * Only SPPD departing within the month count, including its first and last day.
     *
     * @return void
     */
    public function test_counts_by_tanggal_berangkat_in_the_period(): void
    {
        $this->sppd('approved', 2, '2026-10-01');
        $this->sppd('approved', 2, '2026-10-31');
        $this->sppd('approved', 2, '2026-09-30');
        $this->sppd('approved', 2, '2026-11-01');

        $this->assertSame(2, $this->counts()['approved']);
    }

    /**
     * Other clients' SPPD and Data Terhapus are not counted; a user without clients sees zeros.
     *
     * @return void
     */
    public function test_only_the_users_clients_and_not_deleted(): void
    {
        $this->sppd('approved', 2, '2026-10-10', ['client_id' => 99]);
        $this->sppd('approved', 2, '2026-10-10', ['deleted_at' => '2026-10-20 10:00:00']);
        $this->sppd('approved');

        $this->assertSame(1, $this->counts()['approved']);
        $this->assertSame(
            ['on_process' => 0, 'waiting_approval' => 0, 'approved' => 0, 'rejected' => 0],
            $this->counts([])
        );
    }
}
