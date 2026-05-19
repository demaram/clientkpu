<?php

namespace App\Services;

use App\Models\LemburApprovalConfig;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class ClientMenuService
{
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

    private function isRecapUser(): bool
    {
        // Prefer Laravel auth; fall back to the session key that ClientAuth middleware validates.
        // Auth::id() may be null when the web guard hasn't been resolved yet, because
        // these routes only run client.auth (not Laravel's auth middleware).
        $userId = Auth::id() ?? data_get(Session::get('user'), 'id');

        if (!$userId) {
            return false;
        }

        return LemburApprovalConfig::where('recap_user_id', $userId)->exists();
    }
}
