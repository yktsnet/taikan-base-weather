<?php

use App\Http\Controllers\Admin\VerificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/dashboard', [DashboardController::class, 'index']);
Route::get('/stations/{id}', [DashboardController::class, 'show'])->name('stations.show');
Route::get('/alerts', [DashboardController::class, 'alerts'])->name('alerts.index');

// Auth Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Admin Routes (Protected)
Route::middleware('auth')->prefix('admin')->group(function () {
    Route::get('/verification', function () {
        return Inertia::render('Admin/Verification');
    })->name('admin.verification');

    // Verification APIs
    Route::post('/api/load-test', [VerificationController::class, 'loadTest'])->name('admin.api.load_test');
    Route::get('/api/metrics', [VerificationController::class, 'getMetrics'])->name('admin.api.metrics');
    Route::post('/api/dlq-redrive', [VerificationController::class, 'redriveDlq'])->name('admin.api.dlq_redrive');
    Route::get('/api/s3-archives', [VerificationController::class, 'getS3Archives'])->name('admin.api.s3_archives');
    Route::get('/api/s3-archives/download', [VerificationController::class, 'downloadS3Archive'])->name('admin.api.s3_archive_download');
});
