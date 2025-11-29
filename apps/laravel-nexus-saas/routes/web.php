<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/settings.php';

use App\Http\Controllers\Backoffice\CompanyController;
use App\Http\Controllers\Backoffice\OfficeController;
use App\Http\Controllers\Backoffice\DepartmentController;
use App\Http\Controllers\Backoffice\StaffController;

Route::middleware(['auth', 'verified'])->prefix('backoffice')->name('backoffice.')->group(function () {
    Route::resource('companies', CompanyController::class);
    Route::resource('offices', OfficeController::class);
    Route::resource('departments', DepartmentController::class);
    Route::resource('staff', StaffController::class);
});

