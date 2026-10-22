<?php

use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FormTemplateController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\Spg\SubmissionCorrectionController;
use App\Http\Controllers\Spg\TaskController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->to(auth()->user()->homeRoute());
    }

    return redirect()->route('login');
})->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::middleware('role:superadmin,client')->group(function () {
        Route::get('clients', [ClientController::class, 'index'])->name('clients.index');
        Route::get('clients/{client}', [ClientController::class, 'show'])->name('clients.show');
        Route::get('products', [ProductController::class, 'index'])->name('products.index');
        Route::get('campaigns', [CampaignController::class, 'index'])->name('campaigns.index');
        Route::get('campaigns/{campaign}', [CampaignController::class, 'show'])->name('campaigns.show');
        Route::get('submissions', [SubmissionController::class, 'index'])->name('submissions.index');
        Route::get('submissions/export', [SubmissionController::class, 'export'])->name('submissions.export');
        Route::get('submissions/{submission}', [SubmissionController::class, 'show'])->name('submissions.show');
    });

    Route::middleware('role:superadmin')->group(function () {
        Route::resource('clients', ClientController::class)->except(['index', 'show']);
        Route::resource('products', ProductController::class)->except(['index', 'show']);
        Route::resource('campaigns', CampaignController::class)->except(['index', 'show']);
        Route::post('campaigns/{campaign}/activate', [CampaignController::class, 'activate'])->name('campaigns.activate');
        Route::post('campaigns/{campaign}/close', [CampaignController::class, 'close'])->name('campaigns.close');

        Route::resource('forms', FormTemplateController::class)->except(['show', 'destroy']);
        Route::get('forms/{form}/preview', [FormTemplateController::class, 'preview'])->name('forms.preview');
        Route::post('forms/{form}/publish', [FormTemplateController::class, 'publish'])->name('forms.publish');
        Route::post('forms/{form}/version', [FormTemplateController::class, 'version'])->name('forms.version');

        Route::resource('assignments', AssignmentController::class)->except(['show', 'destroy']);
        Route::post('submissions/{submission}/void', [SubmissionController::class, 'void'])->name('submissions.void');
        Route::get('audit', [AuditLogController::class, 'index'])->name('audit.index');
        Route::resource('users', UserController::class)->only(['index', 'create', 'store']);
        Route::put('users/{user}/password', [UserController::class, 'resetPassword'])->name('users.password');
    });

    Route::middleware('role:spg,superadmin')->prefix('tasks')->name('spg.tasks.')->group(function () {
        Route::get('/', [TaskController::class, 'index'])->name('index');
        Route::get('{assignment}', [TaskController::class, 'show'])->name('show');
        Route::post('{assignment}/submissions', [TaskController::class, 'store'])->name('store');
    });

    Route::middleware('role:spg')->prefix('submissions/{submission}')->name('spg.submissions.')->group(function () {
        Route::post('correction', [SubmissionCorrectionController::class, 'correct'])->name('correct');
        Route::post('cancellation', [SubmissionCorrectionController::class, 'cancel'])->name('cancel');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
