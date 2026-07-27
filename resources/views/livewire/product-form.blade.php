<div>

<div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('products.index') }}" wire:navigate
           class="w-9 h-9 flex items-center justify-center rounded-full border border-[#E2E4E9] bg-white text-[#6B7280] hover:bg-[#F3F4F6]">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
        <div>
            <h1 class="text-xl font-bold text-[#3D3D3D]">{{ $productId ? 'تعديل الخدمة' : 'خدمة جديدة' }}</h1>
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

<div class="card p-5 space-y-4 mb-6">
    <div>
        <label class="label">اسم الخدمة <span class="text-red-400">*</span></label>
        <input wire:model="name" type="text" class="input" maxlength="255">
        @error('name')<p class="field-error">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="label">رمز الخدمة</label>
        <input wire:model="product_code" type="text" class="input font-mono" dir="ltr" maxlength="64" placeholder="اختياري — فريد إن وُجد">
        @error('product_code')<p class="field-error">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="label">الوصف</label>
        <textarea wire:model="description" rows="3" class="input" maxlength="5000" placeholder="اختياري"></textarea>
        @error('description')<p class="field-error">{{ $message }}</p>@enderror
    </div>
</div>

<div class="card p-5 space-y-4 mb-6">
    <div>
        <p class="text-sm font-bold text-[#3D3D3D]">مخزون الغاز</p>
        <p class="text-xs text-gray-500 mt-1">فعّل التتبّع لأصناف جرات الغاز لتظهر في المخازن والتسعير اليومي والتحميل.</p>
    </div>
    <label class="flex items-center gap-2 cursor-pointer">
        <input wire:model.live="is_stock_tracked" type="checkbox" class="rounded border-[#E2E4E9]">
        <span class="text-sm text-[#3D3D3D]">تتبّع المخزون (صنف غاز)</span>
    </label>
    @if($is_stock_tracked)
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
    @endif
</div>

<div class="card overflow-hidden mb-6">
    <div class="px-5 py-3 border-b border-[#E2E4E9] bg-[#F9F9FB]">
        <p class="text-sm font-bold text-[#3D3D3D]">الأسعار حسب العملة</p>
        <p class="text-xs text-gray-500 mt-1">لكل عملة: إما تترك الصف فارغًا بالكامل، أو تُدخل <strong>تكلفة الخدمة</strong> و<strong>الحد الأدنى للبيع</strong> و<strong>سعر البيع</strong> معًا. يجب أن يكون الحد الأدنى ≤ سعر البيع.</p>
    </div>
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>العملة</th>
                    <th class="text-left" dir="ltr">تكلفة الخدمة</th>
                    <th class="text-left" dir="ltr">الحد الأدنى للبيع</th>
                    <th class="text-left" dir="ltr">سعر البيع</th>
                </tr>
            </thead>
            <tbody>
                @foreach(\App\Models\Product::billingCurrencies() as $cc)
                <tr wire:key="price-{{ $cc }}">
                    <td class="font-medium text-sm whitespace-nowrap">{{ $labels[$cc] ?? $cc }}</td>
                    <td>
                        <input type="number" step="0.0001" min="0" dir="ltr" class="input font-mono text-sm py-2"
                               wire:model="pricesByCurrency.{{ $cc }}.service_cost_price" placeholder="—">
                        @error("pricesByCurrency.$cc.service_cost_price")<p class="field-error text-xs">{{ $message }}</p>@enderror
                    </td>
                    <td>
                        <input type="number" step="0.0001" min="0" dir="ltr" class="input font-mono text-sm py-2"
                               wire:model="pricesByCurrency.{{ $cc }}.min_sale_price" placeholder="—">
                        @error("pricesByCurrency.$cc.min_sale_price")<p class="field-error text-xs">{{ $message }}</p>@enderror
                    </td>
                    <td>
                        <input type="number" step="0.0001" min="0" dir="ltr" class="input font-mono text-sm py-2"
                               wire:model="pricesByCurrency.{{ $cc }}.sale_price" placeholder="—">
                        @error("pricesByCurrency.$cc.sale_price")<p class="field-error text-xs">{{ $message }}</p>@enderror
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="flex justify-end">
    <button type="button" wire:click="save" wire:loading.attr="disabled" class="btn btn-primary w-full sm:w-auto">حفظ الخدمة</button>
</div>

</div>
