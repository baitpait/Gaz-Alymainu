<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name', 'غاز اليمني') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-[#F5F5F5] text-[#3D3D3D] font-sans">

    {{-- الشريط العلوي --}}
    <nav class="bg-white border-b border-[#E0E0E0] px-6 py-3 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <img src="{{ asset('branding/logo.png') }}" alt="{{ config('app.name') }}" class="h-9 w-auto" onerror="this.style.display='none'">
            <span class="text-lg font-bold text-[#3D3D3D]">{{ config('app.name', 'غاز اليمني') }}</span>
            <span class="text-xs text-[#C9A227] font-medium">توزيع الغاز</span>
        </div>
        <div class="flex items-center gap-4 text-sm">
            @auth
                <span class="text-[#3D3D3D]">{{ auth()->user()->full_name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-[#C9A227] hover:underline">خروج</button>
                </form>
            @endauth
        </div>
    </nav>

    {{-- القائمة الجانبية + المحتوى --}}
    <div class="flex">
        <aside class="w-56 min-h-[calc(100vh-57px)] bg-white border-l border-[#E0E0E0] p-4 hidden md:block">
            <nav class="space-y-1">
                <a href="{{ route('dashboard') }}"
                   class="flex items-center gap-2 px-3 py-2 rounded text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-[#C9A227] text-white' : 'text-[#3D3D3D] hover:bg-[#F5F5F5]' }}">
                    لوحة التحكم
                </a>
                <a href="{{ route('clients.index') }}"
                   class="flex items-center gap-2 px-3 py-2 rounded text-sm font-medium {{ request()->routeIs('clients.*') ? 'bg-[#C9A227] text-white' : 'text-[#3D3D3D] hover:bg-[#F5F5F5]' }}">
                    العملاء
                </a>
                <a href="{{ route('suppliers.index') }}"
                   class="flex items-center gap-2 px-3 py-2 rounded text-sm font-medium {{ request()->routeIs('suppliers.*') ? 'bg-[#C9A227] text-white' : 'text-[#3D3D3D] hover:bg-[#F5F5F5]' }}">
                    الموردون
                </a>
                <a href="{{ route('invoices.index') }}"
                   class="flex items-center gap-2 px-3 py-2 rounded text-sm font-medium {{ request()->routeIs('invoices.*') ? 'bg-[#C9A227] text-white' : 'text-[#3D3D3D] hover:bg-[#F5F5F5]' }}">
                    الفواتير
                </a>
                <a href="{{ route('expenses.index') }}"
                   class="flex items-center gap-2 px-3 py-2 rounded text-sm font-medium {{ request()->routeIs('expenses.*') ? 'bg-[#C9A227] text-white' : 'text-[#3D3D3D] hover:bg-[#F5F5F5]' }}">
                    المصروفات
                </a>
            </nav>
        </aside>

        <main class="flex-1 p-6">
            {{ $slot }}
        </main>
    </div>

    @livewireScripts
</body>
</html>
