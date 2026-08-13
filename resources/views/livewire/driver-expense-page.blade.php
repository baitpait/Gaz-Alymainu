<div @can('delete-driver-expenses') x-data="{ deletingId: null }" @else x-data @endcan>

<div class="flex items-center justify-between mb-6 flex-wrap gap-3">
    <div>
        <h1 class="text-xl font-bold text-[#1E293B]">مصروفات السائق</h1>
        <p class="text-sm text-gray-400 mt-0.5">مصروف يُخصم من الرصيد النقدي لصندوق السائق (مستقل عن المصروفات العامة)</p>
        @if($isDriver)
        <a href="{{ route('pos.index') }}" wire:navigate class="inline-flex items-center gap-1 text-sm font-semibold text-[#1B6CA8] hover:underline mt-2">
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
            <option value="all">كل السائقين</option>
            @foreach($drivers as $d)
            <option value="{{ $d->id }}">{{ $d->full_name }}</option>
            @endforeach
        </select>
        @error('driverUserId')<p class="field-error">{{ $message }}</p>@enderror
    </div>
    @endunless
</div>

@if($hasSelection)
    @if($showAll)
    <div class="grid grid-cols-1 gap-3 mb-6 sm:grid-cols-2 sm:max-w-lg">
        <div class="card p-4 border-r-4 border-red-500">
            <p class="text-xs text-gray-400">إجمالي مصروفات كل السائقين</p>
            <p class="text-2xl font-black text-red-600 mt-1" dir="ltr">{{ number_format($totalExpenses, 2) }} ش</p>
        </div>
    </div>
    <div class="card p-4 mb-6 bg-[#F9F9FB] border border-[#E2E8F0]">
        <p class="text-sm text-gray-500">لعرض الرصيد وتسجيل مصروف جديد، اختر سائقًا محددًا من القائمة.</p>
    </div>
    @else
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
        <p class="text-sm font-bold text-[#1E293B] mb-3">تسجيل مصروف</p>
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
    @endif

<div class="card overflow-hidden">
    <div class="px-5 py-3 border-b border-[#E2E8F0] bg-[#F9F9FB]">
        <p class="text-sm font-bold text-[#1E293B]">{{ $showAll ? 'آخر مصروفات كل السائقين' : 'آخر المصروفات' }}</p>
    </div>
    <div class="overflow-x-auto [-webkit-overflow-scrolling:touch]">
        <table class="data-table">
            <thead><tr>
                <th>الوقت</th>
                @if($showAll)
                <th>السائق</th>
                @endif
                <th>التصنيف</th>
                <th class="text-left" dir="ltr">المبلغ</th>
                <th>سجّلها</th>
                <th>ملاحظات</th>
                @can('delete-driver-expenses')
                <th class="w-28"></th>
                @endcan
            </tr></thead>
            <tbody>
                @forelse($history as $h)
                <tr>
                    <td class="text-sm text-gray-500" dir="ltr">{{ \App\Support\AppDateTime::format($h->spent_at) }}</td>
                    @if($showAll)
                    <td class="font-semibold text-sm">{{ $h->driver?->full_name ?? '—' }}</td>
                    @endif
                    <td><span class="badge badge-yellow">{{ $h->category?->label() ?? '—' }}</span></td>
                    <td class="text-left font-mono text-sm font-semibold text-red-600" dir="ltr">{{ number_format((float) $h->amount, 2) }} ش</td>
                    <td class="text-sm text-gray-500">{{ $h->recordedBy?->full_name ?? '—' }}</td>
                    <td class="text-sm text-gray-500">{{ $h->notes ?? '—' }}</td>
                    @can('delete-driver-expenses')
                    <td>
                        <div class="flex items-center justify-end">
                            <button type="button"
                                    @click="deletingId = {{ $h->id }}"
                                    class="btn btn-ghost py-1 px-2 text-xs text-red-500 hover:bg-red-50">
                                حذف
                            </button>
                        </div>
                    </td>
                    @endcan
                </tr>
                @empty
                <tr><td colspan="{{ ($showAll ? 6 : 5) + (auth()->user()->can('delete-driver-expenses') ? 1 : 0) }}">
                    <div class="text-center py-12 text-gray-300"><p class="text-sm">لا توجد مصروفات بعد</p></div>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@else
<div class="card p-12 text-center text-gray-300">
    <p class="text-sm">اختر سائقًا أو «كل السائقين» لعرض المصروفات.</p>
</div>
@endif

@can('delete-driver-expenses')
<div x-show="deletingId !== null" x-cloak
     class="fixed inset-0 z-[60] flex items-center justify-center">
    <div class="fixed inset-0 bg-black/40 backdrop-blur-[2px]" @click="deletingId = null"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm mx-4 z-10 p-6">
        <h3 class="text-center font-bold text-[#1E293B] mb-2">حذف المصروف؟</h3>
        <p class="text-center text-sm text-gray-500 mb-6">
            سيُحذف السجل ويعود المبلغ إلى الرصيد النقدي لصندوق السائق.
        </p>
        <div class="flex gap-2">
            <button type="button" @click="deletingId = null" class="btn btn-secondary flex-1">إلغاء</button>
            <button type="button" class="btn btn-primary flex-1 bg-red-600 hover:bg-red-700 border-red-600"
                    x-on:click="$wire.deleteRecord(deletingId); deletingId = null">حذف</button>
        </div>
    </div>
</div>
@endcan

</div>
