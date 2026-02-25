<?php

use App\Http\Controllers\TenantController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/tenants/onboard', [TenantController::class, 'onboardForm'])->name('tenants.onboard.form');
Route::post('/tenants/onboard', [TenantController::class, 'onboard'])->name('tenants.onboard');
Route::get('/tenants/status', [TenantController::class, 'status'])->name('tenants.status');
