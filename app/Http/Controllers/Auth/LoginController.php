<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class LoginController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * Show the login form
     * 
     * @return \Illuminate\View\View
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle login request
     * 
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function login(Request $request)
    {
        
        // Validasi input
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
        ], [
            'email.required' => 'Email harus diisi',
            'email.email' => 'Format email tidak valid',
            'password.required' => 'Password harus diisi',
            'password.min' => 'Password minimal 6 karakter',
        ]);

        // Attempt login dengan database
        $credentials = $request->only('email', 'password');
        $remember = $request->filled('remember');
        $checkCredectials = Auth::attempt($credentials, $remember);
        if ($checkCredectials) {
            // Regenerate session dulu untuk keamanan
            $request->session()->regenerate();
            
            // Get user instance
            $user = Auth::user();
            
            // Check if user has client role
            try {
                // Load roles relationship
                $hasClientRole = $user->hasRole('client');
                
                if (!$hasClientRole) {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                    
                    return back()
                        ->withInput($request->only('email'))
                        ->withErrors([
                            'email' => 'Akses ditolak. Hanya client yang dapat login melalui halaman ini.'
                        ]);
                }
            } catch (\Exception $e) {
                
            }
            
            // Set session untuk ClientAuth middleware
            Session::put('auth_token', $user->remember_token ?? 'local_auth_' . $user->id);
            
            Session::put('user', [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]);
            
            return redirect()->intended(route('admin.dashboard'))
                ->with('success', 'Selamat datang, ' . $user->name);
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors([
                'email' => 'Email atau password yang Anda masukkan salah'
            ]);
    }

    /**
     * Handle logout request
     * 
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'Anda telah logout');
    }
}
