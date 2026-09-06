<div
    @if(auth()->user()->can('delete-collections') || auth()->user()->can('update-collections'))
        x-data="{ deletingId: null }"
    @else
        x-data
    @endif
>

<div class="flex items-center justify-between mb-6 flex-wrap gap-3">
    <div>
        <h1 class="text-xl font-bold text-[#1E293B]">التحصيل</h1>
        <p class="text-sm text-gray-400 mt-0.5">تسجيل مبلغ محصّل (نقدي/شيك) بلا تفاصيل زبون</p>
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
    <div class="grid grid-cols-2 gap-3 mb-6 sm:max-w-lg">
        <div class="card p-4 border-r-4 border-green-500">
            <p class="text-xs text-gray-400">تحصيل نقدي (حسب الفلتر)</p>
            <p class="text-2xl font-black text-green-600 mt-1" dir="ltr">{{ number_format($totalCash, 2) }} ش</p>
        </div>
        <div class="card p-4 border-r-4 border-blue-500">
            <p class="text-xs text-gray-400">شيكات (حسب الفلتر)</p>
            <p class="text-2xl font-black text-blue-600 mt-1" dir="ltr">{{ number_format($totalCheque, 2) }} ش</p>
        </div>
    </div>
    <div class="card p-4 mb-6 bg-[#F9F9FB] border border-[#E2E8F0]">
        <p class="text-sm text-gray-500">لتسجيل تحصيل جديد، اختر سائقًا محددًا من القائمة.</p>
    </div>
    @else
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
            <select wire:model.live="method" class="input h-12">
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
        @if($method === 'cheque')
        <div class="mb-3">
            <label class="label">رقم الشيك</label>
            <input wire:model="chequeNumber" type="text" class="input" maxlength="100" placeholder="اختياري" dir="ltr">
            @error('chequeNumber')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        @endif
        <div class="mb-3">
            <label class="label">ملاحظات</label>
            <input wire:model="notes" type="text" class="input" maxlength="2000" placeholder="اختياري">
        </div>
        <button type="button" wire:click="save" wire:loading.attr="disabled" class="btn btn-primary w-full justify-center h-12 text-base">
            حفظ التحصيل
        </button>
    </div>
    @endif

<form wire:submit.prevent="applyCollectionFilters" class="card p-4 mb-5">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between lg:gap-6">
        <div class="flex min-w-0 flex-1 flex-col gap-3">
            <div class="min-w-0 w-full">
                <label class="label">بحث</label>
                <input type="search" wire:model="filterSearch" class="input w-full text-sm"
                       placeholder="بحث بالملاحظات أو رقم الشيك أو اسم السائق..." autocomplete="off">
            </div>
            <div class="grid min-w-0 w-full grid-cols-1 gap-3 sm:grid-cols-3">
                <div class="min-w-0">
                    <label class="label">الآلية</label>
                    <select wire:model="filterMethod" class="input w-full">
                        <option value="">الكل</option>
                        <option value="cash">نقدي</option>
                        <option value="cheque">شيك</option>
                    </select>
                </div>
                <div class="min-w-0">
                    <label class="label">من تاريخ</label>
                    <input wire:model="filterDateFrom" type="date" class="input w-full" dir="ltr">
                </div>
                <div class="min-w-0">
                    <label class="label">إلى تاريخ</label>
                    <input wire:model="filterDateTo" type="date" class="input w-full" dir="ltr">
                </div>
            </div>
        </div>
        @include('livewire.partials.list-filter-actions', [
            'applyMethod' => 'applyCollectionFilters',
            'clearMethod' => 'clearCollectionFilters',
            'showClear' => $this->hasActiveCollectionFilters(),
        ])
    </div>
</form>

@php
    $canMutate = auth()->user()->can('update-collections') || auth()->user()->can('delete-collections');
    $colCount = 5 + ($showAll ? 1 : 0) + ($isDriver ? 0 : 1) + ($canMutate ? 1 : 0);
@endphp

