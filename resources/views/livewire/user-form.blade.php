<div>

{{-- ── شريط العنوان العلوي ── --}}
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;">
    <div style="display:flex;align-items:center;gap:12px;">
        <a href="{{ route('users.index') }}" wire:navigate
           style="width:36px;height:36px;display:flex;align-items:center;justify-content:center;border-radius:50%;border:1px solid #E2E8F0;background:#fff;color:#6B7280;text-decoration:none;"
           onmouseover="this.style.background='#F3F4F6'" onmouseout="this.style.background='#fff'">
            <svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
        <div>
            <h1 style="font-size:18px;font-weight:800;color:#1E293B;line-height:1.2;">
                {{ $userId ? 'تعديل المستخدم' : 'إضافة مستخدم جديد' }}
            </h1>
        </div>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="{{ route('users.index') }}" wire:navigate class="btn btn-secondary" style="font-size:13px;text-decoration:none;">إلغاء</a>
        <button wire:click="save" wire:loading.attr="disabled" class="btn btn-primary" style="font-size:13px;min-width:120px;">
            <svg wire:loading wire:target="save" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
            </svg>
            <span wire:loading.remove wire:target="save">حفظ</span>
            <span wire:loading wire:target="save">جاري الحفظ...</span>
        </button>
    </div>
</div>

<div class="card" style="max-width:680px;margin:0 auto;padding:28px;">
    <div style="display:flex;flex-direction:column;gap:18px;">

        <div>
            <label class="label">الاسم الكامل <span class="text-red-400">*</span></label>
            <input wire:model="full_name" type="text" class="input" placeholder="محمد أحمد">
            @error('full_name')<p class="field-error">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="label">البريد الإلكتروني <span class="text-red-400">*</span></label>
            <input wire:model="email" type="email" dir="ltr" class="input" placeholder="user@example.com">
            @error('email')<p class="field-error">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="label">
                كلمة المرور
                @if($userId)<span class="text-gray-400 font-normal text-xs">(اتركها فارغة للإبقاء على الحالية)</span>@else<span class="text-red-400">*</span>@endif
            </label>
            <input wire:model="password" type="password" dir="ltr" class="input" placeholder="6 أحرف على الأقل">
            @error('password')<p class="field-error">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="label">الصلاحية <span class="text-red-400">*</span></label>
            <div style="display:flex;gap:12px;margin-top:6px;">

                <label style="flex:1;cursor:pointer;">
                    <input type="radio" wire:model="role" value="viewer" class="sr-only peer">
                    <div style="border:2px solid #E2E8F0;border-radius:12px;padding:12px;text-align:center;transition:all .15s;cursor:pointer;"
                         class="peer-checked:border-gray-400 peer-checked:bg-gray-50">
                        <svg xmlns="http://www.w3.org/2000/svg" style="width:20px;height:20px;margin:0 auto 4px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        <p style="font-size:12px;font-weight:700;color:#4B5563;">مشاهد</p>
                        <p style="font-size:11px;color:#9CA3AF;margin-top:2px;">عرض فقط</p>
                    </div>
                </label>

                <label style="flex:1;cursor:pointer;">
                    <input type="radio" wire:model="role" value="accountant" class="sr-only peer">
                    <div style="border:2px solid #E2E8F0;border-radius:12px;padding:12px;text-align:center;transition:all .15s;cursor:pointer;"
                         class="peer-checked:border-blue-400 peer-checked:bg-blue-50">
                        <svg xmlns="http://www.w3.org/2000/svg" style="width:20px;height:20px;margin:0 auto 4px;color:#60A5FA;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 19h16a2 2 0 002-2V7a2 2 0 00-2-2H4a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <p style="font-size:12px;font-weight:700;color:#2563EB;">محاسب</p>
                        <p style="font-size:11px;color:#9CA3AF;margin-top:2px;">إضافة وتعديل</p>
                    </div>
                </label>

                <label style="flex:1;cursor:pointer;">
                    <input type="radio" wire:model="role" value="manager" class="sr-only peer">
                    <div style="border:2px solid #E2E8F0;border-radius:12px;padding:12px;text-align:center;transition:all .15s;cursor:pointer;"
                         class="peer-checked:border-[#1B6CA8] peer-checked:bg-amber-50">
                        <svg xmlns="http://www.w3.org/2000/svg" style="width:20px;height:20px;margin:0 auto 4px;color:#1B6CA8;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        <p style="font-size:12px;font-weight:700;color:#1B6CA8;">مدير</p>
                        <p style="font-size:11px;color:#9CA3AF;margin-top:2px;">صلاحيات كاملة</p>
                    </div>
                </label>

            </div>
            @error('role')<p class="field-error mt-1">{{ $message }}</p>@enderror
        </div>

        <div style="display:flex;align-items:center;justify-content:space-between;padding:14px;background:#F9F9FB;border-radius:12px;">
            <div>
                <p style="font-size:14px;font-weight:600;color:#1E293B;">تفعيل الحساب</p>
                <p style="font-size:12px;color:#9CA3AF;margin-top:2px;">المستخدم المعطّل لا يستطيع تسجيل الدخول</p>
            </div>
            <button type="button" wire:click="$toggle('is_active')"
                    style="position:relative;display:inline-flex;height:24px;width:44px;align-items:center;border-radius:999px;border:none;cursor:pointer;transition:background-color .2s;background:{{ $is_active ? '#1B6CA8' : '#D1D5DB' }};">
                <span style="display:inline-block;height:16px;width:16px;border-radius:50%;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.2);transition:transform .2s;transform:{{ $is_active ? 'translateX(-24px)' : 'translateX(-4px)' }};"></span>
            </button>
        </div>

    </div>
</div>

</div>
