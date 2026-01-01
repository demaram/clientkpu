<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class LaravelEntrustRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $role
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (Auth::guest()) {
            abort(403, 'Unauthorized access.');
        }

        $roles = explode('|', $role);
        
        if (!Auth::user()->hasRole($roles)) {
            abort(403, 'You do not have the required role to access this page.');
        }

        return $next($request);
    }
}
