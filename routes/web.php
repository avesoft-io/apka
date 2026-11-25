<?php

use App\Http\Controllers\AuthenticatedSessionController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ClientInstructionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmailTemplateController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\OfficeActionAssignmentController;
use App\Http\Controllers\OfficeActionController;
use App\Http\Controllers\OfficeActionExportController;
use App\Http\Controllers\OfficeActionWorkflowController;
use App\Http\Controllers\RegisteredUserController;
use App\Http\Controllers\StaffController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('login.store');
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register/email', [RegisteredUserController::class, 'email'])
        ->middleware('throttle:30,1')
        ->name('register.email');
    Route::post('/register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('register.store');
});

Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'send'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::middleware('verified')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/office-actions', [OfficeActionController::class, 'index'])->name('office-actions.index');
        Route::get('/office-actions/export', [OfficeActionExportController::class, 'index'])->name('office-actions.export');
        Route::get('/office-actions/create', [OfficeActionController::class, 'create'])->name('office-actions.create');
        Route::post('/office-actions', [OfficeActionController::class, 'store'])->name('office-actions.store');
        Route::get('/office-actions/{officeAction}', [OfficeActionController::class, 'show'])->name('office-actions.show');
        Route::patch('/office-actions/{officeAction}', [OfficeActionController::class, 'update'])->name('office-actions.update');
        Route::patch('/office-actions/{officeAction}/assignment', [OfficeActionAssignmentController::class, 'update'])->name('office-actions.assignment.update');
        Route::patch('/office-actions/{officeAction}/report', [OfficeActionWorkflowController::class, 'report'])->name('office-actions.report.update');
        Route::patch('/office-actions/{officeAction}/instructions', [OfficeActionWorkflowController::class, 'instructions'])->name('office-actions.instructions.update');
        Route::post('/office-actions/{officeAction}/extension', [OfficeActionWorkflowController::class, 'requestExtension'])->name('office-actions.extension.store');
        Route::patch('/office-actions/{officeAction}/extension', [OfficeActionWorkflowController::class, 'confirmExtension'])->name('office-actions.extension.confirm');
        Route::delete('/office-actions/{officeAction}/extension', [OfficeActionWorkflowController::class, 'withdrawExtension'])->name('office-actions.extension.destroy');
        Route::patch('/office-actions/{officeAction}/draft', [OfficeActionWorkflowController::class, 'draft'])->name('office-actions.draft.update');
        Route::patch('/office-actions/{officeAction}/checking', [OfficeActionWorkflowController::class, 'checking'])->name('office-actions.checking.update');
        Route::patch('/office-actions/{officeAction}/approval', [OfficeActionWorkflowController::class, 'approval'])->name('office-actions.approval.update');
        Route::patch('/office-actions/{officeAction}/filing', [OfficeActionWorkflowController::class, 'filing'])->name('office-actions.filing.update');
        Route::patch('/office-actions/{officeAction}/filing-report', [OfficeActionWorkflowController::class, 'filingReport'])->name('office-actions.filing-report.update');
        Route::post('/office-actions/{officeAction}/abandonment', [OfficeActionWorkflowController::class, 'abandon'])->name('office-actions.abandonment.store');
        Route::patch('/office-actions/{officeAction}/reminder', [OfficeActionWorkflowController::class, 'reminder'])->name('office-actions.reminder.update');

        Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
        Route::post('/clients', [ClientController::class, 'store'])->name('clients.store');
        Route::get('/clients/{client}', [ClientController::class, 'show'])->name('clients.show');
        Route::patch('/clients/{client}', [ClientController::class, 'update'])->name('clients.update');
        Route::post('/clients/{client}/instructions', [ClientInstructionController::class, 'store'])->name('clients.instructions.store');
        Route::delete('/clients/{client}/instructions/{instruction}', [ClientInstructionController::class, 'destroy'])->name('clients.instructions.destroy');

        Route::get('/email-templates', [EmailTemplateController::class, 'index'])->name('email-templates.index');
        Route::patch('/email-templates/{emailTemplate}', [EmailTemplateController::class, 'update'])->name('email-templates.update');

        Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');
        Route::post('/staff', [StaffController::class, 'store'])->name('staff.store');
    });
});
