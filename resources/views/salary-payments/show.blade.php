<x-layouts.app title="تفاصيل الراتب">
@php
    $salaryPayment->load('employee');
@endphp
<div class="max-w-2xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('salary-payments.index') }}" wire:navigate
           style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;border-radius:8px;background:#fff;border:1px solid #E2E8F0;color:#9CA3AF;text-decoration:none;"
           onmouseover="this.style.color='#1E293B'" onmouseout="this.style.color='#9CA3AF'">
            <svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </a>
        <div>
            <h1 class="text-xl font-bold text-[#1E293B]">تفاصيل الراتب</h1>
            <p class="text-sm text-gray-400 mt-0.5">{{ $salaryPayment->employee?->displayName() }} — {{ $salaryPayment->periodLabel() }}</p>
        </div>
    </div>

    <div class="card p-6">
        <p class="text-2xl font-bold text-[#1B6CA8] mb-6" dir="ltr">
            {{ number_format((float)$salaryPayment->net_amount, 2) }}
            <span class="text-sm font-normal text-gray-400">{{ $salaryPayment->currency_code }}</span>
        </p>

        <dl class="divide-y divide-[#E2E8F0] text-sm">
            <div class="flex justify-between py-3"><dt class="text-gray-500">الأساسي</dt><dd class="font-mono" dir="ltr">{{ number_format((float)$salaryPayment->base_amount, 2) }}</dd></div>
            <div class="flex justify-between py-3"><dt class="text-gray-500">مكافأة</dt><dd class="font-mono" dir="ltr">{{ number_format((float)$salaryPayment->bonus_amount, 2) }}</dd></div>
            <div class="flex justify-between py-3"><dt class="text-gray-500">خصم</dt><dd class="font-mono" dir="ltr">{{ number_format((float)$salaryPayment->deduction_amount, 2) }}</dd></div>
            <div class="flex justify-between py-3"><dt class="text-gray-500">الحالة</dt><dd>{{ App\Models\SalaryPayment::statusLabel($salaryPayment->status) }}</dd></div>
            @if($salaryPayment->paid_at)
            <div class="flex justify-between py-3"><dt class="text-gray-500">تاريخ الدفع</dt><dd dir="ltr">{{ $salaryPayment->paid_at->format('Y-m-d') }}</dd></div>
            @endif
            @if($salaryPayment->notes)
            <div class="py-3"><dt class="text-gray-500 mb-1">ملاحظات</dt><dd class="bg-amber-50 rounded-lg p-3">{{ $salaryPayment->notes }}</dd></div>
            @endif
        </dl>

        <div class="flex justify-end gap-2 pt-5 mt-2 border-t border-[#E2E8F0]">
            <a href="{{ route('salary-payments.index') }}" wire:navigate class="btn btn-secondary" style="text-decoration:none;">رجوع</a>
            @can('update', $salaryPayment)
            <a href="{{ route('salary-payments.edit', $salaryPayment) }}" wire:navigate class="btn btn-primary" style="text-decoration:none;">تعديل</a>
            @endcan
        </div>
    </div>
</div>
</x-layouts.app>
