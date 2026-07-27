<x-layouts.app title="لوحة التحكم">
@php
    $dash = app(\App\Services\DashboardSummaryService::class)->forDate();
    $today = $dash['today'];
    $fleet = $dash['fleet'];
    $finance = $dash['finance'];
    $fmt = fn (float|int $n, int $dec = 2) => number_format((float) $n, $dec);
@endphp

{{-- رأس الصفحة --}}
<div class="mb-6 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="text-2xl font-bold text-[#1E293B]">لوحة التحكم</h1>
        <p class="text-sm text-gray-400 mt-0.5">{{ now()->locale('ar')->isoFormat('dddd، D MMMM YYYY') }}</p>
    </div>
    <p class="text-xs text-gray-400" dir="ltr">{{ $dash['date'] }}</p>
</div>

{{-- ═══ صف اليوم ═══ --}}
<section class="mb-7">
    <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-widest mb-3">اليوم — توزيع الغاز</h2>
    <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-3">
        <a href="{{ route('sales.index') }}" wire:navigate class="card p-4 hover:shadow-md transition block" style="text-decoration:none;">
            <div class="text-xs text-gray-400 mb-1">مبيعات اليوم</div>
            <div class="text-2xl font-bold text-[#1E293B]" dir="ltr">{{ $fmt($today['sales_total']) }}</div>
            <div class="text-[11px] text-gray-400 mt-1" dir="ltr">{{ $fmt($today['sales_qty'], 1) }} وحدة · {{ $today['sales_count'] }} عملية</div>
        </a>
        <a href="{{ route('sales.index') }}" wire:navigate class="card p-4 hover:shadow-md transition block" style="text-decoration:none;">
            <div class="text-xs text-gray-400 mb-1">نقدي / على الحساب</div>
            <div class="text-lg font-bold text-green-600" dir="ltr">{{ $fmt($today['sales_cash']) }}</div>
            <div class="text-sm font-semibold text-[#1B6CA8] mt-0.5" dir="ltr">{{ $fmt($today['sales_credit']) }}</div>
        </a>
        <a href="{{ route('collections.index') }}" wire:navigate class="card p-4 hover:shadow-md transition block" style="text-decoration:none;">
            <div class="text-xs text-gray-400 mb-1">تحصيلات اليوم</div>
            <div class="text-2xl font-bold text-[#1E293B]" dir="ltr">{{ $fmt($today['collections_total']) }}</div>
            <div class="text-[11px] text-gray-400 mt-1">نقدي {{ $fmt($today['collections_cash']) }} · شيك {{ $fmt($today['collections_cheque']) }}</div>
        </a>
        <a href="{{ route('driver-expenses.index') }}" wire:navigate class="card p-4 hover:shadow-md transition block" style="text-decoration:none;">
            <div class="text-xs text-gray-400 mb-1">مصروفات السائقين</div>
            <div class="text-2xl font-bold text-amber-600" dir="ltr">{{ $fmt($today['driver_expenses']) }}</div>
            <div class="text-[11px] text-gray-400 mt-1">اليوم</div>
        </a>
        <a href="{{ route('cash-handovers.index') }}" wire:navigate class="card p-4 hover:shadow-md transition block border-r-4 border-[#E8590C]" style="text-decoration:none;">
            <div class="text-xs text-gray-400 mb-1">كاش لدى السائقين</div>
            <div class="text-2xl font-bold text-[#E8590C]" dir="ltr">{{ $fmt($today['drivers_cash_held']) }}</div>
            <div class="text-[11px] text-gray-400 mt-1">شيكات {{ $fmt($today['drivers_cheque_held']) }} · لم يُسلَّم</div>
        </a>
    </div>
</section>

{{-- ═══ المخزون والأسطول ═══ --}}
<section class="mb-7">
    <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-widest mb-3">المخزون والأسطول</h2>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <a href="{{ route('reports.gas-stock-balances') }}" wire:navigate class="card p-4 hover:shadow-md transition block" style="text-decoration:none;">
            <div class="text-xs text-gray-400 mb-1">أرصدة صفر (سيارات)</div>
            <div class="text-2xl font-bold {{ $fleet['zero_vehicle_stock'] > 0 ? 'text-red-600' : 'text-[#1E293B]' }}">{{ $fleet['zero_vehicle_stock'] }}</div>
            <div class="text-[11px] text-gray-400 mt-1">منخفض ≤2: {{ $fleet['low_vehicle_stock'] }}</div>
        </a>
        <a href="{{ route('stock-movements.index') }}" wire:navigate class="card p-4 hover:shadow-md transition block" style="text-decoration:none;">
            <div class="text-xs text-gray-400 mb-1">تحميل / إرجاع اليوم</div>
            <div class="text-2xl font-bold text-[#1E293B]">{{ $fleet['loads_today'] }} <span class="text-gray-300 font-normal">/</span> {{ $fleet['returns_today'] }}</div>
            <div class="text-[11px] text-gray-400 mt-1">حركات مخزون</div>
        </a>
        <a href="{{ route('daily-prices.index') }}" wire:navigate class="card p-4 hover:shadow-md transition block" style="text-decoration:none;">
            <div class="text-xs text-gray-400 mb-1">التسعير اليومي</div>
            @if($fleet['pricing_complete'])
                <div class="text-2xl font-bold text-green-600">مكتمل</div>
            @else
                <div class="text-2xl font-bold text-amber-600">ناقص</div>
            @endif
            <div class="text-[11px] text-gray-400 mt-1" dir="ltr">{{ $fleet['priced_today'] }} / {{ $fleet['tracked_products'] }}</div>
        </a>
        @can('view-driver-locations')
        <a href="{{ route('drivers.map') }}" wire:navigate class="card p-4 hover:shadow-md transition block" style="text-decoration:none;">
            <div class="text-xs text-gray-400 mb-1">سائقون على الخريطة</div>
            <div class="text-2xl font-bold text-[#1B6CA8]">{{ $fleet['sharing_drivers'] }}</div>
            <div class="text-[11px] text-gray-400 mt-1">من أصل {{ $fleet['active_drivers'] }} نشط</div>
        </a>
        @else
        <div class="card p-4">
            <div class="text-xs text-gray-400 mb-1">سائقون نشطون</div>
            <div class="text-2xl font-bold text-[#1E293B]">{{ $fleet['active_drivers'] }}</div>
            <div class="text-[11px] text-gray-400 mt-1">حسابات سائق فعّالة</div>
        </div>
        @endcan
    </div>
</section>

{{-- ═══ ذمم وصناديق مختصرة ═══ --}}
<section class="mb-7">
    <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-widest mb-3">ملخص مالي مختصر ({{ $finance['currency'] }})</h2>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <a href="{{ route('reports.client-receivables-aging') }}" wire:navigate class="card p-4 hover:shadow-md transition block" style="text-decoration:none;">
            <div class="text-xs text-gray-400 mb-1">ذمم عملاء تقريبية</div>
            <div class="text-xl font-bold text-[#1E293B]" dir="ltr">{{ $fmt($finance['client_receivable']) }}</div>
            <div class="text-[11px] text-gray-400 mt-1">فواتير − دفعات (بدون تسويات)</div>
        </a>
        <a href="{{ route('reports.supplier-receivables-aging') }}" wire:navigate class="card p-4 hover:shadow-md transition block" style="text-decoration:none;">
            <div class="text-xs text-gray-400 mb-1">التزامات موردين</div>
            <div class="text-xl font-bold text-[#1E293B]" dir="ltr">{{ $fmt($finance['supplier_payable']) }}</div>
            <div class="text-[11px] text-gray-400 mt-1">مشتريات − دفعات</div>
        </a>
        <a href="{{ route('financial-summary') }}" wire:navigate class="card p-4 hover:shadow-md transition block" style="text-decoration:none;">
            <div class="text-xs text-gray-400 mb-1">الصندوق الرئيسي</div>
            <div class="text-xl font-bold text-green-700" dir="ltr">{{ $fmt($finance['main_cash']) }}</div>
            <div class="text-[11px] text-gray-400 mt-1">شيكات مُسلّمة {{ $fmt($finance['main_cheque']) }}</div>
        </a>
        <div class="card p-4">
            <div class="text-xs text-gray-400 mb-1">مسودات معلّقة</div>
            <div class="text-xl font-bold text-[#1E293B]">
                <a href="{{ route('invoices.index') }}" wire:navigate class="hover:text-[#1B6CA8]" style="text-decoration:none;">{{ $finance['draft_invoices'] }}</a>
                <span class="text-gray-300 font-normal">/</span>
                <a href="{{ route('purchase-orders.index') }}" wire:navigate class="hover:text-[#1B6CA8]" style="text-decoration:none;">{{ $finance['draft_purchase_orders'] }}</a>
            </div>
            <div class="text-[11px] text-gray-400 mt-1">فواتير / مشتريات</div>
        </div>
    </div>
</section>

{{-- ═══ تفاصيل الصناديق (مطوي) ═══ --}}
<div x-data="{ open: false }" class="mb-8">
    <div class="flex items-center justify-between mb-3">
        <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-widest">تفاصيل الصناديق النقدية</h2>
        <div class="flex items-center gap-2">
            <a href="{{ route('financial-summary') }}"
               class="text-xs font-semibold text-[#1B6CA8] hover:underline">صفحة كاملة</a>
            <button @click="open = !open"
                    type="button"
                    class="flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-lg border border-[#E2E8F0] bg-white text-gray-500 hover:border-[#1B6CA8] hover:text-[#1B6CA8] transition select-none">
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
