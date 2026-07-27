<x-layouts.app title="تفاصيل الدفعة">
<div class="max-w-2xl mx-auto">

    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('payments.index') }}" wire:navigate
           style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;border-radius:8px;background:#fff;border:1px solid #E2E8F0;color:#9CA3AF;text-decoration:none;transition:color .15s;"
           onmouseover="this.style.color='#1E293B'" onmouseout="this.style.color='#9CA3AF'">
            <svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </a>
        <div>
            <h1 class="text-xl font-bold text-[#1E293B]">تفاصيل الدفعة</h1>
            <p class="text-sm text-gray-400 mt-0.5">{{ $payment->client?->displayName() ?? '—' }}</p>
        </div>
    </div>

    <div class="card p-6">
        <div class="flex items-center gap-4 mb-6 pb-6 border-b border-[#E2E8F0]">
            <div class="w-12 h-12 rounded-xl bg-green-50 flex items-center justify-center shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-green-600" dir="ltr">
                    {{ number_format((float)$payment->amount, 2) }}
                    <span class="text-sm font-normal text-gray-400">{{ $payment->currency_code }}</span>
                </p>
                <p class="text-sm text-gray-500 mt-0.5">{{ $payment->client?->displayName() ?? '—' }}</p>
            </div>
        </div>

        @php $methods = ['cash'=>'نقدي','bank'=>'بنكي','check'=>'شيك','transfer'=>'تحويل']; @endphp
        <dl class="divide-y divide-[#E2E8F0]">
            <div class="flex justify-between py-3">
                <dt class="text-sm text-gray-500">العميل</dt>
                <dd class="text-sm font-semibold text-[#1E293B]">{{ $payment->client?->displayName() ?? '—' }}</dd>
            </div>
            <div class="flex justify-between py-3">
                <dt class="text-sm text-gray-500">تاريخ الدفع</dt>
                <dd class="text-sm font-medium text-[#1E293B]" dir="ltr">{{ $payment->paid_at?->format('Y-m-d') ?? '—' }}</dd>
            </div>
            <div class="flex justify-between py-3">
                <dt class="text-sm text-gray-500">طريقة الدفع</dt>
                <dd><span class="badge badge-blue">{{ $methods[$payment->method] ?? $payment->method ?? '—' }}</span></dd>
            </div>
            @if($payment->bank_reference)
            <div class="flex justify-between py-3">
                <dt class="text-sm text-gray-500">رقم المرجع</dt>
                <dd class="text-sm font-mono font-medium text-[#1E293B]" dir="ltr">{{ $payment->bank_reference }}</dd>
            </div>
            @endif
            @if($payment->notes)
            <div class="py-3">
                <dt class="text-sm text-gray-500 mb-1">ملاحظات</dt>
                <dd class="text-sm text-[#1E293B] bg-amber-50 rounded-lg p-3">{{ $payment->notes }}</dd>
            </div>
            @endif
        </dl>

        <div class="flex justify-end gap-2 pt-5 mt-2 border-t border-[#E2E8F0]">
            <a href="{{ route('payments.index') }}" wire:navigate class="btn btn-secondary" style="text-decoration:none;">رجوع</a>
            <x-document-export-buttons
                :print-url="route('payments.print', $payment)"
                :pdf-url="route('payments.pdf', $payment)"
            />
            @if(auth()->user()->isAccountant())
            <a href="{{ route('payments.edit', $payment->id) }}" wire:navigate class="btn btn-primary" style="text-decoration:none;">تعديل</a>
            @endif
        </div>
    </div>

</div>
</x-layouts.app>
