<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LemburController;
use App\Http\Controllers\Admin\LemburRekapController;
use App\Http\Controllers\Admin\PiketController;
use App\Http\Controllers\Admin\PiketRekapController;
use App\Http\Controllers\Admin\GantiPasswordController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\SppdController;
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
        Route::get('/{id}/edit', [LemburController::class, 'edit'])->name('admin.lembur.edit');
        Route::put('/{id}', [LemburController::class, 'update'])->name('admin.lembur.update');
        Route::get('/{id}', [LemburController::class, 'show'])->name('admin.lembur.show');
        Route::post('/{id}/approve', [LemburController::class, 'approve'])->name('admin.lembur.approve');
        Route::post('/{id}/reject', [LemburController::class, 'reject'])->name('admin.lembur.reject');
    });

    Route::prefix('piket')->group(function () {
        Route::get('/', [PiketController::class, 'index'])->name('admin.piket.index');
        Route::get('/{id}/edit', [PiketController::class, 'edit'])->name('admin.piket.edit');
        Route::put('/{id}', [PiketController::class, 'update'])->name('admin.piket.update');
        Route::get('/{id}', [PiketController::class, 'show'])->name('admin.piket.show');
        Route::post('/{id}/approve', [PiketController::class, 'approve'])->name('admin.piket.approve');
        Route::post('/{id}/reject', [PiketController::class, 'reject'])->name('admin.piket.reject');
    });

    // Rekap Lembur
    Route::prefix('rekap-lembur')->name('admin.rekap-lembur.')->group(function () {
        Route::get('/',       [LemburRekapController::class, 'index'])->name('index');
        Route::get('/form',   [LemburRekapController::class, 'form'])->name('form');
        Route::post('/approve', [LemburRekapController::class, 'approve'])->name('approve');
        Route::post('/request-update', [LemburRekapController::class, 'requestUpdate'])->name('request-update');
        Route::post('/reject-record', [LemburRekapController::class, 'rejectRecord'])->name('reject-record');
        Route::get('/{id}/detail', [LemburRekapController::class, 'detail'])->name('detail');
        Route::get('/lembur/{id}/detail-ajax', [LemburRekapController::class, 'detailAjax'])->name('detail-ajax');
    });

    // Rekap Piket
    Route::prefix('rekap-piket')->name('admin.rekap-piket.')->group(function () {
        Route::get('/',       [PiketRekapController::class, 'index'])->name('index');
        Route::get('/form',   [PiketRekapController::class, 'form'])->name('form');
        Route::post('/approve', [PiketRekapController::class, 'approve'])->name('approve');
        Route::post('/request-update', [PiketRekapController::class, 'requestUpdate'])->name('request-update');
        Route::post('/reject-record', [PiketRekapController::class, 'rejectRecord'])->name('reject-record');
        Route::get('/{id}/detail', [PiketRekapController::class, 'detail'])->name('detail');
        Route::get('/piket/{id}/detail-ajax', [PiketRekapController::class, 'detailAjax'])->name('detail-ajax');
    });

    // SPPD Routes
    Route::prefix('sppd')->group(function () {
        Route::get('/', [SppdController::class, 'index'])->name('admin.sppd.index');
        Route::get('/{id}', [SppdController::class, 'show'])->name('admin.sppd.show');
        Route::post('/{id}/approve', [SppdController::class, 'approve'])->name('admin.sppd.approve');
        Route::post('/{id}/reject', [SppdController::class, 'reject'])->name('admin.sppd.reject');
    });

    // Profile
    Route::get('/profile', [ProfileController::class, 'index'])->name('admin.profile.index');

    // Ganti Password
    Route::get('/ganti-password', [GantiPasswordController::class, 'index'])->name('admin.ganti-password.index');
    Route::post('/ganti-password', [GantiPasswordController::class, 'update'])->name('admin.ganti-password.update');
});
