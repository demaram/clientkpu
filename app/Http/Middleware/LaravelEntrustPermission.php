<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class LaravelEntrustPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $permission
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (Auth::guest()) {
            abort(403, 'Unauthorized access.');
        }

        $permissions = explode('|', $permission);
        
        if (!Auth::user()->hasPermission($permissions)) {
            abort(403, 'You do not have the required permission to access this page.');
        }

        return $next($request);
    }
}
