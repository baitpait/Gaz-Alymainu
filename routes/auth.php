<?php

use App\Http\Controllers\Auth\ApkWebSessionController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);

    // Capacitor APK: one-time code → web session (avoids 419 CSRF in WebView)
    Route::get('/apk/session/{code}', [ApkWebSessionController::class, 'consume'])
        ->where('code', '[A-Za-z0-9]{64}')
        ->middleware('throttle:20,1')
        ->name('apk.session');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
