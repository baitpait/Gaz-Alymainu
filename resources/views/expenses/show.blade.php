<x-layouts.app title="تفاصيل المصروف">
<div class="max-w-2xl mx-auto">

    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('expenses.index') }}" wire:navigate
           style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;border-radius:8px;background:#fff;border:1px solid #E2E8F0;color:#9CA3AF;text-decoration:none;transition:color .15s;"
           onmouseover="this.style.color='#1E293B'" onmouseout="this.style.color='#9CA3AF'">
            <svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </a>
        <div>
            <h1 class="text-xl font-bold text-[#1E293B]">تفاصيل المصروف</h1>
            <p class="text-sm text-gray-400 mt-0.5">{{ $expense->description }}</p>
        </div>
    </div>

    <div class="card p-6">
        <div class="flex items-center gap-4 mb-6 pb-6 border-b border-[#E2E8F0]">
            <div class="w-12 h-12 rounded-xl bg-red-50 flex items-center justify-center shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"/></svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-red-500" dir="ltr">
                    {{ number_format((float)$expense->amount, 2) }}
                    <span class="text-sm font-normal text-gray-400">{{ $expense->currency_code }}</span>
                </p>
                <p class="text-sm text-gray-500 mt-0.5">{{ $expense->description }}</p>
            </div>
        </div>

        <dl class="divide-y divide-[#E2E8F0]">
            <div class="flex justify-between py-3">
                <dt class="text-sm text-gray-500">الوصف</dt>
                <dd class="text-sm font-semibold text-[#1E293B]">{{ $expense->description }}</dd>
            </div>
            <div class="flex justify-between py-3">
                <dt class="text-sm text-gray-500">التاريخ</dt>
                <dd class="text-sm font-medium text-[#1E293B]" dir="ltr">{{ $expense->expense_date?->format('Y-m-d') ?? '—' }}</dd>
            </div>
            <div class="flex justify-between py-3">
                <dt class="text-sm text-gray-500">المبلغ</dt>
                <dd class="text-sm font-mono font-bold text-red-500" dir="ltr">{{ number_format((float)$expense->amount, 2) }} {{ $expense->currency_code }}</dd>
            </div>
            @if($expense->notes)
            <div class="py-3">
                <dt class="text-sm text-gray-500 mb-1">ملاحظات</dt>
                <dd class="text-sm text-[#1E293B] bg-amber-50 rounded-lg p-3">{{ $expense->notes }}</dd>
            </div>
            @endif
        </dl>

        <div class="flex justify-end gap-2 pt-5 mt-2 border-t border-[#E2E8F0]">
            <a href="{{ route('expenses.index') }}" wire:navigate class="btn btn-secondary" style="text-decoration:none;">رجوع</a>
            @if(auth()->user()->isAccountant())
            <a href="{{ route('expenses.edit', $expense->id) }}" wire:navigate class="btn btn-primary" style="text-decoration:none;">تعديل</a>
            @endif
        </div>
    </div>

</div>
</x-layouts.app>
