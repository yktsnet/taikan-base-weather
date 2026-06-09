<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/dashboard', [DashboardController::class, 'index']);
Route::get('/stations/{id}', [DashboardController::class, 'show'])->name('stations.show');
Route::get('/alerts', [DashboardController::class, 'alerts'])->name('alerts.index');
