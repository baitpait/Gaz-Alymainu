<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\DriverLocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Throwable;

/**
 * Business Purpose: يتجاوز فشل CSRF/كوكيز Laravel داخل Capacitor WebView (419)
 * عبر تسجيل دخول API ثم فتح جلسة ويب برابط لمرة واحدة (تنقّل كامل يحفظ الكوكي).
 */
class ApkWebSessionController extends Controller
{
    private const CACHE_PREFIX = 'apk_web_login:';

    private const TTL_SECONDS = 90;

    public function bootstrap(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ]);

        if (! Auth::attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
        ])) {
            return response()->json(['message' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة.'], 401);
        }

        /** @var User $user */
        $user = Auth::user();
        Auth::logout();

        if ($user === null || ! $user->is_active) {
            return response()->json(['message' => 'هذا الحساب معطّل. تواصل مع المدير.'], 403);
        }

        $driverToken = null;
        if ($user->isDriver()) {
            $user->tokens()->where('name', 'driver-device')->delete();
            $driverToken = $user->createToken('driver-device')->plainTextToken;

            try {
                app(DriverLocationService::class)->startSharing((int) $user->id);
            } catch (Throwable) {
                // لا نمنع الدخول
            }
        }

        $code = Str::random(64);
        Cache::put(self::CACHE_PREFIX.$code, [
            'user_id' => (int) $user->id,
            'remember' => (bool) ($credentials['remember'] ?? false),
        ], now()->addSeconds(self::TTL_SECONDS));

        return response()->json([
            'session_code' => $code,
            'session_url' => route('apk.session', ['code' => $code]),
            'token' => $driverToken,
            'token_type' => $driverToken !== null ? 'Bearer' : null,
            'user' => [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ]);
    }

    public function consume(Request $request, string $code): RedirectResponse
    {
        $payload = Cache::pull(self::CACHE_PREFIX.$code);

        if (! is_array($payload) || empty($payload['user_id'])) {
            return redirect()->route('login')->withErrors([
                'email' => 'انتهت صلاحية رابط الدخول من التطبيق. حاول مرة أخرى.',
            ]);
        }

        $user = User::query()->find((int) $payload['user_id']);

        if ($user === null || ! $user->is_active) {
            return redirect()->route('login')->withErrors([
                'email' => 'هذا الحساب غير متاح.',
            ]);
        }

        Auth::login($user, (bool) ($payload['remember'] ?? false));
        $request->session()->regenerate();

        if ($user->isDriver()) {
            try {
                app(DriverLocationService::class)->startSharing((int) $user->id);
            } catch (Throwable) {
                // لا نمنع الدخول
            }

            return redirect()->intended(route('pos.index'));
        }

        return redirect()->intended(route('dashboard'));
    }
}
