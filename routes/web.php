<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FormTemplateController;
use App\Http\Controllers\PublicFormController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->to(auth()->user()->homeRoute());
    }

    return redirect()->route('login');
})->name('home');

// Public campaign forms: no login, one link per campaign location.
Route::get('f/{token}', [PublicFormController::class, 'show'])->name('public-form.show');
Route::post('f/{token}', [PublicFormController::class, 'store'])->middleware('throttle:public-form')->name('public-form.store');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::middleware('role:superadmin,client')->group(function () {
        Route::get('submissions', [SubmissionController::class, 'index'])->name('submissions.index');
        Route::get('submissions/export', [SubmissionController::class, 'export'])->name('submissions.export');
        Route::get('submissions/{submission}', [SubmissionController::class, 'show'])->whereNumber('submission')->name('submissions.show');
        Route::get('submissions/{submission}/files/{field}', [SubmissionController::class, 'file'])->whereNumber('submission')->name('submissions.file');
    });

    Route::middleware('role:superadmin')->group(function () {
        // Campaign management (incl. public links) is EO-only; clients see results via dashboard & entries.
        Route::resource('campaigns', CampaignController::class);
        Route::post('campaigns/{campaign}/activate', [CampaignController::class, 'activate'])->name('campaigns.activate');
        Route::post('campaigns/{campaign}/close', [CampaignController::class, 'close'])->name('campaigns.close');

        Route::resource('forms', FormTemplateController::class)->except(['show']);
        Route::get('forms/{form}/preview', [FormTemplateController::class, 'preview'])->name('forms.preview');
        Route::post('forms/{form}/publish', [FormTemplateController::class, 'publish'])->name('forms.publish');
        Route::post('forms/{form}/version', [FormTemplateController::class, 'version'])->name('forms.version');

        Route::post('submissions/{submission}/void', [SubmissionController::class, 'void'])->name('submissions.void');
        Route::get('audit', [AuditLogController::class, 'index'])->name('audit.index');
        Route::resource('users', UserController::class)->except(['show']);
        Route::put('users/{user}/password', [UserController::class, 'resetPassword'])->name('users.password');
    });

});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
