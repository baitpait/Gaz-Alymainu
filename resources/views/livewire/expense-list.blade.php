<div x-data="{ deletingId: null }">

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-bold text-[#1E293B]">المصروفات</h1>
        <p class="text-sm text-gray-400 mt-0.5">{{ $rows->total() }} سجل</p>
    </div>
    @if(auth()->user()->isAccountant())
    <a href="{{ route('expenses.create') }}" wire:navigate class="btn btn-primary" style="text-decoration:none;">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        إضافة مصروف
    </a>
    @endif
</div>

@include('livewire.partials.cashflow-list-filters', [
    'partyType' => 'client',
    'showParty' => false,
    'showMethod' => false,
    'currencies' => $currencies,
    'dateLabel' => 'تاريخ المصروف',
    'parties' => collect(),
    'generalSearchPlaceholder' => 'بحث في الوصف أو الملاحظات...',
])

<div class="card overflow-hidden">
    <div wire:loading.delay class="h-0.5 bg-[#1B6CA8]/20 relative overflow-hidden"><div class="absolute inset-y-0 right-0 w-1/3 bg-[#1B6CA8] animate-pulse"></div></div>
    <table class="data-table">
        <thead><tr><th>التاريخ</th><th>الوصف</th><th>المبلغ</th><th class="w-24"></th></tr></thead>
        <tbody>
            @forelse($rows as $exp)
            <tr>
                <td class="text-gray-500 font-mono text-xs" dir="ltr">{{ $exp->expense_date?->format('Y-m-d') ?? '—' }}</td>
                <td class="font-medium">{{ $exp->description }}</td>
                <td class="font-mono font-semibold text-xs text-red-600" dir="ltr">
                    {{ number_format((float)$exp->amount,2) }}
                    <span class="text-gray-400 font-normal">{{ $exp->currency_code }}</span>
                </td>
                <td>
                    <div class="flex items-center gap-1 justify-end">
                        <a href="{{ route('expenses.show', $exp->id) }}" wire:navigate class="btn btn-ghost py-1 px-2 text-xs text-gray-500 hover:bg-gray-50" style="text-decoration:none;">عرض</a>
                        @if(auth()->user()->isAccountant())
                        <a href="{{ route('expenses.edit', $exp->id) }}" wire:navigate class="btn btn-ghost py-1 px-2 text-xs text-blue-600 hover:bg-blue-50" style="text-decoration:none;">تعديل</a>
                        @endif
                        @if(auth()->user()->isManager())
                        <button type="button" @click="deletingId = {{ $exp->id }}" class="btn btn-ghost py-1 px-2 text-xs text-red-500 hover:bg-red-50">حذف</button>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="4">
                <div class="text-center py-16 text-gray-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 13l-5 5m0 0l-5-5m5 5V6"/></svg>
                    <p class="text-sm">{{ $search || $this->hasActiveListFilters() ? 'لا توجد نتائج للبحث أو الفلتر' : 'لا توجد مصروفات بعد' }}</p>
                </div>
            </td></tr>
            @endforelse
        </tbody>
    </table>

    <x-list-pagination :paginator="$rows" />
</div>


<div x-show="deletingId !== null" x-cloak
     class="fixed inset-0 z-[60] flex items-center justify-center">
    <div class="fixed inset-0 bg-black/40 backdrop-blur-[2px]" @click="deletingId = null"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm mx-4 z-10 p-6">
        <div class="w-12 h-12 rounded-full bg-red-50 flex items-center justify-center mx-auto mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        </div>
        <h3 class="text-base font-bold text-center mb-1">حذف المصروف</h3>
        <p class="text-sm text-gray-400 text-center mb-5">هل أنت متأكد؟</p>
        <form method="POST" :action="'{{ url('/expenses') }}/' + deletingId">
            @csrf @method('DELETE')
            <div class="flex gap-2">
                <button type="button" @click="deletingId = null" class="btn btn-secondary flex-1">إلغاء</button>
                <button type="submit" class="btn btn-danger flex-1">حذف</button>
            </div>
        </form>
    </div>
</div>

</div>
