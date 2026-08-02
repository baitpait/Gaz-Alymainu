@php
    $variant = $variant ?? 'app';
@endphp
{{-- CTA تثبيت PWA — يظهر في المتصفح فقط (ليس Capacitor وليس وضع standalone) --}}
<div x-data="window.GazPwaInstall ? GazPwaInstall() : { dismissed: true, canInstall: false, showIosHint: false }"
     x-cloak
     x-show="!dismissed && (canInstall || showIosHint)"
     class="{{ $variant === 'login' ? '' : 'fixed bottom-20 md:bottom-4 inset-x-3 z-40 md:max-w-md md:left-auto md:right-3' }}">
    <div class="{{ $variant === 'login'
        ? 'mt-5 rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-white'
        : 'rounded-xl border border-[#E2E8F0] bg-white shadow-lg px-4 py-3 text-[#1E293B]' }}">
        <div class="flex items-start gap-3">
            <img src="{{ asset('pwa/icon-192.png') }}" alt="" class="h-10 w-10 rounded-lg shrink-0">
            <div class="min-w-0 flex-1">
                <p class="text-sm font-bold">ثبّت التطبيق على جهازك</p>
                <p class="text-xs mt-0.5 {{ $variant === 'login' ? 'text-white/55' : 'text-gray-500' }}">
                    وصول أسرع للوحة التحكم. تتبّع الموقع يعمل والمتصفح مفتوح
                    (التتبّع بالخلفية يحتاج تطبيق أندرويد).
                </p>

                <template x-if="canInstall">
                    <button type="button"
                            @click="install()"
                            class="mt-3 inline-flex items-center justify-center rounded-lg px-3 py-2 text-xs font-bold text-white"
                            style="background:#1B6CA8;">
                        تثبيت الآن
                    </button>
                </template>

                <template x-if="!canInstall && showIosHint">
                    <p class="mt-2 text-xs leading-relaxed {{ $variant === 'login' ? 'text-white/70' : 'text-gray-600' }}">
                        على آيفون: اضغط <span class="font-semibold">مشاركة</span>
                        ثم <span class="font-semibold">إضافة إلى الشاشة الرئيسية</span>.
                    </p>
                </template>
            </div>
            <button type="button"
                    @click="dismiss()"
                    class="shrink-0 text-lg leading-none {{ $variant === 'login' ? 'text-white/40' : 'text-gray-400' }}"
                    aria-label="إخفاء">×</button>
        </div>
    </div>
</div>
