<x-layouts.app title="لوحة التحكم">
@php
    $supplierCount = \App\Models\Supplier::count();
@endphp

{{-- رأس الصفحة --}}
<div class="mb-7">
    <h1 class="text-2xl font-bold text-[#1E293B]">لوحة التحكم</h1>
    <p class="text-sm text-gray-400 mt-0.5">{{ now()->locale('ar')->isoFormat('dddd، D MMMM YYYY') }}</p>
</div>

{{-- بطاقات الإحصاء --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

    <a href="{{ route('suppliers.index') }}" class="card p-5 hover:shadow-md transition-shadow group">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-xl bg-purple-50 flex items-center justify-center text-purple-500 group-hover:bg-purple-100 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </div>
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-300 group-hover:text-[#1B6CA8] transition" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </div>
        <div class="text-3xl font-bold text-[#1E293B]">{{ number_format($supplierCount) }}</div>
        <div class="text-sm text-gray-400 mt-0.5">إجمالي الموردين</div>
    </a>


</div>

{{-- ═══ الملخص المالي (مخفي افتراضياً) — الصفحة الكاملة: «الصناديق النقدية» ═══ --}}
<div x-data="{ open: false }" class="mb-8">

    <div class="flex items-center justify-between mb-3">
        <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-widest">الملخص المالي</h2>
        <div class="flex items-center gap-2">
            <a href="{{ route('financial-summary') }}"
               class="text-xs font-semibold text-[#1B6CA8] hover:underline">صفحة الصناديق النقدية</a>
            <button @click="open = !open"
                    type="button"
                    class="flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-lg border border-[#E2E8F0] bg-white text-gray-500 hover:border-[#1B6CA8] hover:text-[#1B6CA8] transition select-none">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          x-show="!open" d="M15 12a3 3 0 11-6 0 3 3 0 016 0M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          x-show="open"  d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                </svg>
                <span x-text="open ? 'إخفاء الأرقام' : 'إظهار الأرقام'"></span>
            </button>
        </div>
    </div>

    <div x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         x-cloak>
        @include('partials.currency-boxes-full')
    </div>
</div>

</x-layouts.app>
