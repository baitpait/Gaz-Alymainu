<div>
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-bold text-[#3D3D3D]">{{ $recordId ? 'تعديل راتب' : 'تسجيل راتب' }}</h1>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('salary-payments.index') }}" wire:navigate class="btn btn-secondary" style="text-decoration:none;">إلغاء</a>
        <button wire:click="save" wire:loading.attr="disabled" class="btn btn-primary">حفظ</button>
    </div>
</div>

<div class="card max-w-3xl mx-auto p-6 space-y-4">
    <div>
        <label class="label">الموظف <span class="text-red-400">*</span></label>
        <select wire:model.live="employee_id" class="input select" @if($recordId) disabled @endif>
            <option value="">— اختر —</option>
            @foreach($employeeOptions as $id => $name)
            <option value="{{ $id }}">{{ $name }}</option>
            @endforeach
        </select>
        @error('employee_id')<p class="field-error">{{ $message }}</p>@enderror
    </div>

    <div class="grid sm:grid-cols-3 gap-4">
        <div>
            <label class="label">السنة</label>
            <input wire:model="period_year" type="number" min="2000" max="2100" dir="ltr" class="input">
            @error('period_year')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label">الشهر</label>
            <select wire:model="period_month" class="input select">
                @for($m = 1; $m <= 12; $m++)
                <option value="{{ $m }}">{{ sprintf('%02d', $m) }}</option>
                @endfor
            </select>
            @error('period_month')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label">العملة</label>
            <select wire:model="currency_code" class="input select">
                @foreach($currencyOptions as $c)
                <option value="{{ $c }}" dir="ltr">{{ $c }}</option>
                @endforeach
            </select>
        </div>
    </div>

    @if($isDaily)
    <div class="grid sm:grid-cols-3 gap-4">
        <div>
            <label class="label">أجرة اليوم <span class="text-red-400">*</span></label>
            <input wire:model.live="daily_rate" type="number" step="0.01" min="0" dir="ltr" class="input">
            @error('daily_rate')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label">عدد أيام العمل <span class="text-red-400">*</span></label>
            <input wire:model.live="worked_days" type="number" step="1" min="0" max="31" dir="ltr" class="input">
            @error('worked_days')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label">الأساسي (محسوب)</label>
            <input value="{{ number_format((float) $base_amount, 2) }}" type="text" dir="ltr" class="input bg-gray-100" readonly>
            <p class="text-xs text-gray-400 mt-1">أجرة اليوم × عدد الأيام</p>
        </div>
    </div>
    @else
    <div class="grid sm:grid-cols-3 gap-4">
        <div>
            <label class="label">الأساسي</label>
            <input wire:model.live="base_amount" type="number" step="0.01" min="0" dir="ltr" class="input">
            @error('base_amount')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <div class="hidden sm:block"></div>
        <div class="hidden sm:block"></div>
    </div>
    @endif

    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label class="label">مكافأة</label>
            <input wire:model.live="bonus_amount" type="number" step="0.01" min="0" dir="ltr" class="input">
        </div>
        <div>
            <label class="label">خصم</label>
            <input wire:model.live="deduction_amount" type="number" step="0.01" min="0" dir="ltr" class="input">
        </div>
    </div>

    <div class="bg-amber-50 rounded-lg px-4 py-3 text-sm">
        <span class="text-gray-600">الصافي:</span>
        <span class="font-mono font-bold text-[#C9A227] mr-2" dir="ltr">{{ number_format($netPreview, 2) }} {{ $currency_code }}</span>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label class="label">الحالة</label>
            <select wire:model.live="status" class="input select">
                <option value="draft">مسودة</option>
                <option value="paid">مدفوع</option>
                <option value="cancelled">ملغى</option>
            </select>
        </div>
        @if($status === 'paid')
        <div>
            <label class="label">تاريخ الدفع</label>
            <input wire:model="paid_at" type="date" dir="ltr" class="input">
            @error('paid_at')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        @endif
    </div>

    @if($status === 'paid')
    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label class="label">طريقة الدفع</label>
            <select wire:model="method" class="input select">
                <option value="bank">بنك</option>
                <option value="cash">نقد</option>
                <option value="check">شيك</option>
                <option value="transfer">تحويل</option>
            </select>
            @error('method')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label">مرجع بنكي</label>
            <input wire:model="bank_reference" type="text" dir="ltr" class="input">
        </div>
    </div>
    @endif

    <div>
        <label class="label">ملاحظات</label>
        <textarea wire:model="notes" rows="3" class="input"></textarea>
    </div>
</div>
</div>
