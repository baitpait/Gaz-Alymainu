<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php $appName = config('app.name', 'غاز اليمني'); @endphp
    <title>تسجيل الدخول — {{ $appName }}</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    @include('partials.pwa-head')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen font-sans flex flex-col" dir="rtl" style="background: #0B1929;">

{{-- خلفية ديناميكية --}}
<div class="fixed inset-0 overflow-hidden pointer-events-none">
    {{-- دائرة زرقاء ضبابية يمين أعلى --}}
    <div style="position:absolute;top:-120px;right:-80px;width:500px;height:500px;
                background:radial-gradient(circle, rgba(27,108,168,0.22) 0%, transparent 70%);
                border-radius:50%;"></div>
    {{-- دائرة برتقالية يسار أسفل --}}
    <div style="position:absolute;bottom:-100px;left:-60px;width:350px;height:350px;
                background:radial-gradient(circle, rgba(232,93,4,0.12) 0%, transparent 70%);
                border-radius:50%;"></div>
    {{-- خطوط شبكة خفيفة --}}
    <div style="position:absolute;inset:0;
                background-image:linear-gradient(rgba(255,255,255,0.02) 1px,transparent 1px),
                                 linear-gradient(90deg,rgba(255,255,255,0.02) 1px,transparent 1px);
                background-size:48px 48px;"></div>
</div>

{{-- المحتوى المركزي --}}
<div class="relative flex-1 flex flex-col items-center justify-center px-4 py-12">

    {{-- البطاقة --}}
    <div style="width:100%;max-width:420px;">

        {{-- رأس الصفحة --}}
        <div class="text-center mb-8">
            <div class="flex items-center justify-center gap-3 mb-4">
                <img src="{{ asset('branding/logo.png') }}" alt="{{ $appName }}"
                     class="h-14 w-auto drop-shadow-lg"
                     onerror="this.style.display='none'">
            </div>
            <h1 class="text-2xl font-black text-white tracking-tight">{{ $appName }}</h1>
            <p class="text-sm mt-1" style="color:#E85D04;">{{ config('app.company_display_name', 'Gaz Al-Yamani') }}</p>
        </div>

        {{-- البطاقة البيضاء --}}
        <div style="background:rgba(255,255,255,0.04);
                    border:1px solid rgba(255,255,255,0.08);
                    border-radius:20px;
                    backdrop-filter:blur(16px);
                    padding:2rem;">

            <p class="text-center text-sm font-semibold mb-6" style="color:rgba(255,255,255,0.5);">
                تسجيل الدخول إلى لوحة التحكم
            </p>

            <form id="login-form" method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf
                <p id="apk-login-error" class="text-xs text-center hidden" style="color:#f87171;"></p>

                {{-- البريد --}}
                <div>
                    <label class="block text-xs font-bold mb-1.5 tracking-wide"
                           style="color:rgba(255,255,255,0.5);">البريد الإلكتروني</label>
                    <input type="email" name="email" value="{{ old('email') }}"
                           required autofocus autocomplete="email"
                           placeholder="example@domain.com"
                           dir="ltr"
                           style="width:100%;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);
                                  border-radius:10px;padding:0.65rem 0.875rem;font-size:0.875rem;color:#fff;
                                  outline:none;transition:border-color .2s,box-shadow .2s;
                                  {{ $errors->has('email') ? 'border-color:#ef4444;' : '' }}"
                           onfocus="this.style.borderColor='#1B6CA8';this.style.boxShadow='0 0 0 3px rgba(27,108,168,0.15)'"
                           onblur="this.style.borderColor='rgba(255,255,255,0.1)';this.style.boxShadow='none'">
                    @error('email')
                        <p class="text-xs mt-1.5" style="color:#f87171;">{{ $message }}</p>
                    @enderror
                </div>

                {{-- كلمة المرور --}}
                <div>
                    <label class="block text-xs font-bold mb-1.5 tracking-wide"
                           style="color:rgba(255,255,255,0.5);">كلمة المرور</label>
                    <input type="password" name="password"
                           required autocomplete="current-password"
                           placeholder="••••••••"
                           dir="ltr"
                           style="width:100%;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);
                                  border-radius:10px;padding:0.65rem 0.875rem;font-size:0.875rem;color:#fff;
                                  outline:none;transition:border-color .2s,box-shadow .2s;"
                           onfocus="this.style.borderColor='#1B6CA8';this.style.boxShadow='0 0 0 3px rgba(27,108,168,0.15)'"
                           onblur="this.style.borderColor='rgba(255,255,255,0.1)';this.style.boxShadow='none'">
                    @error('password')
                        <p class="text-xs mt-1.5" style="color:#f87171;">{{ $message }}</p>
                    @enderror
                </div>

                {{-- تذكّرني --}}
                <div class="flex items-center gap-2 pt-1">
                    <input type="checkbox" name="remember" id="remember"
                           style="width:15px;height:15px;accent-color:#1B6CA8;cursor:pointer;">
                    <label for="remember" class="text-sm cursor-pointer select-none"
                           style="color:rgba(255,255,255,0.45);">تذكّرني</label>
                </div>

                {{-- زر الدخول --}}
                <button type="submit"
                        style="width:100%;background:linear-gradient(135deg,#1B6CA8,#2580C4);
                               color:#fff;font-weight:800;font-size:0.95rem;letter-spacing:0.02em;
                               padding:0.75rem;border-radius:10px;border:none;cursor:pointer;
                               box-shadow:0 4px 20px rgba(27,108,168,0.35);
                               transition:opacity .2s,transform .1s;margin-top:0.5rem;"
                        onmouseover="this.style.opacity='0.9'"
                        onmouseout="this.style.opacity='1'"
                        onmousedown="this.style.transform='scale(0.98)'"
                        onmouseup="this.style.transform='scale(1)'">
                    دخول
                </button>
            </form>
        </div>

        {{-- تذييل --}}
        <p class="text-center text-xs mt-6" style="color:rgba(255,255,255,0.2);">
            © {{ date('Y') }} {{ config('app.company_display_name', 'Gaz Al-Yamani') }} — جميع الحقوق محفوظة
        </p>

        @include('partials.pwa-install-banner', ['variant' => 'login'])

    </div>
