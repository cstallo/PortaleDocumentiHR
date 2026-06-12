<?php

use App\Http\Controllers\DocumentoController;
use App\Http\Controllers\PasswordChangeController;
use App\Http\Middleware\ForcePasswordChange;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});
Route::middleware(['auth'])->group(function () {
    Route::get('/cambio-password', [PasswordChangeController::class, 'show'])
        ->name('password.change');
    Route::post('/cambio-password', [PasswordChangeController::class, 'update'])
        ->name('password.change.update');
});

Route::middleware(['auth', 'verified', ForcePasswordChange::class])->group(function () {
    Route::get('/dashboard', fn () => redirect('/admin'))->name('dashboard');

    Route::get('/documenti', [DocumentoController::class, 'index'])
        ->name('documenti.index');
    Route::get('/documenti/{documento}/download', [DocumentoController::class, 'download'])
        ->name('documenti.download');
    Route::get('/documenti/{documento}/inline', [DocumentoController::class, 'inline'])
        ->name('documenti.inline');

    Route::post('/notifiche/{id}/letta', function ($id) {
        auth()->user()->notifications()->findOrFail($id)->markAsRead();

        return back();
    })->name('notifiche.letta');

    Route::post('/notifiche/tutte-lette', function () {
        auth()->user()->unreadNotifications->markAsRead();

        return back();
    })->name('notifiche.tutte-lette');

});
