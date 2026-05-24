<?php

namespace App\AdminLte\Filters;

use App\Models\LemburApprovalConfig;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use JeroenNoten\LaravelAdminLte\Menu\Filters\FilterInterface;

class ClientRoleMenuFilter implements FilterInterface
{
    /**
     * Cached result of the recap-user check, shared across all menu items
     * in a single request to avoid multiple identical DB queries.
     */
    private static ?bool $isRecapUser = null;

    /**
     * Control menu item visibility based on whether the logged-in user is a
     * recap user on any lembur_approval_configs:
     *
     * - recap users  : see Rekap Lembur; Lembur / Piket / SPPD are hidden
     * - approver users: see Lembur / Piket / SPPD; Rekap Lembur is hidden
     *
     * @param  array  $item  AdminLTE menu item array
     * @return array         Modified menu item (may include 'restricted' => true)
     */
    public function transform($item)
    {
        $key = $item['key'] ?? '';

        if (!in_array($key, ['rekap-lembur', 'menu-lembur', 'menu-piket', 'menu-sppd'], true)) {
            return $item;
        }

        $recap = $this->checkIsRecapUser();

        if ($key === 'rekap-lembur' && !$recap) {
            $item['restricted'] = true;
        }

        if (in_array($key, ['menu-lembur', 'menu-piket', 'menu-sppd'], true) && $recap) {
            $item['restricted'] = true;
        }

        return $item;
    }

    /**
     * Check once per request whether the current user is a recap user.
     * Result is cached in a static property to prevent N+1 queries.
     *
     * @return bool
     */
    private function checkIsRecapUser(): bool
    {
        if (self::$isRecapUser !== null) {
            return self::$isRecapUser;
        }

        $userId = Auth::id() ?? data_get(Session::get('user'), 'id');

        if (!$userId) {
            return self::$isRecapUser = false;
        }

        return self::$isRecapUser = LemburApprovalConfig::where('recap_user_id', $userId)->exists();
    }
}
