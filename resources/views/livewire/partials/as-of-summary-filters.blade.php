@props([
    'applyMethod' => 'applyPeriodFilters',
    'clearMethod' => 'clearPeriodFilters',
    'showClient' => false,
    'showSupplier' => false,
    'currencyOptions' => [],
    'clientOptions' => [],
    'supplierOptions' => [],
])

<form wire:submit.prevent="{{ $applyMethod }}" class="bg-white border border-[#E0E0E0] rounded p-4 mb-6">
    <div class="flex flex-wrap items-end justify-between gap-3 mb-3">
        <h2 class="text-sm font-bold text-[#1E293B]">فلاتر التقرير</h2>
        <p class="text-xs text-gray-400">الرصيد يُحسب حتى «إلى تاريخ»</p>
    </div>
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
        <div>
            <label class="block text-xs font-medium text-[#1E293B] mb-1">من تاريخ</label>
            <input type="date" wire:model="dateFrom" dir="ltr"
                   class="border border-[#E0E0E0] rounded px-3 py-2 text-sm w-full focus:outline-none focus:border-[#1B6CA8]">
        </div>
        <div>
            <label class="block text-xs font-medium text-[#1E293B] mb-1">إلى تاريخ (حتى)</label>
            <input type="date" wire:model="dateTo" dir="ltr"
                   class="border border-[#E0E0E0] rounded px-3 py-2 text-sm w-full focus:outline-none focus:border-[#1B6CA8]">
        </div>
        <div>
            <label class="block text-xs font-medium text-[#1E293B] mb-1">العملة</label>
            <select wire:model="currency"
                    class="border border-[#E0E0E0] rounded px-3 py-2 text-sm w-full focus:outline-none focus:border-[#1B6CA8]">
                <option value="">كل العملات</option>
                @foreach($currencyOptions as $c)
                    <option value="{{ $c }}" dir="ltr">{{ $c }}</option>
                @endforeach
            </select>
        </div>
        @if($showClient)
        <div>
            <label class="block text-xs font-medium text-[#1E293B] mb-1">العميل</label>
            <select wire:model="clientId"
                    class="border border-[#E0E0E0] rounded px-3 py-2 text-sm w-full focus:outline-none focus:border-[#1B6CA8]">
                <option value="">كل العملاء</option>
                @foreach($clientOptions as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>
        @endif
        @if($showSupplier)
        <div>
            <label class="block text-xs font-medium text-[#1E293B] mb-1">المورد</label>
            <select wire:model="supplierId"
                    class="border border-[#E0E0E0] rounded px-3 py-2 text-sm w-full focus:outline-none focus:border-[#1B6CA8]">
                <option value="">كل الموردين</option>
                @foreach($supplierOptions as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>
        @endif
        <div>
            <label class="block text-xs font-medium text-[#1E293B] mb-1">بحث</label>
            <input type="text" wire:model="search" placeholder="اسم أو هاتف"
                   class="border border-[#E0E0E0] rounded px-3 py-2 text-sm w-full focus:outline-none focus:border-[#1B6CA8]">
        </div>
        <div>
            <label class="block text-xs font-medium text-[#1E293B] mb-1">حد أدنى للمبلغ</label>
            <input type="number" wire:model="minBalance" step="0.01" min="0" dir="ltr"
                   class="border border-[#E0E0E0] rounded px-3 py-2 text-sm w-full focus:outline-none focus:border-[#1B6CA8]">
        </div>
        <div class="flex flex-wrap gap-2 items-end">
            @include('livewire.partials.list-filter-actions', [
                'applyMethod' => $applyMethod,
                'clearMethod' => $clearMethod,
                'showClear' => true,
            ])
        </div>
    </div>
</form>
