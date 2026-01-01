<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use App\Services\PayrollApiService;
use Symfony\Component\HttpFoundation\Response;

class ClientAuth
{
    protected $payrollApi;

    public function __construct(PayrollApiService $payrollApi)
    {
        $this->payrollApi = $payrollApi;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        
        $token = Session::get('auth_token');
        $user = Session::get('user');
        
        // Jika tidak ada token atau user, redirect ke login
        if (!$token || !$user) {
            return redirect()->route('login')
                ->with('error', 'Silakan login terlebih dahulu');
        }

        // Validasi token dengan API (optional, bisa di-cache untuk performa)
        // Uncomment jika ingin validasi setiap request
        /*
        $result = $this->payrollApi->me($token);
        
        if (!$result['success']) {
            Session::flush();
            return redirect()->route('login')
                ->with('error', 'Sesi Anda telah berakhir, silakan login kembali');
        }
        
        // Update session dengan data terbaru
        Session::put('user', $result['data']['data']['user']);
        */

        return $next($request);
    }
}
