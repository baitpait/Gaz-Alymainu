<div>

<div class="mb-6">
    <h1 class="text-xl font-bold text-[#1E293B]">دين السوق</h1>
    <p class="text-sm text-gray-400 mt-0.5">
        دين مجمّع للشركة بلا عملاء: رصيد افتتاحي + مبيعات على الحساب − تحصيل نقدي (من تاريخ البداية). الشيكات خارج الحساب.
    </p>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3 mb-6">
    <div class="card p-4 border-r-4 border-[#1B6CA8]">
        <p class="text-xs text-gray-400">الرصيد الافتتاحي</p>
        <p class="text-2xl font-black text-[#1B6CA8] mt-1" dir="ltr">{{ number_format($summary['opening_amount'], 2) }} ش</p>
        <p class="text-[11px] text-gray-400 mt-1" dir="ltr">من {{ $summary['as_of_date'] }}</p>
    </div>
    <div class="card p-4 border-r-4 border-amber-500">
        <p class="text-xs text-gray-400">مجموع البيع على الحساب</p>
        <p class="text-2xl font-black text-amber-600 mt-1" dir="ltr">{{ number_format($summary['credit_sales_total'], 2) }} ش</p>
    </div>
    <div class="card p-4 border-r-4 border-green-500">
        <p class="text-xs text-gray-400">مجموع التحصيل النقدي</p>
        <p class="text-2xl font-black text-green-600 mt-1" dir="ltr">{{ number_format($summary['cash_collections_total'], 2) }} ش</p>
    </div>
    <div class="card p-4 border-r-4 {{ $summary['is_over_collected'] ? 'border-purple-500' : 'border-red-500' }}">
        <p class="text-xs text-gray-400">{{ $summary['is_over_collected'] ? 'فائض التحصيل' : 'صافي دين السوق' }}</p>
        <p class="text-2xl font-black mt-1 {{ $summary['is_over_collected'] ? 'text-purple-600' : 'text-red-600' }}" dir="ltr">
            {{ number_format(abs($summary['net_market_debt']), 2) }} ش
        </p>
        @if($summary['is_over_collected'])
        <p class="text-[11px] text-purple-500 mt-1">التحصيل النقدي أكبر من (افتتاحي + آجل)</p>
        @endif
    </div>
</div>

<div class="card p-5 max-w-lg">
    <p class="text-sm font-bold text-[#1E293B] mb-3">إعداد الرصيد الافتتاحي</p>
    <div class="mb-3">
        <label class="label">المبلغ الافتتاحي (ش)</label>
        <input wire:model="openingAmount" type="number" step="0.01" min="0" dir="ltr" class="input font-mono">
        @error('openingAmount')<p class="field-error">{{ $message }}</p>@enderror
    </div>
    <div class="mb-3">
        <label class="label">تاريخ بداية الاحتساب</label>
        <input wire:model="asOfDate" type="date" class="input" dir="ltr">
        @error('asOfDate')<p class="field-error">{{ $message }}</p>@enderror
        <p class="text-[11px] text-gray-400 mt-1">تُحسب المبيعات الآجلة والتحصيل النقدي من هذا التاريخ فصاعداً.</p>
    </div>
    <div class="mb-4">
        <label class="label">ملاحظات</label>
        <input wire:model="notes" type="text" class="input" maxlength="2000" placeholder="اختياري — مصدر الرقم الافتتاحي">
        @error('notes')<p class="field-error">{{ $message }}</p>@enderror
    </div>
    <button type="button" wire:click="saveOpening" wire:loading.attr="disabled" class="btn btn-primary w-full justify-center">
        حفظ الإعدادات
    </button>
</div>

</div>
