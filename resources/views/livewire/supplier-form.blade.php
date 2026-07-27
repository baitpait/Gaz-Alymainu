<div>

{{-- ── شريط العنوان العلوي ── --}}
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;">
    <div style="display:flex;align-items:center;gap:12px;">
        <a href="{{ route('suppliers.index') }}" wire:navigate
           style="width:36px;height:36px;display:flex;align-items:center;justify-content:center;border-radius:50%;border:1px solid #E2E8F0;background:#fff;color:#6B7280;text-decoration:none;"
           onmouseover="this.style.background='#F3F4F6'" onmouseout="this.style.background='#fff'">
            <svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
        <div>
            <h1 style="font-size:18px;font-weight:800;color:#1E293B;line-height:1.2;">
                {{ $supplierId ? 'تعديل بيانات المورد' : 'إضافة مورد جديد' }}
            </h1>
        </div>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="{{ route('suppliers.index') }}" wire:navigate class="btn btn-secondary" style="font-size:13px;text-decoration:none;">إلغاء</a>
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
            <label class="label">اسم الشركة</label>
            <input wire:model="business_name" type="text" class="input" placeholder="اسم الشركة أو المؤسسة">
            @error('business_name')<p class="field-error">{{ $message }}</p>@enderror
        </div>

        <div style="display:flex;gap:16px;">
            <div style="flex:1;">
                <label class="label">الاسم الأول</label>
                <input wire:model="first_name" type="text" class="input">
            </div>
            <div style="flex:1;">
                <label class="label">الاسم الأخير</label>
                <input wire:model="last_name" type="text" class="input">
            </div>
        </div>

        <p style="font-size:12px;color:#9CA3AF;margin-top:-10px;">يجب إدخال اسم الشركة أو الاسم الشخصي على الأقل</p>

        <div style="display:flex;gap:16px;">
            <div style="flex:1;">
                <label class="label">البريد الإلكتروني</label>
                <input wire:model="email" type="email" dir="ltr" class="input">
                @error('email')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div style="flex:1;">
                <label class="label">الهاتف الرئيسي</label>
                <input wire:model="phone_primary" type="tel" dir="ltr" class="input">
                @error('phone_primary')<p class="field-error">{{ $message }}</p>@enderror
            </div>
        </div>

        <div style="display:flex;gap:16px;">
            <div style="flex:1;">
                <label class="label">الهاتف الثانوي</label>
                <input wire:model="phone_secondary" type="tel" dir="ltr" class="input">
            </div>
            <div style="flex:1;">
                <label class="label">المدينة</label>
                <input wire:model="city" type="text" class="input">
            </div>
        </div>

        <div>
            <label class="label">ملاحظات</label>
            <textarea wire:model="notes" rows="3" class="input"></textarea>
        </div>

    </div>
</div>

</div>
