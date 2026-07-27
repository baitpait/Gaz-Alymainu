<?php

use App\Http\Controllers\Api\DriverAuthController;
use App\Http\Controllers\Api\DriverLocationController;
use App\Http\Controllers\Auth\ApkWebSessionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Driver mobile API (Capacitor APK — background GPS)
|--------------------------------------------------------------------------
*/

Route::post('/apk/bootstrap-session', [ApkWebSessionController::class, 'bootstrap'])
    ->middleware('throttle:10,1');

Route::prefix('driver')->group(function (): void {
    Route::post('/login', [DriverAuthController::class, 'login'])
        ->middleware('throttle:10,1');

    Route::middleware(['auth:sanctum', 'driver.api'])->group(function (): void {
        Route::get('/me', [DriverAuthController::class, 'me']);
        Route::post('/location', [DriverLocationController::class, 'update'])
            ->middleware('throttle:120,1');
        Route::post('/logout', [DriverAuthController::class, 'logout']);
    });
});
