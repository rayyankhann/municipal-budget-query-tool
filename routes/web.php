<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\BudgetAlertController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QueryController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/query', [QueryController::class, 'index'])->name('query');
    Route::post('/query', [QueryController::class, 'query'])->name('query.run')->middleware('throttle:15,1');
    Route::get('/query/suggestions', [QueryController::class, 'suggestions'])->name('query.suggestions');
    Route::post('/query/export', [QueryController::class, 'export'])->name('query.export');
    Route::get('/query/history', [QueryController::class, 'history'])->name('query.history');

    Route::get('/alerts', [BudgetAlertController::class, 'index'])->name('alerts');
    Route::get('/audit-log', [AuditLogController::class, 'index'])->name('audit-log');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
