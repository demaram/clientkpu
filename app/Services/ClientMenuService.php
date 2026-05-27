<?php

namespace App\Services;

use App\Models\LemburApprovalConfig;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class ClientMenuService
{
    /**
     * Build the full sidebar menu array for the current user.
     *
     * Rekap Lembur is appended only when the user is a recap_user_id on any
     * lembur_approval_configs. The ClientRoleMenuFilter in adminlte.php also
     * hides Lembur/Piket/SPPD for recap users; this service is kept for
     * any programmatic menu overrides.
     *
     * @return array
     */
    public function getMenus(): array
    {
        $menus = [
            // Navbar items
            [
                'type'          => 'navbar-search',
                'text'          => 'search',
                'topnav_right'  => true,
            ],
            [
                'type'          => 'fullscreen-widget',
                'topnav_right'  => true,
            ],
            // Sidebar items
            [
                'type' => 'sidebar-menu-search',
                'text' => 'search',
            ],
            [
                'text'   => 'Dashboard',
                'route'  => 'admin.dashboard',
                'icon'   => 'fas fa-fw fa-tachometer-alt',
                'active' => ['admin/dashboard'],
            ],
            ['header' => 'Manajemen'],
            [
                'text' => 'Lembur',
                'url'  => 'admin/lembur',
                'icon' => 'fas fa-fw fa-clock',
            ],
            [
                'text' => 'Piket',
                'url'  => 'admin/piket',
                'icon' => 'fas fa-fw fa-calendar-check',
            ],
            [
                'text' => 'SPPD',
                'url'  => 'admin/sppd',
                'icon' => 'fas fa-fw fa-plane',
            ],
        ];

        if ($this->isRecapUser()) {
            $menus[] = [
                'text' => 'Rekap Lembur',
                'url'  => 'admin/rekap-lembur',
                'icon' => 'fas fa-fw fa-file-invoice',
            ];
        }

        return $menus;
    }

    /**
     * Check whether the current user is a recap_user_id on any approval config.
     *
     * Priority order to avoid unnecessary DB queries:
     *  1. Session flag set at login time (no DB hit).
     *  2. DB fallback for sessions that pre-date the session flag being stored.
     *
     * @return bool
     */
    private function isRecapUser(): bool
    {
        // Session flag is written by LoginController on every successful login.
        if (Session::has('is_recap_user')) {
            return (bool) Session::get('is_recap_user');
        }

        // Fallback: DB check.
        // Auth::id() may be null when the web guard hasn't been resolved yet
        // because these routes only use client.auth (not Laravel's auth middleware).
        $userId = Auth::id() ?? data_get(Session::get('user'), 'id');

        if (!$userId) {
            return false;
        }

        return LemburApprovalConfig::where('recap_user_id', $userId)->exists();
    }
}
