<div
    @if(auth()->user()->can('delete-sales') || auth()->user()->can('update-sales'))
        x-data="{ deletingId: null }"
    @else
        x-data
    @endif
>

<div class="flex items-center justify-between mb-6 flex-wrap gap-3">
    <div>
        <h1 class="text-xl font-bold text-[#1E293B]">سجل المبيعات</h1>
        <p class="text-sm text-gray-400 mt-0.5">{{ $rows->total() }} عملية</p>
    </div>
</div>

<form wire:submit.prevent="applySaleFilters" class="card p-4 mb-5">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between lg:gap-6">
        <div class="flex min-w-0 flex-1 flex-col gap-3">
            <div class="min-w-0 w-full">
                <label class="label">بحث عام</label>
                <input type="search" wire:model="search" class="input w-full text-sm"
                       placeholder="بحث بالصنف، السيارة، السائق، أو الملاحظات..." autocomplete="off">
            </div>

            <div class="grid min-w-0 w-full grid-cols-1 gap-3 sm:grid-cols-2">
                <div class="min-w-0">
                    <label class="label">نوع الدفع</label>
                    <select wire:model="filterPaymentType" class="input w-full">
                        <option value="">الكل</option>
                        <option value="cash">نقدي</option>
                        <option value="credit">على الحساب</option>
                    </select>
                </div>
                <div class="min-w-0">
                    <label class="label">الصنف</label>
                    <select wire:model="filterProductId" class="input w-full">
                        <option value="">كل الأصناف</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid min-w-0 w-full grid-cols-1 gap-3 sm:grid-cols-2">
                <div class="min-w-0">
                    <label class="label">السيارة / المخزن</label>
                    <select wire:model="filterWarehouseId" class="input w-full">
                        <option value="">الكل</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                </div>
                @if($showDriverFilter)
                <div class="min-w-0">
                    <label class="label">السائق</label>
                    <select wire:model="filterDriverId" class="input w-full">
                        <option value="">كل السائقين</option>
                        @foreach($drivers as $driver)
                            <option value="{{ $driver->id }}">{{ $driver->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
            </div>

            <div class="grid min-w-0 grid-cols-1 gap-3 sm:grid-cols-2">
                <div class="min-w-0">
                    <label class="label">من تاريخ البيع</label>
                    <input wire:model="filterDateFrom" type="date" class="input w-full" dir="ltr">
                </div>
                <div class="min-w-0">
                    <label class="label">إلى تاريخ البيع</label>
                    <input wire:model="filterDateTo" type="date" class="input w-full" dir="ltr">
                </div>
            </div>
        </div>
        @include('livewire.partials.list-filter-actions', [
            'applyMethod' => 'applySaleFilters',
            'clearMethod' => 'clearSaleFilters',
            'showClear' => $this->hasActiveSaleFilters(),
        ])
    </div>
</form>

<div class="card overflow-hidden">
    <div wire:loading.delay class="h-0.5 bg-[#1B6CA8]/20 relative overflow-hidden"><div class="absolute inset-y-0 right-0 w-1/3 bg-[#1B6CA8] animate-pulse"></div></div>
    <div class="overflow-x-auto [-webkit-overflow-scrolling:touch]">
        <table class="data-table">
            <thead><tr>
                <th>الوقت</th>
                <th>الصنف</th>
                <th>السيارة / السائق</th>
                <th>النوع</th>
                <th>ملاحظات</th>
                <th class="text-left" dir="ltr">الكمية</th>
                <th class="text-left" dir="ltr">السعر</th>
                <th class="text-left" dir="ltr">الإجمالي</th>
                @if(auth()->user()->can('update-sales') || auth()->user()->can('delete-sales'))
                <th class="w-36"></th>
                @endif
            </tr></thead>
            <tbody>
                @forelse($rows as $s)
                <tr>
                    <td class="text-sm text-gray-500" dir="ltr">{{ \App\Support\AppDateTime::format($s->sold_at) }}</td>
                    <td class="font-semibold text-sm">{{ $s->product?->name ?? '—' }}</td>
                    <td class="text-sm text-gray-500">{{ $s->warehouse?->name ?? '—' }}@if($s->driver) — {{ $s->driver->full_name }}@endif</td>
                    <td>
                        @if($s->payment_type->value === 'cash')
                            <span class="rounded px-2 py-0.5 bg-green-50 text-green-600 text-xs">{{ $s->payment_type->label() }}</span>
                        @else
                            <span class="rounded px-2 py-0.5 bg-[#1B6CA8]/10 text-[#1B6CA8] text-xs">{{ $s->payment_type->label() }}</span>
                        @endif
                    </td>
                    <td class="text-sm text-gray-600 max-w-[12rem]">{{ $s->notes ?: '—' }}</td>
                    <td class="text-left font-mono text-sm" dir="ltr">{{ rtrim(rtrim(number_format((float) $s->quantity, 4), '0'), '.') }}</td>
                    <td class="text-left font-mono text-sm" dir="ltr">{{ number_format((float) $s->unit_price, 2) }}</td>
                    <td class="text-left font-mono text-sm font-semibold" dir="ltr">{{ number_format((float) $s->total_amount, 2) }}</td>
                    @if(auth()->user()->can('update-sales') || auth()->user()->can('delete-sales'))
                    <td>
                        <div class="flex items-center justify-end gap-1">
                            @can('update-sales')
                            <button type="button"
                                    wire:click="startEdit({{ $s->id }})"
                                    class="btn btn-ghost py-1 px-2 text-xs text-[#1B6CA8] hover:bg-[#1B6CA8]/10">
                                تعديل
                            </button>
                            @endcan
                            @can('delete-sales')
                            <button type="button"
                                    @click="deletingId = {{ $s->id }}"
                                    class="btn btn-ghost py-1 px-2 text-xs text-red-500 hover:bg-red-50">
                                حذف
                            </button>
                            @endcan
                        </div>
                    </td>
                    @endif
                </tr>
                @empty
                <tr><td colspan="{{ (auth()->user()->can('update-sales') || auth()->user()->can('delete-sales')) ? 9 : 8 }}">
                    <div class="text-center py-16 text-gray-300"><p class="text-sm">لا توجد مبيعات</p></div>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <x-list-pagination :paginator="$rows" />
</div>

@can('update-sales')
@if($editingId !== null)
<div class="fixed inset-0 z-[60] flex items-center justify-center">
    <div class="fixed inset-0 bg-black/40 backdrop-blur-[2px]" wire:click="cancelEdit"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 z-10 p-6">
        <h3 class="text-center font-bold text-[#1E293B] mb-1">تعديل البيع</h3>
        <p class="text-center text-xs text-gray-400 mb-5">تعديل الكمية والسعر والملاحظات — الصنف والسيارة ثابتان.</p>

        <div class="space-y-4">
            <div>
                <label class="label">الكمية</label>
                <input type="number" step="0.0001" min="0.0001" wire:model="editQuantity" class="input w-full" dir="ltr">
                @error('editQuantity') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label">سعر الوحدة</label>
                <input type="number" step="0.01" min="0.01" wire:model="editUnitPrice" class="input w-full" dir="ltr">
                @error('editUnitPrice') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label">الملاحظات</label>
                <textarea wire:model="editNotes" rows="3" class="input w-full" placeholder="ملاحظات البيع على الحساب أو أي توضيح..."></textarea>
                @error('editNotes') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="flex gap-2 mt-6">
            <button type="button" wire:click="cancelEdit" class="btn btn-secondary flex-1">إلغاء</button>
            <button type="button" wire:click="saveEdit" class="btn btn-primary flex-1"
                    wire:loading.attr="disabled">حفظ</button>
        </div>
    </div>
</div>
@endif
@endcan

@can('delete-sales')
<div x-show="deletingId !== null" x-cloak
     class="fixed inset-0 z-[60] flex items-center justify-center">
    <div class="fixed inset-0 bg-black/40 backdrop-blur-[2px]" @click="deletingId = null"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm mx-4 z-10 p-6">
        <h3 class="text-center font-bold text-[#1E293B] mb-2">حذف البيع؟</h3>
        <p class="text-center text-sm text-gray-500 mb-6">
            سيُحذف السجل وتُعاد الكمية إلى مخزن/سيارة البيع. يتأثر صندوق السائق ودين السوق تلقائياً.
            البيع النقدي المُسلَّم كاشه لا يُحذف.
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
