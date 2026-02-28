<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LemburController;
use App\Http\Controllers\Admin\PiketController;
use App\Http\Controllers\Auth\LoginController;

// Redirect root ke login
Route::get('/', function () {
    return redirect()->route('login');
});

// Auth Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Admin Routes - Protected with client.auth middleware
Route::prefix('admin')->middleware('client.auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
    
    // Lembur Routes
    Route::prefix('lembur')->group(function () {
        Route::get('/', [LemburController::class, 'index'])->name('admin.lembur.index');
        Route::get('/{id}', [LemburController::class, 'show'])->name('admin.lembur.show');
        Route::post('/{id}/approve', [LemburController::class, 'approve'])->name('admin.lembur.approve');
        Route::post('/{id}/reject', [LemburController::class, 'reject'])->name('admin.lembur.reject');
    });

    Route::prefix('piket')->group(function () {
        Route::get('/', [PiketController::class, 'index'])->name('admin.piket.index');
        Route::get('/{id}', [PiketController::class, 'show'])->name('admin.piket.show');
        Route::post('/{id}/approve', [PiketController::class, 'approve'])->name('admin.piket.approve');
        Route::post('/{id}/reject', [PiketController::class, 'reject'])->name('admin.piket.reject');
    });
});
