<div>

<div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('stock-movements.index') }}" wire:navigate
           class="w-9 h-9 flex items-center justify-center rounded-full border border-[#E2E8F0] bg-white text-[#6B7280] hover:bg-[#F3F4F6]">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
        <div>
            <h1 class="text-xl font-bold text-[#1E293B]">حركة مخزون جديدة</h1>
            <p class="text-xs text-gray-400 mt-0.5">تحميل / إدخال بالشراء / إرجاع / تسوية</p>
        </div>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('stock-movements.index') }}" wire:navigate class="btn btn-secondary" style="text-decoration:none;">إلغاء</a>
        <button type="button" wire:click="save" wire:loading.attr="disabled" class="btn btn-primary">تسجيل الحركة</button>
    </div>
</div>

<div class="card p-5 space-y-4 mb-6 max-w-2xl">
    <div>
        <label class="label">نوع الحركة <span class="text-red-400">*</span></label>
        <select wire:model.live="type" class="input">
            @foreach($typeOptions as $val => $label)
            <option value="{{ $val }}">{{ $label }}</option>
            @endforeach
        </select>
        @error('type')<p class="field-error">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="label">الصنف <span class="text-red-400">*</span></label>
        <select wire:model="product_id" class="input">
            <option value="">— اختر الصنف —</option>
            @foreach($products as $p)
            <option value="{{ $p->id }}">{{ $p->name }}</option>
            @endforeach
        </select>
        @error('product_id')<p class="field-error">{{ $message }}</p>@enderror
    </div>

    @if($type === 'load')
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="label">من مخزن (ثابت) <span class="text-red-400">*</span></label>
            <select wire:model="from_warehouse_id" class="input">
                <option value="">— اختر المخزن —</option>
                @foreach($fixedWarehouses as $w)<option value="{{ $w->id }}">{{ $w->name }}</option>@endforeach
            </select>
        </div>
        <div>
            <label class="label">إلى سيارة <span class="text-red-400">*</span></label>
            <select wire:model="to_warehouse_id" class="input">
                <option value="">— اختر السيارة —</option>
                @foreach($vehicles as $v)<option value="{{ $v->id }}">{{ $v->name }} @if($v->driver)— {{ $v->driver->full_name }}@endif</option>@endforeach
            </select>
        </div>
    </div>
    @elseif($type === 'return')
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="label">من سيارة <span class="text-red-400">*</span></label>
            <select wire:model="from_warehouse_id" class="input">
                <option value="">— اختر السيارة —</option>
                @foreach($vehicles as $v)<option value="{{ $v->id }}">{{ $v->name }} @if($v->driver)— {{ $v->driver->full_name }}@endif</option>@endforeach
            </select>
        </div>
        <div>
            <label class="label">إلى مخزن (ثابت) <span class="text-red-400">*</span></label>
            <select wire:model="to_warehouse_id" class="input">
                <option value="">— اختر المخزن —</option>
                @foreach($fixedWarehouses as $w)<option value="{{ $w->id }}">{{ $w->name }}</option>@endforeach
            </select>
        </div>
    </div>
    @elseif($type === 'transfer')
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="label">من مخزن <span class="text-red-400">*</span></label>
            <select wire:model="from_warehouse_id" class="input">
                <option value="">— اختر —</option>
                @foreach($allWarehouses as $w)<option value="{{ $w->id }}">{{ $w->name }} ({{ $w->type->label() }})</option>@endforeach
            </select>
        </div>
        <div>
            <label class="label">إلى مخزن <span class="text-red-400">*</span></label>
            <select wire:model="to_warehouse_id" class="input">
                <option value="">— اختر —</option>
                @foreach($allWarehouses as $w)<option value="{{ $w->id }}">{{ $w->name }} ({{ $w->type->label() }})</option>@endforeach
            </select>
        </div>
    </div>
    @elseif($type === 'purchase_in')
    <div>
        <label class="label">إلى مخزن (ثابت) <span class="text-red-400">*</span></label>
        <select wire:model="to_warehouse_id" class="input">
            <option value="">— اختر المخزن —</option>
            @foreach($fixedWarehouses as $w)<option value="{{ $w->id }}">{{ $w->name }}</option>@endforeach
        </select>
    </div>
    @else
    <div>
        <label class="label">المخزن / السيارة <span class="text-red-400">*</span></label>
        <select wire:model="to_warehouse_id" class="input">
            <option value="">— اختر —</option>
            @foreach($fixedWarehouses as $w)<option value="{{ $w->id }}">{{ $w->name }}</option>@endforeach
            @foreach($vehicles as $v)<option value="{{ $v->id }}">{{ $v->name }} (سيارة)</option>@endforeach
        </select>
    </div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="label">
                @if($type === 'adjustment') الكمية الجديدة (الرصيد بعد الجرد) @else الكمية @endif
                <span class="text-red-400">*</span>
            </label>
            <input wire:model="quantity" type="number" step="0.0001" min="0" dir="ltr" class="input font-mono">
            @error('quantity')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label">التاريخ <span class="text-red-400">*</span></label>
            <input wire:model="moved_at" type="date" class="input" dir="ltr">
            @error('moved_at')<p class="field-error">{{ $message }}</p>@enderror
        </div>
    </div>

    <div>
        <label class="label">ملاحظات</label>
        <textarea wire:model="notes" rows="2" class="input" maxlength="2000" placeholder="اختياري"></textarea>
    </div>
</div>

</div>
