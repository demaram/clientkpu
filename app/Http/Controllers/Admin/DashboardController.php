<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LemburKaryawan;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    
    /**
     * Show the admin dashboard.
     */
    public function index()
    {
        $user = Auth::user();
        $clientId = $user?->id_client;

        $statusCounts = LemburKaryawan::query()
            ->where('type', 'lembur')
            ->when($clientId, function ($query) use ($clientId) {
                return $query->where('client_id', $clientId);
            })
            ->selectRaw("status, COUNT(*) as total")
            ->groupBy('status')
            ->pluck('total', 'status');

        $lemburCounts = [
            'pending' => (int) ($statusCounts['pending'] ?? 0),
            'waiting_approval' => (int) ($statusCounts['waiting_approval'] ?? 0),
            'approved' => (int) ($statusCounts['approved'] ?? 0),
            'rejected' => (int) ($statusCounts['rejected'] ?? 0),
        ];
        
        return view('admin.dashboard', [
            'user' => $user,
            'lemburCounts' => $lemburCounts,
        ]);
    }
}
