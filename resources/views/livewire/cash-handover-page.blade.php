<div>

<div class="flex items-center justify-between mb-6 flex-wrap gap-3">
    <div>
        <h1 class="text-xl font-bold text-[#1E293B]">سحب الكاش</h1>
        <p class="text-sm text-gray-400 mt-0.5">سحب النقدية من صندوق السائق</p>
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
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
    <div class="card p-4">
        <p class="text-xs text-gray-400">إجمالي المبيعات النقدية</p>
        <p class="text-xl font-bold text-[#1E293B] mt-1" dir="ltr">{{ number_format($totalCash, 2) }} ش</p>
    </div>
    <div class="card p-4">
        <p class="text-xs text-gray-400">إجمالي المسحوب</p>
        <p class="text-xl font-bold text-[#1E293B] mt-1" dir="ltr">{{ number_format($totalHanded, 2) }} ش</p>
    </div>
    <div class="card p-4 border-r-4 border-green-500">
        <p class="text-xs text-gray-400">الرصيد النقدي المتاح</p>
        <p class="text-2xl font-black text-green-600 mt-1" dir="ltr">{{ number_format($balance, 2) }} ش</p>
    </div>
    <div class="card p-4 border-r-4 border-blue-500">
        <p class="text-xs text-gray-400">رصيد الشيكات المتاح</p>
        <p class="text-2xl font-black text-blue-600 mt-1" dir="ltr">{{ number_format($chequeBalance, 2) }} ش</p>
    </div>
</div>

<div class="card p-5 mb-6 max-w-lg">
    <p class="text-sm font-bold text-[#1E293B] mb-3">سحب من صندوق السائق</p>
    <div class="mb-3">
        <label class="label">آلية الدفع</label>
        <select wire:model.live="method" class="input">
            <option value="cash">نقدي</option>
            <option value="cheque">شيك</option>
        </select>
        @error('method')<p class="field-error">{{ $message }}</p>@enderror
    </div>
    <div class="flex items-end gap-2 mb-3">
        <div class="flex-1">
            <label class="label">المبلغ (ش)</label>
            <input wire:model="amount" type="number" step="0.01" min="0" dir="ltr" class="input font-mono">
            @error('amount')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <button type="button" wire:click="fillFull" class="btn btn-secondary">سحب الكل</button>
    </div>
    @if($method === 'cheque')
    <div class="mb-3">
        <label class="label">رقم الشيك (اختياري)</label>
        <input wire:model="chequeNumber" type="text" class="input font-mono" maxlength="100" placeholder="اختياري">
    </div>
    @endif
    <div class="mb-3">
        <label class="label">ملاحظات</label>
        <input wire:model="notes" type="text" class="input" maxlength="2000" placeholder="اختياري">
    </div>
    <button type="button" wire:click="handOver" wire:loading.attr="disabled" class="btn btn-primary w-full justify-center">
        {{ $method === 'cheque' ? 'سحب شيك' : 'سحب كاش' }}
    </button>
</div>

<div class="card overflow-hidden">
    <div class="px-5 py-3 border-b border-[#E2E8F0] bg-[#F9F9FB]">
        <p class="text-sm font-bold text-[#1E293B]">آخر عمليات السحب</p>
    </div>
    <div class="overflow-x-auto [-webkit-overflow-scrolling:touch]">
        <table class="data-table">
            <thead><tr>
                <th>الوقت</th>
                <th>الآلية</th>
                <th class="text-left" dir="ltr">المبلغ</th>
                <th>رقم الشيك</th>
                <th>نفّذها</th>
                <th>ملاحظات</th>
            </tr></thead>
            <tbody>
                @forelse($history as $h)
                <tr>
                    <td class="text-sm text-gray-500" dir="ltr">{{ $h->handed_at?->format('Y-m-d H:i') }}</td>
                    <td>
                        <span class="badge {{ $h->method === \App\Enums\CollectionMethod::Cheque ? 'badge-blue' : 'badge-green' }}">
                            {{ $h->method?->label() ?? 'نقدي' }}
                        </span>
                    </td>
                    <td class="text-left font-mono text-sm font-semibold" dir="ltr">{{ number_format((float) $h->amount, 2) }} ش</td>
                    <td class="text-sm text-gray-500 font-mono" dir="ltr">{{ $h->cheque_number ?? '—' }}</td>
                    <td class="text-sm text-gray-500">{{ $h->receivedBy?->full_name ?? '—' }}</td>
                    <td class="text-sm text-gray-500">{{ $h->notes ?? '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="6">
                    <div class="text-center py-12 text-gray-300"><p class="text-sm">لا توجد عمليات سحب بعد</p></div>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@else
<div class="card p-12 text-center text-gray-300">
    <p class="text-sm">اختر سائقًا لعرض صندوقه.</p>
</div>
@endif

</div>
