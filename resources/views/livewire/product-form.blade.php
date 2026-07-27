<div>

<div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('products.index') }}" wire:navigate
           class="w-9 h-9 flex items-center justify-center rounded-full border border-[#E2E8F0] bg-white text-[#6B7280] hover:bg-[#F3F4F6]">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
        <div>
            <h1 class="text-xl font-bold text-[#1E293B]">{{ $productId ? 'تعديل منتج' : 'منتج جديد' }}</h1>
            @if($productId)
            <p class="text-xs text-gray-400 mt-0.5">رقم #{{ $productId }}</p>
            @endif
        </div>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('products.index') }}" wire:navigate class="btn btn-secondary" style="text-decoration:none;">إلغاء</a>
        <button type="button" wire:click="save" wire:loading.attr="disabled" class="btn btn-primary">حفظ</button>
    </div>
</div>

{{-- 1) بيانات المنتج --}}
<div class="card p-5 space-y-4 mb-6">
    <div>
        <p class="text-sm font-bold text-[#1E293B]">بيانات المنتج</p>
        <p class="text-xs text-gray-500 mt-1">اسم ورمز المنتج الظاهران في المخزون ونقطة البيع.</p>
    </div>
    <div>
        <label class="label">اسم المنتج <span class="text-red-400">*</span></label>
        <input wire:model="name" type="text" class="input" maxlength="255" placeholder="مثال: جرة غاز 12 كغ">
        @error('name')<p class="field-error">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="label">رمز المنتج</label>
        <input wire:model="product_code" type="text" class="input font-mono" dir="ltr" maxlength="64" placeholder="اختياري — فريد إن وُجد">
        @error('product_code')<p class="field-error">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="label">الوصف</label>
        <textarea wire:model="description" rows="3" class="input" maxlength="5000" placeholder="اختياري"></textarea>
        @error('description')<p class="field-error">{{ $message }}</p>@enderror
    </div>
</div>

{{-- 2) المخزون --}}
<div class="card p-5 space-y-4 mb-6">
    <div>
        <p class="text-sm font-bold text-[#1E293B]">المخزون</p>
        <p class="text-xs text-gray-500 mt-1">وحدة وسعة الجرة، وهل يظهر الصنف في المخازن والتحميل.</p>
    </div>
    <label class="flex items-center gap-2 cursor-pointer">
        <input wire:model="is_stock_tracked" type="checkbox" class="rounded border-[#E2E8F0]">
        <span class="text-sm text-[#1E293B]">يُحسب في المخازن</span>
    </label>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
            <label class="label">الوحدة</label>
            <input wire:model="unit" type="text" class="input" maxlength="32" placeholder="مثال: جرة">
            @error('unit')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label">السعة (كغ)</label>
            <input wire:model="capacity_kg" type="number" step="0.01" min="0" dir="ltr" class="input font-mono" placeholder="مثال: 12">
            @error('capacity_kg')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label">التصنيف</label>
            <input wire:model="category" type="text" class="input" maxlength="64" placeholder="اختياري">
            @error('category')<p class="field-error">{{ $message }}</p>@enderror
        </div>
    </div>
</div>

{{-- 3) التسعير بالشيكل فقط --}}
<div class="card p-5 space-y-4 mb-6">
    <div>
        <p class="text-sm font-bold text-[#1E293B]">التسعير (شيكل ₪)</p>
        <p class="text-xs text-gray-500 mt-1">سعر البيع بالشيكل فقط. يجب أن يكون الحد الأدنى ≤ سعر البيع.</p>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
            <label class="label">تكلفة المنتج (₪) <span class="text-red-400">*</span></label>
            <input wire:model="service_cost_price" type="number" step="0.01" min="0" dir="ltr"
                   class="input font-mono" placeholder="0.00">
            @error('service_cost_price')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label">الحد الأدنى للبيع (₪) <span class="text-red-400">*</span></label>
            <input wire:model="min_sale_price" type="number" step="0.01" min="0" dir="ltr"
                   class="input font-mono" placeholder="0.00">
            @error('min_sale_price')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label">سعر البيع (₪) <span class="text-red-400">*</span></label>
            <input wire:model="sale_price" type="number" step="0.01" min="0" dir="ltr"
                   class="input font-mono" placeholder="0.00">
            @error('sale_price')<p class="field-error">{{ $message }}</p>@enderror
        </div>
    </div>
</div>

<div class="flex justify-end">
    <button type="button" wire:click="save" wire:loading.attr="disabled" class="btn btn-primary w-full sm:w-auto">حفظ المنتج</button>
</div>

</div>
