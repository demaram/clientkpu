<?php

namespace App\AdminLte\Filters;

use App\Models\LemburApprovalConfig;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use JeroenNoten\LaravelAdminLte\Menu\Filters\FilterInterface;

class RecapLemburFilter implements FilterInterface
{
    /**
     * Hide the Rekap Lembur menu item for users who are not a recap_user_id
     * on any lembur_approval_configs. Runs at menu-compile time (lazy, per
     * request) so the session is available.
     */
    public function transform($item)
    {
        if (($item['key'] ?? '') !== 'rekap-lembur') {
            return $item;
        }

        $userId = Auth::id() ?? data_get(Session::get('user'), 'id');

        if (!$userId || !LemburApprovalConfig::where('recap_user_id', $userId)->exists()) {
            $item['restricted'] = true;
        }

        return $item;
    }
}
