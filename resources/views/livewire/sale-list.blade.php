<div>

<div class="flex items-center justify-between mb-6 flex-wrap gap-3">
    <div>
        <h1 class="text-xl font-bold text-[#1E293B]">سجل المبيعات</h1>
        <p class="text-sm text-gray-400 mt-0.5">{{ $rows->total() }} عملية</p>
    </div>
</div>

<form wire:submit.prevent="applySaleFilters" class="card p-4 mb-5">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between lg:gap-6">
        <div class="flex min-w-0 flex-1 flex-col gap-3">
            <div class="min-w-0 w-full">
                <label class="label">بحث عام</label>
                <input type="search" wire:model="search" class="input w-full text-sm"
                       placeholder="بحث بالصنف، السيارة، السائق، أو الملاحظات..." autocomplete="off">
            </div>

            <div class="grid min-w-0 w-full grid-cols-1 gap-3 sm:grid-cols-2">
                <div class="min-w-0">
                    <label class="label">نوع الدفع</label>
                    <select wire:model="filterPaymentType" class="input w-full">
                        <option value="">الكل</option>
                        <option value="cash">نقدي</option>
                        <option value="credit">على الحساب</option>
                    </select>
                </div>
                <div class="min-w-0">
                    <label class="label">الصنف</label>
                    <select wire:model="filterProductId" class="input w-full">
                        <option value="">كل الأصناف</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid min-w-0 w-full grid-cols-1 gap-3 sm:grid-cols-2">
                <div class="min-w-0">
                    <label class="label">السيارة / المخزن</label>
                    <select wire:model="filterWarehouseId" class="input w-full">
                        <option value="">الكل</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                </div>
                @if($showDriverFilter)
                <div class="min-w-0">
                    <label class="label">السائق</label>
                    <select wire:model="filterDriverId" class="input w-full">
                        <option value="">كل السائقين</option>
                        @foreach($drivers as $driver)
                            <option value="{{ $driver->id }}">{{ $driver->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
            </div>

            <div class="grid min-w-0 grid-cols-1 gap-3 sm:grid-cols-2">
                <div class="min-w-0">
                    <label class="label">من تاريخ البيع</label>
                    <input wire:model="filterDateFrom" type="date" class="input w-full" dir="ltr">
                </div>
                <div class="min-w-0">
                    <label class="label">إلى تاريخ البيع</label>
                    <input wire:model="filterDateTo" type="date" class="input w-full" dir="ltr">
                </div>
            </div>
        </div>
        @include('livewire.partials.list-filter-actions', [
            'applyMethod' => 'applySaleFilters',
            'clearMethod' => 'clearSaleFilters',
            'showClear' => $this->hasActiveSaleFilters(),
        ])
    </div>
</form>

<div class="card overflow-hidden">
    <div wire:loading.delay class="h-0.5 bg-[#1B6CA8]/20 relative overflow-hidden"><div class="absolute inset-y-0 right-0 w-1/3 bg-[#1B6CA8] animate-pulse"></div></div>
    <div class="overflow-x-auto [-webkit-overflow-scrolling:touch]">
        <table class="data-table">
            <thead><tr>
                <th>الوقت</th>
                <th>الصنف</th>
                <th>السيارة / السائق</th>
                <th>النوع</th>
                <th class="text-left" dir="ltr">الكمية</th>
                <th class="text-left" dir="ltr">السعر</th>
                <th class="text-left" dir="ltr">الإجمالي</th>
            </tr></thead>
            <tbody>
                @forelse($rows as $s)
                <tr>
                    <td class="text-sm text-gray-500" dir="ltr">{{ $s->sold_at?->format('Y-m-d H:i') }}</td>
                    <td class="font-semibold text-sm">{{ $s->product?->name ?? '—' }}</td>
                    <td class="text-sm text-gray-500">{{ $s->warehouse?->name ?? '—' }}@if($s->driver) — {{ $s->driver->full_name }}@endif</td>
                    <td>
                        @if($s->payment_type->value === 'cash')
                            <span class="rounded px-2 py-0.5 bg-green-50 text-green-600 text-xs">{{ $s->payment_type->label() }}</span>
                        @else
                            <span class="rounded px-2 py-0.5 bg-[#1B6CA8]/10 text-[#1B6CA8] text-xs">{{ $s->payment_type->label() }}</span>
                        @endif
                    </td>
                    <td class="text-left font-mono text-sm" dir="ltr">{{ rtrim(rtrim(number_format((float) $s->quantity, 4), '0'), '.') }}</td>
                    <td class="text-left font-mono text-sm" dir="ltr">{{ number_format((float) $s->unit_price, 2) }}</td>
                    <td class="text-left font-mono text-sm font-semibold" dir="ltr">{{ number_format((float) $s->total_amount, 2) }}</td>
                </tr>
                @empty
                <tr><td colspan="7">
                    <div class="text-center py-16 text-gray-300"><p class="text-sm">لا توجد مبيعات</p></div>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <x-list-pagination :paginator="$rows" />
</div>

</div>
