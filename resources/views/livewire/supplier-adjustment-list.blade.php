<div x-data="{ deletingId: null }">

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-bold text-[#1E293B]">تسويات الموردين</h1>
        <p class="text-sm text-gray-400 mt-0.5">{{ $rows->total() }} تسوية</p>
    </div>
</div>

<div class="card p-4 mb-5">
    <p class="text-sm font-semibold text-[#1E293B] mb-3">تسوية جديدة</p>
    <div class="flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[200px]">
            <label class="label">المورد</label>
            <input type="search" wire:model.live.debounce.300ms="supplierSearch" class="input mb-2" placeholder="ابحث باسم المورد...">
            <select wire:model="supplier_id" class="input select">
                <option value="">— اختر المورد —</option>
                @foreach($suppliers as $s)
                <option value="{{ $s->id }}">{{ $s->displayName() }}</option>
                @endforeach
            </select>
            @error('supplier_id')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <button type="button" wire:click="goCreate" class="btn btn-primary">+ تسوية على الذمة</button>
    </div>
</div>

@include('livewire.partials.list-search-form', [
    'searchPlaceholder' => 'بحث بالمورد أو السبب...',
    'hasActive' => $this->hasActiveListFilters(),
])

<div class="card overflow-hidden">
    <div class="overflow-x-auto [-webkit-overflow-scrolling:touch]">
        <table class="data-table">
            <thead>
                <tr><th>التاريخ</th><th>المورد</th><th>النوع</th><th>السبب</th><th>المبلغ</th><th class="w-32"></th></tr>
            </thead>
            <tbody>
                @forelse($rows as $adj)
                <tr>
                    <td class="font-mono text-xs" dir="ltr">{{ $adj->adjustment_date?->format('Y-m-d') }}</td>
                    <td class="font-semibold">{{ $adj->supplier?->displayName() }}</td>
                    <td><span class="badge" style="background:#EDE9FE;color:#6D28D9;">{{ $adj->typeLabel() }}</span></td>
                    <td>{{ $adj->reason ?? '—' }}</td>
                    <td class="font-mono font-semibold text-[#7C3AED]" dir="ltr">−{{ number_format((float) $adj->amount, 2) }} {{ $adj->currency_code }}</td>
                    <td>
                        <a href="{{ route('suppliers.statement', $adj->supplier_id) }}" wire:navigate class="btn btn-ghost py-1 px-2 text-xs text-[#1B6CA8]" style="text-decoration:none;">كشف</a>
                        <a href="{{ route('suppliers.adjustments.edit', [$adj->supplier_id, $adj]) }}" wire:navigate class="btn btn-ghost py-1 px-2 text-xs text-blue-600" style="text-decoration:none;">تعديل</a>
                        @if(auth()->user()->isManager())
                        <form method="POST" action="{{ route('suppliers.adjustments.destroy', [$adj->supplier_id, $adj]) }}" class="inline" onsubmit="return confirm('حذف هذه التسوية؟')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-ghost py-1 px-2 text-xs text-red-500">حذف</button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center py-12 text-gray-400">لا توجد تسويات</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-4 py-3 border-t">{{ $rows->links() }}</div>
</div>

</div>
