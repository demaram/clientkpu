<?php

namespace App\Providers;

use App\Services\ClientMenuService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Session;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Share user data to all views
        View::composer('*', function ($view) {
            $user = Session::get('user');
            $view->with('authUser', $user);
        });
        
        // Configure AdminLTE user info
        if (config('adminlte')) {
            config([
                'adminlte.usermenu_enabled' => true,
                'adminlte.usermenu_header'  => true,
            ]);
        }

        // Dynamic sidebar menu based on user role
        View::composer('adminlte::page', function ($view) {
            if (auth()->check()) {
                config(['adminlte.menu' => (new ClientMenuService())->getMenus()]);
            }
        });
    }
}
