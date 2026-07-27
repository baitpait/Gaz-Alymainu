<div>

<div class="flex items-center justify-between mb-6 flex-wrap gap-3">
    <div>
        <h1 class="text-xl font-bold text-[#1E293B]">حركات المخزون</h1>
        <p class="text-sm text-gray-400 mt-0.5">{{ $rows->total() }} حركة</p>
    </div>
    @can('manage-inventory')
    <a href="{{ route('stock-movements.create') }}" wire:navigate class="btn btn-primary" style="text-decoration:none;">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        حركة جديدة
    </a>
    @endcan
</div>

<form wire:submit.prevent="applyStockMovementFilters" class="card p-4 mb-5">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between lg:gap-6">
        <div class="flex min-w-0 flex-1 flex-col gap-3">
            <div class="min-w-0 w-full">
                <label class="label">بحث عام</label>
                <input type="search" wire:model="search" class="input w-full text-sm"
                       placeholder="بحث بالصنف، المخزن، السائق، أو الملاحظات..." autocomplete="off">
            </div>

            <div class="grid min-w-0 w-full grid-cols-1 gap-3 sm:grid-cols-2">
                <div class="min-w-0">
                    <label class="label">نوع الحركة</label>
                    <select wire:model="filterType" class="input w-full">
                        <option value="">الكل</option>
                        @foreach($typeOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
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
                    <label class="label">المخزن / السيارة</label>
                    <select wire:model="filterWarehouseId" class="input w-full">
                        <option value="">الكل</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="min-w-0">
                    <label class="label">السائق</label>
                    <select wire:model="filterDriverId" class="input w-full">
                        <option value="">كل السائقين</option>
                        @foreach($drivers as $driver)
                            <option value="{{ $driver->id }}">{{ $driver->full_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid min-w-0 grid-cols-1 gap-3 sm:grid-cols-2">
                <div class="min-w-0">
                    <label class="label">من تاريخ الحركة</label>
                    <input wire:model="filterDateFrom" type="date" class="input w-full" dir="ltr">
                </div>
                <div class="min-w-0">
                    <label class="label">إلى تاريخ الحركة</label>
                    <input wire:model="filterDateTo" type="date" class="input w-full" dir="ltr">
                </div>
            </div>
        </div>
        @include('livewire.partials.list-filter-actions', [
            'applyMethod' => 'applyStockMovementFilters',
            'clearMethod' => 'clearStockMovementFilters',
            'showClear' => $this->hasActiveStockMovementFilters(),
        ])
    </div>
</form>

<div class="card overflow-hidden">
    <div wire:loading.delay class="h-0.5 bg-[#1B6CA8]/20 relative overflow-hidden"><div class="absolute inset-y-0 right-0 w-1/3 bg-[#1B6CA8] animate-pulse"></div></div>
    <table class="data-table">
        <thead><tr>
            <th>التاريخ</th>
            <th>النوع</th>
            <th>الصنف</th>
            <th>من</th>
            <th>إلى</th>
            <th class="text-left" dir="ltr">الكمية</th>
        </tr></thead>
        <tbody>
            @forelse($rows as $m)
            <tr>
                <td class="text-sm text-gray-500" dir="ltr">{{ $m->moved_at?->format('Y-m-d') }}</td>
                <td><span class="rounded px-2 py-0.5 bg-[#F7F8FA] border border-[#E2E8F0] text-xs">{{ $m->type->label() }}</span></td>
                <td class="font-semibold text-sm">{{ $m->product?->name ?? '—' }}</td>
                <td class="text-sm text-gray-500">{{ $m->fromWarehouse?->name ?? '—' }}</td>
                <td class="text-sm text-gray-500">{{ $m->toWarehouse?->name ?? '—' }}</td>
                <td class="text-left font-mono text-sm" dir="ltr">{{ rtrim(rtrim(number_format((float) $m->quantity, 4), '0'), '.') }}</td>
            </tr>
            @empty
            <tr><td colspan="6">
                <div class="text-center py-16 text-gray-300"><p class="text-sm">لا توجد حركات بعد</p></div>
            </td></tr>
            @endforelse
        </tbody>
    </table>

    <x-list-pagination :paginator="$rows" />
</div>

</div>
