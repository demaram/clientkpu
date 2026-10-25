<?php

namespace Tests\Unit;

use App\AdminLte\Filters\ClientRoleMenuFilter;
use Illuminate\Support\Facades\Session;
use ReflectionProperty;
use Tests\TestCase;

/**
 * The Report SPPD menu is for Perekap Lembur only, and the approver's SPPD menu stays hidden for them.
 */
class ClientRoleMenuFilterReportSppdTest extends TestCase
{
    /**
     * The filter caches the answer per request in a static; clear it so each test starts fresh.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->resetCache();
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        $this->resetCache();

        parent::tearDown();
    }

    /**
     * @return void
     */
    private function resetCache(): void
    {
        $cache = new ReflectionProperty(ClientRoleMenuFilter::class, 'isRecapUser');
        $cache->setAccessible(true);
        $cache->setValue(null, null);
    }

    /**
     * @param  string  $key  Menu item key
     * @param  bool  $recap  Whether the session says the user is a Perekap Lembur
     * @return bool Whether the menu item ends up restricted (hidden)
     */
    private function isHidden(string $key, bool $recap): bool
    {
        $this->resetCache();
        Session::put('is_recap_user', $recap);

        $item = (new ClientRoleMenuFilter())->transform(['key' => $key]);

        return (bool) ($item['restricted'] ?? false);
    }

    /**
     * Report SPPD is visible to a Perekap Lembur and hidden from everyone else.
     *
     * @return void
     */
    public function test_report_sppd_menu_is_only_for_recap_users(): void
    {
        $this->assertFalse($this->isHidden('report-sppd', true));
        $this->assertTrue($this->isHidden('report-sppd', false));
    }

    /**
     * The approver's SPPD menu is hidden from a Perekap Lembur and still shown to approvers.
     *
     * @return void
     */
    public function test_approver_sppd_menu_stays_hidden_for_recap_users(): void
    {
        $this->assertTrue($this->isHidden('menu-sppd', true));
        $this->assertFalse($this->isHidden('menu-sppd', false));
    }
}
