<div>

<div class="flex items-center justify-between mb-6 flex-wrap gap-3">
    <div>
        <h1 class="text-xl font-bold text-[#1E293B]">التسعير اليومي</h1>
        <p class="text-sm text-gray-400 mt-0.5">سعر بيع الجرة لكل صنف — بالشيكل (ILS)</p>
    </div>
    <div class="flex items-end gap-2">
        <div>
            <label class="label">التاريخ</label>
            <input wire:model.live="date" type="date" class="input" dir="ltr">
        </div>
        @can('manage-daily-prices')
        <button type="button" wire:click="save" wire:loading.attr="disabled" class="btn btn-primary">حفظ الأسعار</button>
        @endcan
    </div>
</div>
@error('date')<p class="field-error mb-3">{{ $message }}</p>@enderror

<div class="card overflow-hidden">
    <div class="overflow-x-auto [-webkit-overflow-scrolling:touch]">
        <table class="data-table">
            <thead><tr>
                <th>الصنف</th>
                <th>السعة</th>
                <th class="text-left w-48" dir="ltr">سعر البيع (ILS)</th>
            </tr></thead>
            <tbody>
                @forelse($products as $p)
                <tr wire:key="price-{{ $p->id }}">
                    <td class="font-semibold">{{ $p->name }}</td>
                    <td class="text-gray-500 text-sm">{{ $p->capacity_kg ? $p->capacity_kg.' كغ' : '—' }}</td>
                    <td>
                        <input type="number" step="0.0001" min="0" dir="ltr" class="input font-mono text-sm py-2"
                               wire:model="prices.{{ $p->id }}" placeholder="—" @cannot('manage-daily-prices') disabled @endcannot>
                        @error("prices.{$p->id}")<p class="field-error text-xs">{{ $message }}</p>@enderror
                    </td>
                </tr>
                @empty
                <tr><td colspan="3">
                    <div class="text-center py-16 text-gray-300">
                        <p class="text-sm">لا توجد أصناف غاز — فعّل «تتبّع المخزون» على الصنف من قسم الأصناف.</p>
                    </div>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

</div>
