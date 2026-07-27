<div>

<div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('drivers.index') }}" wire:navigate
           class="w-9 h-9 flex items-center justify-center rounded-full border border-[#E2E8F0] bg-white text-[#6B7280] hover:bg-[#F3F4F6]">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
        <div>
            <h1 class="text-xl font-bold text-[#1E293B]">{{ $userId ? 'تعديل السائق' : 'سائق جديد' }}</h1>
            <p class="text-xs text-gray-400 mt-0.5">حساب دخول بدور «سائق» + ملف موظف</p>
        </div>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('drivers.index') }}" wire:navigate class="btn btn-secondary" style="text-decoration:none;">إلغاء</a>
        <button type="button" wire:click="save" wire:loading.attr="disabled" class="btn btn-primary">حفظ</button>
    </div>
</div>

<div class="card p-5 space-y-4 mb-6 max-w-2xl">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="label">الاسم الكامل <span class="text-red-400">*</span></label>
            <input wire:model="full_name" type="text" class="input" maxlength="255">
            @error('full_name')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label">الهاتف</label>
            <input wire:model="phone_primary" type="text" class="input font-mono" dir="ltr" maxlength="30" placeholder="اختياري">
            @error('phone_primary')<p class="field-error">{{ $message }}</p>@enderror
        </div>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="label">البريد الإلكتروني (للدخول) <span class="text-red-400">*</span></label>
            <input wire:model="email" type="email" class="input font-mono" dir="ltr" maxlength="255">
            @error('email')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label">كلمة المرور @if(!$userId)<span class="text-red-400">*</span>@endif</label>
            <input wire:model="password" type="password" class="input" autocomplete="new-password"
                   placeholder="{{ $userId ? 'اتركها فارغة للإبقاء على الحالية' : '6 أحرف على الأقل' }}">
            @error('password')<p class="field-error">{{ $message }}</p>@enderror
        </div>
    </div>
    <div>
        <label class="flex items-center gap-2 cursor-pointer">
            <input wire:model="is_active" type="checkbox" class="rounded border-[#E2E8F0]">
            <span class="text-sm text-[#1E293B]">نشط (يمكنه تسجيل الدخول)</span>
        </label>
    </div>
    <p class="text-xs text-gray-500 border-t border-[#E2E8F0] pt-3">
        لتعيين سيارة لهذا السائق، أنشئ مخزنًا من نوع «سيارة» واختره من قسم «المخازن والسيارات».
    </p>
</div>

</div>
