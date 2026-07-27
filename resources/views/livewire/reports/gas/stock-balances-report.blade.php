<div>
    <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-[#3D3D3D]">أرصدة المخزون</h1>
            <p class="text-sm text-gray-500 mt-1">الكمية الحالية لكل صنف في كل مخزن/سيارة (لقطة لحظية).</p>
        </div>
        @can('export-period-reports')
        <button type="button" wire:click="exportCsv" class="btn btn-secondary">تصدير CSV</button>
        @endcan
    </div>

    <div class="card p-4 mb-4 flex flex-wrap gap-4 items-end">
        <div>
            <label class="label">المخزن</label>
            <select wire:model.live="warehouseId" class="input min-w-48">
                <option value="">الكل</option>
                @foreach($warehouses as $w)<option value="{{ $w->id }}">{{ $w->name }}</option>@endforeach
            </select>
        </div>
        <label class="flex items-center gap-2 text-sm text-gray-600 pb-2">
            <input type="checkbox" wire:model.live="hideZero" class="rounded"> إخفاء الأصناف صفرية الرصيد
        </label>
        <div class="card px-4 py-2 text-sm mr-auto"><span class="text-gray-500">إجمالي الكمية:</span> <span class="font-mono font-bold text-[#3D3D3D] mr-2" dir="ltr">{{ rtrim(rtrim(number_format($grandQty, 2), '0'), '.') }}</span></div>
    </div>

    @if($grouped->isEmpty())
    <div class="text-center py-16 text-gray-400">لا توجد أرصدة.</div>
    @else
    @foreach($grouped as $warehouseName => $items)
    <div class="card overflow-hidden mb-4">
        <div class="px-5 py-3 border-b border-[#E2E4E9] bg-[#F9F9FB] flex items-center justify-between">
            <p class="text-sm font-bold text-[#3D3D3D]">{{ $warehouseName }}</p>
            <span class="text-xs text-gray-400">إجمالي: <span class="font-mono" dir="ltr">{{ rtrim(rtrim(number_format($items->sum('quantity'), 2), '0'), '.') }}</span></span>
        </div>
        <table class="data-table">
            <thead><tr>
                <th>الصنف</th>
                <th class="text-left w-40" dir="ltr">الكمية</th>
            </tr></thead>
            <tbody>
                @foreach($items as $b)
                <tr>
                    <td class="font-semibold text-sm">{{ $b->product->name }}</td>
                    <td class="font-mono text-left text-base font-bold {{ $b->quantity > 0 ? 'text-[#16A34A]' : 'text-red-500' }}" dir="ltr">{{ rtrim(rtrim(number_format((float) $b->quantity, 2), '0'), '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endforeach
    @endif
</div>
