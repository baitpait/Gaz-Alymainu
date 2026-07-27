<div>

<div class="flex items-center justify-between mb-6 flex-wrap gap-3">
    <div>
        <h1 class="text-xl font-bold text-[#3D3D3D]">مصروفات السائق</h1>
        <p class="text-sm text-gray-400 mt-0.5">مصروف يُخصم من الرصيد النقدي لصندوق السائق (مستقل عن المصروفات العامة)</p>
        @if($isDriver)
        <a href="{{ route('pos.index') }}" wire:navigate class="inline-flex items-center gap-1 text-sm font-semibold text-[#C9A227] hover:underline mt-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
            رجوع لنقطة البيع
        </a>
        @endif
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
        <p class="text-xs text-gray-400">الرصيد النقدي المتاح</p>
        <p class="text-2xl font-black text-green-600 mt-1" dir="ltr">{{ number_format($balance, 2) }} ش</p>
    </div>
    <div class="card p-4 border-r-4 border-red-500">
        <p class="text-xs text-gray-400">إجمالي المصروفات</p>
        <p class="text-2xl font-black text-red-600 mt-1" dir="ltr">{{ number_format($totalExpenses, 2) }} ش</p>
    </div>
</div>

<div class="card p-5 mb-6 max-w-lg">
    <p class="text-sm font-bold text-[#3D3D3D] mb-3">تسجيل مصروف</p>
    <div class="mb-3">
        <label class="label">التصنيف</label>
        <select wire:model="category" class="input">
            @foreach($categories as $value => $label)
            <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
        @error('category')<p class="field-error">{{ $message }}</p>@enderror
    </div>
    <div class="mb-3">
        <label class="label">المبلغ (ش)</label>
        <input wire:model="amount" type="number" step="0.01" min="0" dir="ltr" class="input font-mono">
        @error('amount')<p class="field-error">{{ $message }}</p>@enderror
    </div>
    <div class="mb-3">
        <label class="label">ملاحظات</label>
        <input wire:model="notes" type="text" class="input" maxlength="2000" placeholder="اختياري">
    </div>
    <button type="button" wire:click="save" wire:loading.attr="disabled" class="btn btn-primary w-full justify-center">
        تسجيل المصروف
    </button>
</div>

<div class="card overflow-hidden">
    <div class="px-5 py-3 border-b border-[#E2E4E9] bg-[#F9F9FB]">
        <p class="text-sm font-bold text-[#3D3D3D]">آخر المصروفات</p>
    </div>
    <table class="data-table">
        <thead><tr>
            <th>الوقت</th>
            <th>التصنيف</th>
            <th class="text-left" dir="ltr">المبلغ</th>
            <th>سجّلها</th>
            <th>ملاحظات</th>
        </tr></thead>
        <tbody>
            @forelse($history as $h)
            <tr>
                <td class="text-sm text-gray-500" dir="ltr">{{ $h->spent_at?->format('Y-m-d H:i') }}</td>
                <td><span class="badge badge-yellow">{{ $h->category?->label() ?? '—' }}</span></td>
                <td class="text-left font-mono text-sm font-semibold text-red-600" dir="ltr">{{ number_format((float) $h->amount, 2) }} ش</td>
                <td class="text-sm text-gray-500">{{ $h->recordedBy?->full_name ?? '—' }}</td>
                <td class="text-sm text-gray-500">{{ $h->notes ?? '—' }}</td>
            </tr>
            @empty
            <tr><td colspan="5">
                <div class="text-center py-12 text-gray-300"><p class="text-sm">لا توجد مصروفات بعد</p></div>
            </td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@else
<div class="card p-12 text-center text-gray-300">
    <p class="text-sm">اختر سائقًا لعرض مصروفاته.</p>
</div>
@endif

</div>
