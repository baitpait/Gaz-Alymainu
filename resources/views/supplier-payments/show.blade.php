<x-layouts.app title="تفاصيل دفعة المورد">
<div class="max-w-2xl mx-auto">

    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('supplier-payments.index', ['sp_supplier' => $supplierPayment->supplier_id]) }}" wire:navigate
           class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-[#E2E8F0] text-[#9CA3AF] hover:text-[#1E293B] transition"
           style="text-decoration:none;">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </a>
        <div>
            <h1 class="text-xl font-bold text-[#1E293B]">تفاصيل دفعة المورد</h1>
            <p class="text-sm text-gray-400 mt-0.5">{{ $supplierPayment->supplier?->displayName() ?? '—' }}</p>
        </div>
    </div>

    <div class="card p-6">
        <div class="flex items-center gap-4 mb-6 pb-6 border-b border-[#E2E8F0]">
            <div class="w-12 h-12 rounded-xl bg-purple-50 flex items-center justify-center shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-purple-700" dir="ltr">
                    {{ number_format((float) $supplierPayment->amount, 2) }}
                    <span class="text-sm font-normal text-gray-400">{{ $supplierPayment->currency_code }}</span>
                </p>
            </div>
        </div>

        @php $methods = ['cash'=>'نقدي','bank'=>'بنكي','check'=>'شيك','transfer'=>'تحويل']; @endphp
        <dl class="divide-y divide-[#E2E8F0]">
            <div class="flex justify-between py-3">
                <dt class="text-sm text-gray-500">المورد</dt>
                <dd class="text-sm font-semibold text-[#1E293B]">{{ $supplierPayment->supplier?->displayName() ?? '—' }}</dd>
            </div>
            <div class="flex justify-between py-3">
                <dt class="text-sm text-gray-500">تاريخ الدفع</dt>
                <dd class="text-sm font-medium text-[#1E293B]" dir="ltr">{{ $supplierPayment->paid_at?->format('Y-m-d') ?? '—' }}</dd>
            </div>
            <div class="flex justify-between py-3">
                <dt class="text-sm text-gray-500">طريقة الدفع</dt>
                <dd><span class="badge badge-blue">{{ $methods[$supplierPayment->method] ?? ($supplierPayment->method ?: '—') }}</span></dd>
            </div>
            @if($supplierPayment->bank_reference)
            <div class="flex justify-between py-3">
                <dt class="text-sm text-gray-500">رقم المرجع</dt>
                <dd class="text-sm font-mono font-medium text-[#1E293B]" dir="ltr">{{ $supplierPayment->bank_reference }}</dd>
            </div>
            @endif
            @if($supplierPayment->notes)
            <div class="py-3">
                <dt class="text-sm text-gray-500 mb-1">ملاحظات</dt>
                <dd class="text-sm text-[#1E293B] bg-amber-50 rounded-lg p-3 whitespace-pre-wrap">{{ $supplierPayment->notes }}</dd>
            </div>
            @endif
        </dl>

        <div class="flex justify-end gap-2 pt-5 mt-2 border-t border-[#E2E8F0]">
            <a href="{{ route('supplier-payments.index', ['sp_supplier' => $supplierPayment->supplier_id]) }}" wire:navigate class="btn btn-secondary" style="text-decoration:none;">رجوع</a>
            <x-document-export-buttons
                :print-url="route('supplier-payments.print', $supplierPayment)"
                :pdf-url="route('supplier-payments.pdf', $supplierPayment)"
            />
            @if(auth()->user()->isAccountant())
            <a href="{{ route('supplier-payments.edit', $supplierPayment->id) }}" wire:navigate class="btn btn-primary" style="text-decoration:none;">تعديل</a>
            @endif
        </div>
    </div>

</div>
</x-layouts.app>
