<div>

<div class="flex items-center justify-between mb-6 flex-wrap gap-3">
    <div>
        <h1 class="text-xl font-bold text-[#1E293B]">التحصيل</h1>
        <p class="text-sm text-gray-400 mt-0.5">تسجيل مبلغ محصّل (نقدي/شيك) بلا تفاصيل زبون</p>
    </div>
    @unless($isDriver)
    <div>
        <label class="label">السائق</label>
        <select wire:model.live="driverUserId" class="input min-w-56">
            <option value="">— اختر السائق —</option>
            @foreach($drivers as $d)
            <option value="{{ $d->id }}">{{ $d->full_name }}</option>
            @endforeach
        </select>
    </div>
    @endunless
</div>

@if($driverUserId)
<div class="grid grid-cols-2 gap-3 mb-6">
    <div class="card p-4 border-r-4 border-green-500">
        <p class="text-xs text-gray-400">تحصيل نقدي اليوم</p>
        <p class="text-2xl font-black text-green-600 mt-1" dir="ltr">{{ number_format($cashToday, 2) }} ش</p>
    </div>
    <div class="card p-4 border-r-4 border-blue-500">
        <p class="text-xs text-gray-400">شيكات اليوم</p>
        <p class="text-2xl font-black text-blue-600 mt-1" dir="ltr">{{ number_format($chequeToday, 2) }} ش</p>
    </div>
</div>

<div class="card p-5 mb-6 max-w-lg">
    <p class="text-sm font-bold text-[#1E293B] mb-3">تسجيل تحصيل</p>
    <div class="mb-3">
        <label class="label">آلية الدفع</label>
        <select wire:model="method" class="input h-12">
            <option value="cash">نقدي</option>
            <option value="cheque">شيك</option>
        </select>
        @error('method')<p class="field-error">{{ $message }}</p>@enderror
    </div>
    <div class="mb-3">
        <label class="label">المبلغ (ش)</label>
        <input wire:model="amount" type="number" step="0.01" min="0" dir="ltr" class="input font-mono text-lg h-12">
        @error('amount')<p class="field-error">{{ $message }}</p>@enderror
    </div>
    <div class="mb-3">
        <label class="label">ملاحظات</label>
        <input wire:model="notes" type="text" class="input" maxlength="2000" placeholder="اختياري">
    </div>
    <button type="button" wire:click="save" wire:loading.attr="disabled" class="btn btn-primary w-full justify-center h-12 text-base">
        حفظ التحصيل
    </button>
</div>

<div class="card overflow-hidden">
    <div class="px-5 py-3 border-b border-[#E2E8F0] bg-[#F9F9FB]">
        <p class="text-sm font-bold text-[#1E293B]">آخر التحصيلات</p>
    </div>
    <div class="overflow-x-auto [-webkit-overflow-scrolling:touch]">
        <table class="data-table">
            <thead><tr>
                <th>الوقت</th>
                <th>الآلية</th>
                <th class="text-left" dir="ltr">المبلغ</th>
                <th>ملاحظات</th>
            </tr></thead>
            <tbody>
                @forelse($history as $h)
                <tr>
                    <td class="text-sm text-gray-500" dir="ltr">{{ \App\Support\AppDateTime::format($h->collected_at) }}</td>
                    <td>
                        <span class="badge {{ $h->method === \App\Enums\CollectionMethod::Cheque ? 'badge-blue' : 'badge-green' }}">
                            {{ $h->method?->label() ?? 'نقدي' }}
                        </span>
                    </td>
                    <td class="text-left font-mono text-sm font-semibold" dir="ltr">{{ number_format((float) $h->amount, 2) }} ش</td>
                    <td class="text-sm text-gray-500">{{ $h->notes ?? '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="4">
                    <div class="text-center py-12 text-gray-300"><p class="text-sm">لا توجد تحصيلات بعد</p></div>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@else
<div class="card p-12 text-center text-gray-300">
    <p class="text-sm">اختر سائقًا لعرض تحصيلاته.</p>
</div>
@endif

</div>
