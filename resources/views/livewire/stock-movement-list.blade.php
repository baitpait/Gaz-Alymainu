<div>

<div class="flex items-center justify-between mb-6">
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

<div class="card overflow-hidden">
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
