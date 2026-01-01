<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    
    /**
     * Show the admin dashboard.
     */
    public function index()
    {
        $user = Auth::user();
        
        return view('admin.dashboard', [
            'user' => $user
        ]);
    }
}
