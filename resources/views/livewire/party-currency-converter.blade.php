<div>
    @if(count($availableCurrencies) > 0)
    <button type="button" wire:click="openModal"
            class="px-4 py-2 text-sm bg-white border border-[#E0E0E0] rounded hover:bg-[#F5F5F5] font-medium text-[#DC2626]">
        تحويل العملة
    </button>
    @endif

    @if($showModal)
    <div class="fixed inset-0 z-[80] flex items-center justify-center p-4" style="background:rgba(0,0,0,.45);" wire:click.self="closeModal">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6" wire:click.stop role="dialog" aria-modal="true">
            <h3 class="text-lg font-bold text-[#1E293B] mb-1">تحويل العملة</h3>
            <p class="text-xs text-gray-500 mb-4">
                يغيّر <strong>رمز العملة</strong> فقط في كل الفواتير (بما فيها المسودات) والدفعات والتسويات.
                <strong>المبالغ تبقى كما هي</strong> — بدون سعر صرف.
            </p>

            <div class="space-y-3">
                <div>
                    <label class="label">من عملة</label>
                    <select wire:model.live="fromCurrency" class="input select w-full">
                        @foreach($availableCurrencies as $code)
                        <option value="{{ $code }}">{{ $code }}</option>
                        @endforeach
                    </select>
                    @error('fromCurrency')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label">إلى عملة</label>
                    <select wire:model.live="toCurrency" class="input select w-full">
                        @foreach($billingCurrencies as $code)
                        @if($code !== $fromCurrency)
                        <option value="{{ $code }}">{{ $code }}</option>
                        @endif
                        @endforeach
                    </select>
                    @error('toCurrency')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>

            @if($preview !== null)
            <div class="mt-4 p-3 bg-[#FAFAFA] border border-[#E0E0E0] rounded-lg text-sm space-y-1">
                <p class="font-semibold text-[#1E293B] mb-2">معاينة التحويل</p>
                <p>{{ $documentLabel }}: <span class="font-mono">{{ $preview['invoices'] }}</span></p>
                <p>دفعات: <span class="font-mono">{{ $preview['payments'] }}</span></p>
                <p>تسويات: <span class="font-mono">{{ $preview['adjustments'] }}</span></p>
                <p class="pt-2 border-t border-[#E0E0E0] font-bold">الإجمالي: <span class="font-mono">{{ $preview['total'] }}</span> سجل</p>
                @if($preview['total'] === 0)
                <p class="text-[#DC2626] text-xs mt-1">لا توجد سجلات للتحويل.</p>
                @endif
            </div>
            @endif

            <div class="flex gap-2 mt-6 justify-end flex-wrap">
                <button type="button" wire:click="closeModal" class="btn btn-secondary">إلغاء</button>
                <button type="button" wire:click="runPreview" wire:loading.attr="disabled" class="btn btn-secondary">
                    معاينة
                </button>
                <button type="button" wire:click="applyConversion" wire:loading.attr="disabled"
                        class="btn btn-primary"
                        @if($preview === null || ($preview['total'] ?? 0) === 0) disabled @endif>
                    تنفيذ التحويل
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
