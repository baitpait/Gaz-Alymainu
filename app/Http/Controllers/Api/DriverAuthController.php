<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DriverLocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

/**
 * Business Purpose: مصادقة تطبيق السائق (APK) عبر Sanctum token للموقع بالخلفية.
 */
class DriverAuthController extends Controller
{
    public function login(Request $request, DriverLocationService $locations): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials)) {
            return response()->json(['message' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة.'], 401);
        }

        $user = Auth::user();
        Auth::logout();

        if ($user === null || ! $user->isDriver() || ! $user->is_active) {
            return response()->json(['message' => 'هذا الحساب ليس سائقاً نشطاً.'], 403);
        }

        $user->tokens()->where('name', 'driver-device')->delete();
        $token = $user->createToken('driver-device')->plainTextToken;

        try {
            $locations->startSharing((int) $user->id);
        } catch (Throwable) {
            // لا نمنع الدخول
        }

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'email' => $user->email,
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'id' => $user->id,
            'full_name' => $user->full_name,
            'email' => $user->email,
        ]);
    }

    public function logout(Request $request, DriverLocationService $locations): JsonResponse
    {
        $user = $request->user();

        if ($user !== null) {
            try {
                $locations->stopSharing((int) $user->id);
            } catch (Throwable) {
                // تجاهل
            }

            $user->currentAccessToken()?->delete();
        }

        return response()->json(['message' => 'تم تسجيل الخروج.']);
    }
}
