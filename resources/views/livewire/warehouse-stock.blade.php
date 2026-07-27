<div>

<div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('warehouses.index') }}" wire:navigate
           class="w-9 h-9 flex items-center justify-center rounded-full border border-[#E2E8F0] bg-white text-[#6B7280] hover:bg-[#F3F4F6]">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
        <div>
            <h1 class="text-xl font-bold text-[#1E293B]">مخزون: {{ $warehouse->name }}</h1>
            <p class="text-xs text-gray-400 mt-0.5">{{ $warehouse->type->label() }}@if($warehouse->driver) — {{ $warehouse->driver->full_name }}@endif</p>
        </div>
    </div>
</div>

<div class="card overflow-hidden">
    <table class="data-table">
        <thead><tr>
            <th>الصنف</th>
            <th>السعة</th>
            <th class="text-left" dir="ltr">الرصيد</th>
        </tr></thead>
        <tbody>
            @forelse($balances as $b)
            <tr>
                <td class="font-semibold">{{ $b->product?->name ?? '—' }}</td>
                <td class="text-gray-500 text-sm">{{ $b->product?->capacity_kg ? $b->product->capacity_kg.' كغ' : '—' }}</td>
                <td class="text-left font-mono text-sm" dir="ltr">{{ rtrim(rtrim(number_format((float) $b->quantity, 4), '0'), '.') }}</td>
            </tr>
            @empty
            <tr><td colspan="3">
                <div class="text-center py-16 text-gray-300"><p class="text-sm">لا يوجد مخزون في هذا المخزن بعد</p></div>
            </td></tr>
            @endforelse
        </tbody>
    </table>
</div>

</div>