</div>

@include('components.layouts.footer', ['dark' => true])

{{-- Capacitor: تجنب 419 — API بدون CSRF ثم تنقّل كامل لفتح جلسة الويب --}}
<script>
(function () {
    function isNativeApk() {
        try {
            if (window.Capacitor && window.Capacitor.isNativePlatform && window.Capacitor.isNativePlatform()) {
                return true;
            }
        } catch (e) { /* ignore */ }
        // Android System WebView داخل APK غالباً يحمل "; wv)"
        var ua = navigator.userAgent || '';
        return /Android/i.test(ua) && /; wv\)/i.test(ua);
    }

    function bindNativeLogin() {
        if (!isNativeApk()) {
            return;
        }

        var form = document.getElementById('login-form');
        var errEl = document.getElementById('apk-login-error');
        if (!form || form.dataset.apkBound === '1') {
            return;
        }
        form.dataset.apkBound = '1';

        form.addEventListener('submit', async function (e) {
            e.preventDefault();

            var btn = form.querySelector('[type="submit"]');
            var email = form.email && form.email.value;
            var password = form.password && form.password.value;
            var remember = form.remember && form.remember.checked;

            if (errEl) {
                errEl.classList.add('hidden');
                errEl.textContent = '';
            }
            if (btn) {
                btn.disabled = true;
                btn.style.opacity = '0.7';
            }

            try {
                var res = await fetch('/api/apk/bootstrap-session', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ email: email, password: password, remember: Boolean(remember) }),
                });
                var data = await res.json().catch(function () { return {}; });

                if (!res.ok) {
                    var msg = (data && data.message) ? data.message : 'فشل تسجيل الدخول.';
                    if (errEl) {
                        errEl.textContent = msg;
                        errEl.classList.remove('hidden');
                    } else {
                        alert(msg);
                    }
                    if (btn) {
                        btn.disabled = false;
                        btn.style.opacity = '1';
                    }
                    return;
                }

                if (data.token && window.Capacitor && window.Capacitor.Plugins && window.Capacitor.Plugins.Preferences) {
                    try {
                        await window.Capacitor.Plugins.Preferences.set({ key: 'driver_api_token', value: data.token });
                        await window.Capacitor.Plugins.Preferences.set({
                            key: 'driver_api_base',
                            value: window.location.origin + '/api',
                        });
                    } catch (prefErr) { /* تجاهل */ }
                }

                window.location.href = data.session_url;
            } catch (netErr) {
                if (errEl) {
                    errEl.textContent = 'تعذّر الاتصال بالخادم. تحقق من الإنترنت.';
                    errEl.classList.remove('hidden');
                }
                if (btn) {
                    btn.disabled = false;
                    btn.style.opacity = '1';
                }
            }
        });
    }

    // Capacitor قد يُحقن بعد تحميل الصفحة قليلاً
    bindNativeLogin();
    var tries = 0;
    var timer = setInterval(function () {
        bindNativeLogin();
        if (++tries >= 40) {
            clearInterval(timer);
        }
    }, 50);
})();
</script>

</body>
</html>
