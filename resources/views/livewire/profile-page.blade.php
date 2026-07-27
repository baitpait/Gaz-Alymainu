<div>
    <div class="mb-6">
        <h1 class="text-xl font-bold text-[#1E293B]">الملف الشخصي</h1>
        <p class="text-sm text-gray-400 mt-0.5">عرض بيانات الحساب وتغيير كلمة المرور</p>
    </div>

    <div class="card p-5 sm:p-6 max-w-lg mb-5">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-12 h-12 rounded-full bg-[#1B6CA8]/15 flex items-center justify-center text-[#1B6CA8] font-bold text-lg">
                {{ mb_substr($full_name, 0, 1) }}
            </div>
            <div>
                <div class="text-sm font-bold text-[#1E293B]">{{ $full_name }}</div>
                <div class="text-xs text-gray-400">{{ $role_label }}</div>
            </div>
        </div>
        <div class="space-y-3">
            <div>
                <label class="label">الاسم الكامل</label>
                <input type="text" class="input bg-gray-50" value="{{ $full_name }}" disabled>
            </div>
            <div>
                <label class="label">البريد الإلكتروني</label>
                <input type="email" class="input bg-gray-50" dir="ltr" value="{{ $email }}" disabled>
            </div>
        </div>
    </div>

    <div class="card p-5 sm:p-6 max-w-lg">
        <h2 class="text-sm font-bold text-[#1E293B] mb-4">تغيير كلمة المرور</h2>
        <form wire:submit="updatePassword" class="space-y-4">
            <div>
                <label class="label">كلمة المرور الحالية <span class="text-red-400">*</span></label>
                <input wire:model="current_password" type="password" class="input" dir="ltr"
                       autocomplete="current-password" placeholder="••••••••">
                @error('current_password')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label">كلمة المرور الجديدة <span class="text-red-400">*</span></label>
                <input wire:model="password" type="password" class="input" dir="ltr"
                       autocomplete="new-password" placeholder="6 أحرف على الأقل">
                @error('password')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label">تأكيد كلمة المرور <span class="text-red-400">*</span></label>
                <input wire:model="password_confirmation" type="password" class="input" dir="ltr"
                       autocomplete="new-password" placeholder="أعد إدخال كلمة المرور">
                @error('password_confirmation')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <button type="submit" wire:loading.attr="disabled" class="btn btn-primary w-full sm:w-auto min-w-[140px]">
                <span wire:loading.remove wire:target="updatePassword">حفظ كلمة المرور</span>
                <span wire:loading wire:target="updatePassword">جاري الحفظ...</span>
            </button>
        </form>
    </div>
</div>
