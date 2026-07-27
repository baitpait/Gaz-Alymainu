<div class="max-w-xl mx-auto">

{{-- ═══ الترويسة ═══ --}}
<div class="mb-4">
    <div class="flex items-center justify-between">
        <h1 class="text-lg font-bold text-[#1E293B]">نقطة البيع</h1>
        <span class="text-xs text-gray-400" dir="ltr">{{ $today }}</span>
    </div>
    @unless($isDriver)
    <select wire:model.live="warehouseId" class="input mt-3 w-full">
        <option value="">— اختر السيارة —</option>
        @foreach($vehicles as $v)
        <option value="{{ $v->id }}">{{ $v->name }} @if($v->driver)— {{ $v->driver->full_name }}@endif</option>
        @endforeach
    </select>
    @endunless
</div>

@if($warehouse)
{{-- ═══ الدوائر على شكل صفّين وعمودين (2×2) ═══ --}}
<div class="grid grid-cols-2 gap-4 mb-5 max-w-[16rem] mx-auto justify-items-center">
    {{-- نقدي اليوم --}}
    <div class="flex flex-col items-center gap-1.5">
        <div class="w-20 h-20 rounded-full flex items-center justify-center border-[3px]"
             style="border-color:#16A34A;background:#16A34A14;">
            <span class="text-lg font-black" style="color:#16A34A" dir="ltr">{{ number_format($cashToday, 0) }}</span>
        </div>
        <span class="text-[11px] text-gray-500 text-center leading-tight">نقدي اليوم</span>
    </div>

    {{-- على الحساب --}}
    <div class="flex flex-col items-center gap-1.5">
        <div class="w-20 h-20 rounded-full flex items-center justify-center border-[3px]"
             style="border-color:#1B6CA8;background:#1B6CA814;">
            <span class="text-lg font-black" style="color:#1B6CA8" dir="ltr">{{ number_format($creditToday, 0) }}</span>
        </div>
        <span class="text-[11px] text-gray-500 text-center leading-tight">على الحساب</span>
    </div>

    {{-- كاش الصندوق --}}
    <div class="flex flex-col items-center gap-1.5">
        <div class="w-20 h-20 rounded-full flex items-center justify-center border-[3px]"
             style="border-color:#1E293B;background:#1E293B14;">
            <span class="text-lg font-black" style="color:#1E293B" dir="ltr">{{ number_format($boxBalance, 0) }}</span>
        </div>
        <span class="text-[11px] text-gray-500 text-center leading-tight">كاش الصندوق</span>
    </div>

    {{-- مجموع التحصيلات --}}
    <div class="flex flex-col items-center gap-1.5">
        <div class="w-20 h-20 rounded-full flex items-center justify-center border-[3px]"
             style="border-color:#E8590C;background:#E8590C14;">
            <span class="text-lg font-black" style="color:#E8590C" dir="ltr">{{ number_format($collectCashToday + $collectChequeToday, 0) }}</span>
        </div>
        <span class="text-[11px] text-gray-500 text-center leading-tight">مجموع التحصيلات</span>
    </div>
</div>

{{-- ═══ الأصناف (عمود واحد) ═══ --}}
@if($items->isEmpty())
<div class="card p-10 text-center text-gray-300">
    <p class="text-sm">لا يوجد مخزون في هذه السيارة.</p>
</div>
@else
<div class="space-y-3">
    @foreach($items as $item)
    @php $p = $item['product']; $stock = $item['quantity']; $price = $item['price']; @endphp
    <div class="card p-4" wire:key="pos-{{ $p->id }}">
        <div class="flex items-center justify-between mb-3">
            <div class="min-w-0">
                <p class="text-xl font-black truncate" style="color:#1B6CA8">{{ $p->name }}</p>
                <p class="text-sm font-semibold text-[#1E293B] mt-0.5">
                    {{ $p->capacity_kg ? $p->capacity_kg.' كغ' : '' }}
                    @if($price !== null)
                    · <span class="text-gray-400">سعر الإدارة:</span>
                    <span class="font-mono" dir="ltr">{{ number_format($price, 2) }} ش</span>
                    @endif
                </p>
            </div>
            <div class="text-center shrink-0 mr-2">
                <p class="text-4xl font-black leading-none {{ $stock > 0 ? 'text-[#16A34A]' : 'text-red-500' }}" dir="ltr">{{ rtrim(rtrim(number_format($stock, 4), '0'), '.') }}</p>
                <p class="text-xs font-semibold text-gray-500 mt-1">المتبقّي</p>
            </div>
        </div>

        {{-- سعر البيع القابل للتعديل (افتراضيًا سعر الإدارة، ويمكن تغييره حسب الطلب) --}}
        <div class="flex items-center gap-2 mb-3">
            <label class="text-xs font-semibold text-gray-500 shrink-0">سعر البيع (ش)</label>
            <input type="number" step="0.01" min="0" dir="ltr" wire:model="price.{{ $p->id }}"
                   class="input h-11 text-center text-lg w-full font-mono">
        </div>

        <div class="flex items-center gap-2 mb-3"
             x-data="{ q: {{ (int) ($qty[$p->id] ?? 1) }} }"
             x-effect="$wire.set('qty.{{ $p->id }}', q, false)"
             @sold.window="if ($event.detail.productId === {{ $p->id }}) q = 0">
            <button type="button" @click="q = Math.max(1, (parseInt(q)||1) - 1)"
                    class="h-11 w-12 shrink-0 rounded-lg text-white text-2xl font-bold leading-none flex items-center justify-center active:scale-95 transition-transform"
                    style="background:#DC2626">−</button>
            <input type="number" min="1" dir="ltr" x-model.number="q"
                   class="input h-11 text-center text-lg w-full font-mono">
            <button type="button" @click="q = (parseInt(q)||0) + 1"
                    class="h-11 w-12 shrink-0 rounded-lg text-white text-2xl font-bold leading-none flex items-center justify-center active:scale-95 transition-transform"
                    style="background:#16A34A">+</button>
        </div>

        <div class="grid grid-cols-2 gap-2">
            <button type="button"
                    wire:click="sell({{ $p->id }}, 'cash')"
                    wire:loading.attr="disabled"
                    @disabled($stock <= 0)
                    class="btn btn-primary bg-green-600 hover:bg-green-700 border-green-600 justify-center h-12 text-base disabled:opacity-40 disabled:cursor-not-allowed">
                بيع نقدي
            </button>
            <button type="button"
                    wire:click="sell({{ $p->id }}, 'credit')"
                    wire:loading.attr="disabled"
                    @disabled($stock <= 0)
                    class="btn btn-primary bg-[#1B6CA8] hover:bg-[#b3901f] border-[#1B6CA8] justify-center h-12 text-base disabled:opacity-40 disabled:cursor-not-allowed">
                على الحساب
            </button>
        </div>
    </div>
    @endforeach
</div>
@endif

@else
<div class="card p-10 text-center text-gray-300">
    <p class="text-sm">
        @if($isDriver)
            لا توجد سيارة مسندة لك بعد. راجع الإدارة.
        @else
            اختر سيارة لعرض المخزون وبدء البيع.
        @endif
    </p>
</div>
@endif

</div>
