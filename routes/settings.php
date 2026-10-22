<?php

use App\Http\Controllers\Settings\CompanyController;
use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('auth')->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('settings/password', [PasswordController::class, 'update'])->name('password.update');

    Route::get('settings/appearance', function () {
        return Inertia::render('settings/appearance');
    })->name('appearance');

    Route::middleware('role:superadmin')->group(function () {
        Route::get('settings/company', [CompanyController::class, 'edit'])->name('company.edit');
        Route::post('settings/company', [CompanyController::class, 'update'])->name('company.update');
    });
});

// Public: the logo is also shown on the sign-in page.
Route::get('branding/logo', [CompanyController::class, 'logo'])->name('branding.logo');
