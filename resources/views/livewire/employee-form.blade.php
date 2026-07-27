<div>
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-bold text-[#1E293B]">{{ $employeeId ? 'تعديل موظف' : 'إضافة موظف' }}</h1>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('employees.index') }}" wire:navigate class="btn btn-secondary" style="text-decoration:none;">إلغاء</a>
        <button wire:click="save" wire:loading.attr="disabled" class="btn btn-primary">حفظ</button>
    </div>
</div>

<div class="card max-w-3xl mx-auto p-6 space-y-4">
    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label class="label">الاسم الكامل <span class="text-red-400">*</span></label>
            <input wire:model="full_name" type="text" class="input">
            @error('full_name')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label">الرقم الوظيفي</label>
            <input wire:model="employee_code" type="text" dir="ltr" class="input">
            @error('employee_code')<p class="field-error">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label class="label">القسم</label>
            <input wire:model="department" type="text" class="input">
        </div>
        <div>
            <label class="label">المسمى الوظيفي</label>
            <input wire:model="job_title" type="text" class="input">
        </div>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label class="label">الهاتف</label>
            <input wire:model="phone_primary" type="tel" dir="ltr" class="input">
        </div>
        <div>
            <label class="label">هاتف إضافي</label>
            <input wire:model="phone_secondary" type="tel" dir="ltr" class="input">
        </div>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label class="label">البريد</label>
            <input wire:model="email" type="email" dir="ltr" class="input">
            @error('email')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label">رقم الهوية</label>
            <input wire:model="national_id" type="text" dir="ltr" class="input">
        </div>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label class="label">تاريخ التعيين</label>
            <input wire:model="hire_date" type="date" dir="ltr" class="input">
        </div>
        <div>
            <label class="label">تاريخ إنهاء الخدمة</label>
            <input wire:model="termination_date" type="date" dir="ltr" class="input">
        </div>
    </div>

    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div>
            <label class="label">الراتب الأساسي <span class="text-red-400">*</span></label>
            <input wire:model="base_salary_amount" type="number" step="0.01" min="0" dir="ltr" class="input">
            @error('base_salary_amount')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label">العملة</label>
            <select wire:model="base_salary_currency" class="input select">
                @foreach($currencyOptions as $c)
                <option value="{{ $c }}" dir="ltr">{{ $c }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="label">نوع التوظيف <span class="text-red-400">*</span></label>
            <select wire:model="pay_frequency" class="input select">
                @foreach($employmentTypeOptions as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            @error('pay_frequency')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <div class="flex items-end pb-2">
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" wire:model="is_active" class="rounded border-gray-300">
                موظف نشط
            </label>
        </div>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label class="label">البنك</label>
            <input wire:model="bank_name" type="text" class="input">
        </div>
        <div>
            <label class="label">رقم الحساب</label>
            <input wire:model="bank_account" type="text" dir="ltr" class="input">
        </div>
    </div>

    <div>
        <label class="label">ملاحظات</label>
        <textarea wire:model="notes" rows="3" class="input"></textarea>
    </div>
</div>
</div>