<div class="card overflow-hidden">
    <div class="px-5 py-3 border-b border-[#E2E8F0] bg-[#F9F9FB]">
        <p class="text-sm font-bold text-[#1E293B]">{{ $showAll ? 'تحصيلات كل السائقين' : 'آخر التحصيلات' }}</p>
    </div>
    <div class="overflow-x-auto [-webkit-overflow-scrolling:touch]">
        <table class="data-table">
            <thead><tr>
                <th>الوقت</th>
                @if($showAll)
                <th>السائق</th>
                @endif
                <th>الآلية</th>
                <th class="text-left" dir="ltr">المبلغ</th>
                <th>رقم الشيك</th>
                @unless($isDriver)
                <th>سجّلها</th>
                @endunless
                <th>ملاحظات</th>
                @if($canMutate)
                <th class="w-36"></th>
                @endif
            </tr></thead>
            <tbody>
                @forelse($history as $h)
                <tr>
                    <td class="text-sm text-gray-500" dir="ltr">{{ \App\Support\AppDateTime::format($h->collected_at) }}</td>
                    @if($showAll)
                    <td class="font-semibold text-sm">{{ $h->driver?->full_name ?? '—' }}</td>
                    @endif
                    <td>
                        <span class="badge {{ $h->method === \App\Enums\CollectionMethod::Cheque ? 'badge-blue' : 'badge-green' }}">
                            {{ $h->method?->label() ?? 'نقدي' }}
                        </span>
                    </td>
                    <td class="text-left font-mono text-sm font-semibold" dir="ltr">{{ number_format((float) $h->amount, 2) }} ش</td>
                    <td class="text-sm text-gray-500 font-mono" dir="ltr">{{ $h->cheque_number ?? '—' }}</td>
                    @unless($isDriver)
                    <td class="text-sm text-gray-500">{{ $h->recordedBy?->full_name ?? '—' }}</td>
                    @endunless
                    <td class="text-sm text-gray-500">{{ $h->notes ?? '—' }}</td>
                    @if($canMutate)
                    <td>
                        <div class="flex items-center justify-end gap-1">
                            @can('update-collections')
                            <button type="button"
                                    wire:click="startEdit({{ $h->id }})"
                                    class="btn btn-ghost py-1 px-2 text-xs text-[#1B6CA8] hover:bg-[#1B6CA8]/10">
                                تعديل
                            </button>
                            @endcan
                            @can('delete-collections')
                            <button type="button"
                                    @click="deletingId = {{ $h->id }}"
                                    class="btn btn-ghost py-1 px-2 text-xs text-red-500 hover:bg-red-50">
                                حذف
                            </button>
                            @endcan
                        </div>
                    </td>
                    @endif
                </tr>
                @empty
                <tr><td colspan="{{ $colCount }}">
                    <div class="text-center py-12 text-gray-300"><p class="text-sm">لا توجد تحصيلات بعد</p></div>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@else
<div class="card p-12 text-center text-gray-300">
    <p class="text-sm">اختر سائقًا أو «كل السائقين» لعرض التحصيلات.</p>
</div>
@endif

@can('update-collections')
@if($editingId !== null)
<div class="fixed inset-0 z-[60] flex items-center justify-center">
    <div class="fixed inset-0 bg-black/40 backdrop-blur-[2px]" wire:click="cancelEdit"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 z-10 p-6">
        <h3 class="text-center font-bold text-[#1E293B] mb-1">تعديل التحصيل</h3>
        <p class="text-center text-xs text-gray-400 mb-5">تعديل المبلغ والآلية والملاحظات. التخفيض مشروط برصيد صندوق السائق.</p>

        <div class="space-y-4">
            <div>
                <label class="label">آلية الدفع</label>
                <select wire:model.live="editMethod" class="input w-full">
                    <option value="cash">نقدي</option>
                    <option value="cheque">شيك</option>
                </select>
                @error('editMethod') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label">المبلغ (ش)</label>
                <input type="number" step="0.01" min="0.01" wire:model="editAmount" class="input w-full font-mono" dir="ltr">
                @error('editAmount') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
            @if($editMethod === 'cheque')
            <div>
                <label class="label">رقم الشيك</label>
                <input type="text" wire:model="editChequeNumber" class="input w-full" maxlength="100" placeholder="اختياري" dir="ltr">
                @error('editChequeNumber') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
            @endif
            <div>
                <label class="label">الملاحظات</label>
                <textarea wire:model="editNotes" rows="3" class="input w-full" placeholder="اختياري"></textarea>
                @error('editNotes') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="flex gap-2 mt-6">
            <button type="button" wire:click="cancelEdit" class="btn btn-secondary flex-1">إلغاء</button>
            <button type="button" wire:click="saveEdit" class="btn btn-primary flex-1" wire:loading.attr="disabled">حفظ</button>
        </div>
    </div>
</div>
@endif
@endcan

@can('delete-collections')
<div x-show="deletingId !== null" x-cloak
     class="fixed inset-0 z-[60] flex items-center justify-center">
    <div class="fixed inset-0 bg-black/40 backdrop-blur-[2px]" @click="deletingId = null"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm mx-4 z-10 p-6">
        <h3 class="text-center font-bold text-[#1E293B] mb-2">حذف التحصيل؟</h3>
        <p class="text-center text-sm text-gray-500 mb-6">
            سيُحذف السجل ويُستبعد من صندوق السائق ودين السوق.
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
