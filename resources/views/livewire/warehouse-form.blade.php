<div>

<div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('warehouses.index') }}" wire:navigate
           class="w-9 h-9 flex items-center justify-center rounded-full border border-[#E2E4E9] bg-white text-[#6B7280] hover:bg-[#F3F4F6]">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
        <div>
            <h1 class="text-xl font-bold text-[#3D3D3D]">{{ $warehouseId ? 'تعديل المخزن' : 'مخزن جديد' }}</h1>
            @if($warehouseId)<p class="text-xs text-gray-400 mt-0.5">رقم #{{ $warehouseId }}</p>@endif
        </div>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('warehouses.index') }}" wire:navigate class="btn btn-secondary" style="text-decoration:none;">إلغاء</a>
        <button type="button" wire:click="save" wire:loading.attr="disabled" class="btn btn-primary">حفظ</button>
    </div>
</div>

<div class="card p-5 space-y-4 mb-6">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="label">اسم المخزن <span class="text-red-400">*</span></label>
            <input wire:model="name" type="text" class="input" maxlength="255">
            @error('name')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label">الرمز</label>
            <input wire:model="code" type="text" class="input font-mono" dir="ltr" maxlength="64" placeholder="اختياري — فريد إن وُجد">
            @error('code')<p class="field-error">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="label">النوع <span class="text-red-400">*</span></label>
            <select wire:model.live="type" class="input">
                @foreach($typeOptions as $val => $label)
                <option value="{{ $val }}">{{ $label }}</option>
                @endforeach
            </select>
            @error('type')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <div class="flex items-end">
            <label class="flex items-center gap-2 cursor-pointer">
                <input wire:model="is_active" type="checkbox" class="rounded border-[#E2E4E9]">
                <span class="text-sm text-[#3D3D3D]">نشط</span>
            </label>
        </div>
    </div>

    @if($type === 'vehicle')
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="label">السائق <span class="text-red-400">*</span></label>
            <select wire:model="assigned_user_id" class="input">
                <option value="">— اختر سائقًا —</option>
                @foreach($drivers as $d)
                <option value="{{ $d->id }}">{{ $d->full_name }}</option>
                @endforeach
            </select>
            @error('assigned_user_id')<p class="field-error">{{ $message }}</p>@enderror
            @if($drivers->isEmpty())
            <p class="text-xs text-amber-600 mt-1">لا يوجد سائقون بعد — أضف سائقًا من قسم «السائقون».</p>
            @endif
        </div>
        <div>
            <label class="label">رقم اللوحة</label>
            <input wire:model="vehicle_plate" type="text" class="input font-mono" dir="ltr" maxlength="32" placeholder="اختياري">
            @error('vehicle_plate')<p class="field-error">{{ $message }}</p>@enderror
        </div>
    </div>
    @endif

    <div>
        <label class="label">ملاحظات</label>
        <textarea wire:model="notes" rows="2" class="input" maxlength="5000" placeholder="اختياري"></textarea>
        @error('notes')<p class="field-error">{{ $message }}</p>@enderror
    </div>
</div>

<div class="card p-5 mb-6">
    <p class="text-sm font-bold text-[#3D3D3D] mb-1">الأصناف المسموحة في هذا المخزن</p>
    <p class="text-xs text-gray-500 mb-3">حدّد أصناف الغاز التي تُخزَّن هنا. يحقق «أكثر من مخزن لأصناف معيّنة».</p>
    @if($products->isEmpty())
        <p class="text-sm text-amber-600">لا توجد أصناف غاز بعد — فعّل «تتبّع المخزون» على الصنف من قسم الأصناف.</p>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
            @foreach($products as $p)
            <label class="flex items-center gap-2 border border-[#E2E4E9] rounded-lg px-3 py-2 cursor-pointer hover:bg-[#F9F9FB]" wire:key="prod-{{ $p->id }}">
                <input type="checkbox" value="{{ $p->id }}" wire:model="allowedProductIds" class="rounded border-[#E2E4E9]">
                <span class="text-sm text-[#3D3D3D]">{{ $p->name }}</span>
            </label>
            @endforeach
        </div>
    @endif
</div>

<div class="flex justify-end">
    <button type="button" wire:click="save" wire:loading.attr="disabled" class="btn btn-primary w-full sm:w-auto">حفظ المخزن</button>
</div>

</div>
