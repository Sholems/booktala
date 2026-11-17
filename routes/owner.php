<?php

use App\Http\Controllers\Owner\PortalController;
use Illuminate\Support\Facades\Route;

// Property owners: read-only view of their own properties and statements.
Route::prefix('owner')->middleware(['auth', 'role:owner'])->group(function () {
    Route::get('/dashboard', [PortalController::class, 'dashboard'])->name('owner.dashboard');
    Route::get('/statements', [PortalController::class, 'statements'])->name('owner.statements.index');
    Route::get('/statements/{statement}', [PortalController::class, 'statement'])->name('owner.statements.show');
    Route::get('/statements/{statement}/pdf', [PortalController::class, 'statementPdf'])->name('owner.statements.pdf');
});
