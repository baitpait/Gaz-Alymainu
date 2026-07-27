<div>

<div class="flex items-center justify-between mb-6 flex-wrap gap-3">
    <div>
        <h1 class="text-xl font-bold text-[#1E293B]">سجل المبيعات</h1>
        <p class="text-sm text-gray-400 mt-0.5">{{ $rows->total() }} عملية</p>
    </div>
    <div class="flex items-end gap-2">
        <div>
            <label class="label">التاريخ</label>
            <input wire:model.live="date" type="date" class="input" dir="ltr">
        </div>
    </div>
</div>

<div class="card overflow-hidden">
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

    <x-list-pagination :paginator="$rows" />
</div>

</div>
