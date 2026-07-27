<div x-data="{ deletingId: null }">

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-bold text-[#1E293B]">تسويات العملاء</h1>
        <p class="text-sm text-gray-400 mt-0.5">خصم أو إعفاء على الذمة دون تعديل الفواتير — {{ $rows->total() }} تسوية</p>
    </div>
</div>

<div class="card p-4 mb-5">
    <p class="text-sm font-semibold text-[#1E293B] mb-3">تسوية جديدة</p>
    <div class="flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[200px]">
            @include('livewire.partials.client-select-with-search', ['clients' => $clients, 'placeholder' => '— اختر العميل —'])
        </div>
        <button type="button" wire:click="goCreate" class="btn btn-primary" style="white-space:nowrap;">
            + تسوية على الذمة
        </button>
    </div>
</div>

@include('livewire.partials.list-search-form', [
    'searchPlaceholder' => 'بحث بالعميل أو السبب...',
    'hasActive' => $this->hasActiveListFilters(),
])

<div class="card overflow-hidden">
    <table class="data-table">
        <thead>
            <tr>
                <th>التاريخ</th>
                <th>العميل</th>
                <th>النوع</th>
                <th>السبب</th>
                <th>المبلغ</th>
                <th class="w-32"></th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $adj)
            <tr>
                <td class="text-gray-500 font-mono text-xs" dir="ltr">{{ $adj->adjustment_date?->format('Y-m-d') }}</td>
                <td class="font-semibold">{{ $adj->client?->displayName() ?? '—' }}</td>
                <td><span class="badge" style="background:#EDE9FE;color:#6D28D9;">{{ $adj->typeLabel() }}</span></td>
                <td class="text-sm text-gray-600">{{ $adj->reason ?? '—' }}</td>
                <td class="font-mono font-semibold text-[#7C3AED]" dir="ltr">−{{ number_format((float) $adj->amount, 2) }} {{ $adj->currency_code }}</td>
                <td>
                    <div class="flex items-center gap-1 justify-end">
                        <a href="{{ route('clients.statement', $adj->client_id) }}" wire:navigate class="btn btn-ghost py-1 px-2 text-xs text-[#1B6CA8]" style="text-decoration:none;">كشف</a>
                        <a href="{{ route('clients.adjustments.edit', [$adj->client_id, $adj]) }}" wire:navigate class="btn btn-ghost py-1 px-2 text-xs text-blue-600" style="text-decoration:none;">تعديل</a>
                        @if(auth()->user()->isManager())
                        <form method="POST" action="{{ route('clients.adjustments.destroy', [$adj->client_id, $adj]) }}" class="inline" onsubmit="return confirm('حذف هذه التسوية؟')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-ghost py-1 px-2 text-xs text-red-500">حذف</button>
                        </form>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center py-12 text-gray-400">لا توجد تسويات مسجّلة</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-4 py-3 border-t border-[#E0E0E0]">{{ $rows->links() }}</div>
</div>

</div>
