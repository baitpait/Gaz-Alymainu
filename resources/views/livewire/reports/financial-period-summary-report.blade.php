<div>
    <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-[#1E293B]">لوحة الفترة المالية</h1>
            <p class="text-sm text-gray-500 mt-1">ملخص المبيعات والمشتريات والتدفق النقدي لكل عملة ضمن الفترة.</p>
        </div>
        @include('livewire.partials.period-report-export-actions', ['pdfExportUrl' => $pdfExportUrl])
    </div>

    @include('livewire.partials.period-report-filters', ['currencyOptions' => $currencyOptions, 'showMethod' => false])

    @if(empty($summary))
    <div class="text-center py-16 text-gray-400">لا توجد حركات في هذه الفترة.</div>
    @else
    <div class="grid grid-cols-1 lg:grid-cols-{{ min(count($summary), 3) }} gap-4">
        @foreach($summary as $cur => $s)
        <div class="card p-5">
            <p class="text-xs font-bold text-gray-400 mb-4 uppercase tracking-widest" dir="ltr">{{ $cur }}</p>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between"><span class="text-gray-500">مبيعات ({{ $s['invoice_count'] }} فاتورة)</span><span class="font-mono text-blue-600" dir="ltr">{{ number_format($s['sales'], 2) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">مشتريات ({{ $s['po_count'] }} أمر)</span><span class="font-mono text-purple-600" dir="ltr">{{ number_format($s['purchases'], 2) }}</span></div>
                <div class="flex justify-between border-t border-dashed pt-2"><span class="text-gray-500">دفعات العملاء</span><span class="font-mono text-green-600" dir="ltr">{{ number_format($s['client_payments'], 2) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">دفعات الموردين</span><span class="font-mono text-red-500" dir="ltr">{{ number_format($s['supplier_payments'], 2) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">مصروفات</span><span class="font-mono text-red-500" dir="ltr">{{ number_format($s['expenses'], 2) }}</span></div>
                <div class="flex justify-between pt-2 border-t font-bold"><span>صافي نقدي</span><span class="font-mono {{ $s['net_cash'] >= 0 ? 'text-[#1B6CA8]' : 'text-red-600' }}" dir="ltr">{{ number_format($s['net_cash'], 2) }}</span></div>
                <div class="flex justify-between pt-2 mt-2 border-t border-dashed"><span class="text-gray-500">ذمم عملاء (حتى {{ $dateTo }})</span><span class="font-mono text-blue-700" dir="ltr">{{ number_format($s['client_receivables'] ?? 0, 2) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">التزام موردين (حتى {{ $dateTo }})</span><span class="font-mono text-purple-700" dir="ltr">{{ number_format($s['supplier_payables'] ?? 0, 2) }}</span></div>
            </div>
            <p class="text-[10px] text-gray-400 mt-3">صافي نقدي = دفعات العملاء − دفعات الموردين − مصروفات. الذمم لحظية حتى «إلى تاريخ».</p>
        </div>
        @endforeach
    </div>
    @endif
</div>
