<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\DriverLocationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class AuthenticatedSessionController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            if (! Auth::user()->is_active) {
                Auth::logout();
                $request->session()->invalidate();

                return back()->withErrors([
                    'email' => 'هذا الحساب معطّل. تواصل مع المدير.',
                ]);
            }
            $request->session()->regenerate();

            $user = Auth::user();
            if ($user && $user->isDriver()) {
                try {
                    app(DriverLocationService::class)->startSharing((int) $user->id);
                } catch (Throwable) {
                    // لا نمنع الدخول إن فشل تفعيل المشاركة
                }

                return redirect()->intended(route('pos.index'));
            }

            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'email' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة.',
        ]);
    }

    public function destroy(Request $request)
    {
        $user = Auth::user();
        if ($user && $user->isDriver()) {
            try {
                app(DriverLocationService::class)->stopSharing((int) $user->id);
            } catch (Throwable) {
                // تجاهل
            }

            $user->tokens()->where('name', 'driver-device')->delete();
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
