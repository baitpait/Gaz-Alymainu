<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php $appName = config('app.name', 'غاز اليمني'); @endphp
    <title>{{ ($title ?? '') ? $title . ' — ' . $appName : $appName }}</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    @include('partials.pwa-head')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    @livewireScriptConfig
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen font-sans antialiased flex flex-col"
      @auth
      @if(auth()->user()->isDriver())
      data-driver-native-bridge="1"
      data-driver-bg-title="{{ config('gaz_driver.background_notification_title') }}"
      data-driver-bg-text="{{ config('gaz_driver.background_notification_text') }}"
      @else
      x-data="{ navOpen: false }"
      x-on:keydown.escape.window="navOpen = false"
      x-on:livewire:navigated.window="navOpen = false"
      @endif
      @endauth>

{{-- ═══ شريط التنقل العلوي ═══ --}}
<header class="bg-white border-b border-[#E2E8F0] h-14 flex items-center px-5 sticky top-0 z-30 shadow-sm">
    <div class="flex items-center gap-3 flex-1 min-w-0">
        @auth
        @unless(auth()->user()->isDriver())
        <button type="button"
                class="md:hidden w-9 h-9 flex items-center justify-center rounded-lg text-[#1E293B] hover:bg-gray-100 transition shrink-0"
                @click="navOpen = true"
                aria-label="فتح القائمة"
                :aria-expanded="navOpen.toString()">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>
        @endunless
        @endauth
        <img src="{{ asset('branding/logo.png') }}" alt="{{ $appName }}" class="h-8 w-auto" onerror="this.style.display='none'">
        <div class="flex flex-col leading-tight">
            <span class="text-sm font-bold text-[#1E293B]">{{ $appName }}</span>
            <span class="text-[10px] text-[#E85D04] font-medium tracking-wide">توزيع الغاز</span>
        </div>
    </div>
    @auth
    <div class="relative shrink-0" x-data="{ open: false }">
        <button type="button"
                @click="open = !open"
                class="flex items-center gap-2.5 rounded-lg px-1.5 py-1 hover:bg-gray-50 transition"
                aria-haspopup="menu"
                :aria-expanded="open.toString()">
            <div class="hidden sm:block text-left">
                <div class="text-xs font-semibold text-[#1E293B]">{{ auth()->user()->full_name }}</div>
                <div class="text-[10px] text-gray-400">
                    {{ match(auth()->user()->role) { 'manager' => 'مدير', 'accountant' => 'محاسب', 'driver' => 'سائق', default => 'مشاهد' } }}
                </div>
            </div>
            <div class="w-8 h-8 rounded-full bg-[#1B6CA8]/15 flex items-center justify-center text-[#1B6CA8] font-bold text-sm shrink-0">
                {{ mb_substr(auth()->user()->full_name, 0, 1) }}
            </div>
            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-gray-400 shrink-0 transition"
                 :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        <div x-show="open"
             x-cloak
             @click.outside="open = false"
             x-transition:enter="transition ease-out duration-100"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-75"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="absolute left-0 top-full mt-2 w-52 rounded-xl border border-[#E2E8F0] bg-white shadow-lg py-1.5 z-50 origin-top-left"
             role="menu">
            <div class="px-3 py-2 border-b border-[#E2E8F0] sm:hidden">
                <div class="text-xs font-semibold text-[#1E293B]">{{ auth()->user()->full_name }}</div>
                <div class="text-[10px] text-gray-400">
                    {{ match(auth()->user()->role) { 'manager' => 'مدير', 'accountant' => 'محاسب', 'driver' => 'سائق', default => 'مشاهد' } }}
                </div>
            </div>
            <a href="{{ route('profile') }}" wire:navigate
               @click="open = false"
               role="menuitem"
               class="flex items-center gap-2.5 px-3 py-2.5 text-sm text-[#1E293B] hover:bg-gray-50 transition"
               style="text-decoration:none;">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                الملف الشخصي
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" role="menuitem"
                        class="w-full flex items-center gap-2.5 px-3 py-2.5 text-sm text-red-600 hover:bg-red-50 transition text-right">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    تسجيل الخروج
                </button>
            </form>
        </div>
    </div>
    @endauth
</header>

@auth
@unless(auth()->user()->isDriver())
{{-- درج القائمة للموبايل/APK — نفس روابط الشريط الجانبي --}}
<div x-show="navOpen"
     x-cloak
     class="fixed inset-0 z-[60] md:hidden"
     role="dialog"
     aria-modal="true"
     aria-label="قائمة التنقل">
    <div class="absolute inset-0 bg-black/40"
         x-show="navOpen"
         x-transition.opacity
         @click="navOpen = false"></div>
    <aside class="absolute top-0 bottom-0 right-0 w-72 max-w-[85vw] bg-white shadow-2xl flex flex-col"
           x-show="navOpen"
           x-transition:enter="transition ease-out duration-200"
           x-transition:enter-start="translate-x-full"
           x-transition:enter-end="translate-x-0"
           x-transition:leave="transition ease-in duration-150"
           x-transition:leave-start="translate-x-0"
           x-transition:leave-end="translate-x-full">
        <div class="h-14 px-4 border-b border-[#E2E8F0] flex items-center justify-between shrink-0">
            <span class="text-sm font-bold text-[#1E293B]">القائمة</span>
            <button type="button"
                    class="w-9 h-9 flex items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 transition"
                    @click="navOpen = false"
                    aria-label="إغلاق القائمة">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <nav class="flex-1 px-3 py-2 space-y-0.5 overflow-y-auto" @click="if ($event.target.closest('a')) navOpen = false">
            @include('components.layouts.partials.sidebar-nav')
        </nav>
        <div class="px-3 pt-1 pb-3 border-t border-[#E2E8F0] shrink-0">
            <x-support-whatsapp-link variant="sidebar" />
        </div>
    </aside>
</div>
@endunless
@endauth

{{-- ═══ الهيكل الرئيسي ═══ --}}
<div class="flex flex-1 min-h-0">

    {{-- ═══ القائمة الجانبية (مخفية للسائق) ═══ --}}
    @unless(auth()->user()->isDriver())
    <aside class="w-56 bg-white border-l border-[#E2E8F0] hidden md:flex flex-col py-3 shrink-0">
        <nav class="flex-1 px-3 space-y-0.5 overflow-y-auto">
            @include('components.layouts.partials.sidebar-nav')
        </nav>

        <div class="px-3 pt-1 pb-2 shrink-0">
            <x-support-whatsapp-link variant="sidebar" />
        </div>
    </aside>
    @endunless

    {{-- ═══ المحتوى الرئيسي ═══ --}}
    <main class="flex-1 p-6 min-w-0 @can('record-sales') pb-24 md:pb-6 @endcan">
        {{ $slot }}
    </main>
</div>

@can('share-location')
{{-- مشاركة موقع السائق تلقائية على كل الصفحات (بدون أزرار إيقاف) --}}
<livewire:driver-location-beacon />
@endcan

@can('record-sales')
{{-- ═══ شريط التنقل السفلي — يظهر على الموبايل فقط ═══ --}}
<nav class="fixed bottom-0 inset-x-0 z-40 bg-white border-t border-[#E2E8F0] shadow-[0_-2px_10px_rgba(0,0,0,0.05)] flex items-stretch md:hidden">
    <a href="{{ route('pos.index') }}" wire:navigate
       class="flex-1 flex flex-col items-center justify-center gap-1 py-2.5 transition {{ request()->routeIs('pos.*') ? 'text-[#1B6CA8]' : 'text-gray-400' }}">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
        </svg>
        <span class="text-[11px] font-bold">المبيعات</span>
    </a>

    <a href="{{ route('collections.index') }}" wire:navigate
       class="flex-1 flex flex-col items-center justify-center gap-1 py-2.5 transition {{ request()->routeIs('collections.*') ? 'text-[#1B6CA8]' : 'text-gray-400' }}">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
        </svg>
        <span class="text-[11px] font-bold">التحصيل</span>
    </a>

    @can('manage-driver-expenses')
    <a href="{{ route('driver-expenses.index') }}" wire:navigate
       class="flex-1 flex flex-col items-center justify-center gap-1 py-2.5 transition {{ request()->routeIs('driver-expenses.*') ? 'text-[#1B6CA8]' : 'text-gray-400' }}">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17 14l-5-5-5 5m10 0H7"/>
        </svg>
        <span class="text-[11px] font-bold">المصروفات</span>
    </a>
    @endcan
</nav>
@endcan

@include('components.layouts.footer')

@include('partials.pwa-install-banner', ['variant' => 'app'])

{{-- ═══ نظام الإشعارات (Toast) ═══ --}}
<div x-data="toastManager"
     @toast.window="add($event.detail.message, $event.detail.type ?? 'success')"
     class="fixed bottom-5 left-5 z-[200] flex flex-col gap-2 w-80">
    <template x-for="toast in toasts" :key="toast.id">
        <div x-show="toast.show"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 translate-y-2"
             :class="toast.type === 'success' ? 'border-r-4 border-green-500' :
                     toast.type === 'error'   ? 'border-r-4 border-red-500'   :
                                                'border-r-4 border-[#1B6CA8]'"
             class="card px-4 py-3 flex items-center gap-3 shadow-xl">
            <span x-show="toast.type === 'success'" class="text-green-500 shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </span>
            <span x-show="toast.type === 'error'" class="text-red-500 shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </span>
            <span class="text-sm text-[#1E293B] flex-1" x-text="toast.message"></span>
            <button @click="remove(toast.id)" class="text-gray-300 hover:text-gray-500 transition shrink-0 text-lg leading-none">&times;</button>
        </div>
    </template>
</div>

@if(session('toast'))
<script>
    document.addEventListener('DOMContentLoaded', () => {
        window.dispatchEvent(new CustomEvent('toast', { detail: { message: @json(session('toast')), type: 'success' } }));
    });
</script>
@endif

</body>
</html>
