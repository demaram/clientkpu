<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = Session::get('auth_token');
        $user = Session::get('user');

        // Jika sudah login, redirect ke dashboard
        if ($token && $user) {
            return redirect()->route('admin.dashboard');
        }

        return $next($request);
    }
}
