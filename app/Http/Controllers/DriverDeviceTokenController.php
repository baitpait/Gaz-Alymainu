<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Business Purpose: يصدر token Sanctum للسائق بعد تسجيل الدخول عبر WebView (Capacitor)
 * ليستخدمه الـ native layer لبث الموقع بالخلفية.
 */
class DriverDeviceTokenController extends Controller
{
    public function store(): JsonResponse
    {
        Gate::authorize('share-location');

        $user = auth()->user();
        $user->tokens()->where('name', 'driver-device')->delete();
        $token = $user->createToken('driver-device')->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'api_base' => url('/api'),
        ]);
    }
}
