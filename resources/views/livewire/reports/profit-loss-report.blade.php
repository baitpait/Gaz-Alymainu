<div>
    <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-[#1E293B]">قائمة الربح والخسارة</h1>
            <p class="text-sm text-gray-500 mt-1">{{ $modeLabel }} — صافي = مبيعات − تكلفة المباع − مصروفات − رواتب. المخزون أصل منفصل.</p>
        </div>
        @include('livewire.partials.period-report-export-actions', ['pdfExportUrl' => $pdfExportUrl])
    </div>

    <div class="flex flex-wrap gap-2 mb-4 text-sm">
        <a href="{{ route('reports.profit-loss') }}" wire:navigate
           class="px-3 py-1.5 rounded border {{ !$isCash ? 'bg-[#1E293B] text-white border-[#1E293B]' : 'bg-white border-[#E0E0E0] text-gray-600' }}"
           style="text-decoration:none;">كامل (فواتير)</a>
        <a href="{{ route('reports.profit-loss-cash') }}" wire:navigate
           class="px-3 py-1.5 rounded border {{ $isCash ? 'bg-[#1E293B] text-white border-[#1E293B]' : 'bg-white border-[#E0E0E0] text-gray-600' }}"
           style="text-decoration:none;">بدون دين (نقدي)</a>
        <a href="{{ route('reports.profit-loss-ils') }}" wire:navigate
           class="px-3 py-1.5 rounded border bg-white border-[#E0E0E0] text-gray-600"
           style="text-decoration:none;">بالشيكل (FX)</a>
    </div>

    @include('livewire.partials.period-report-filters', ['currencyOptions' => $currencyOptions, 'showMethod' => false])

    <div class="card p-4 mb-4 max-w-md border-r-4 border-[#1B6CA8]">
        <p class="text-xs text-gray-400 font-bold">أصل المخزون (ليس ربحاً)</p>
        <p class="text-2xl font-black text-[#1B6CA8] mt-1 font-mono" dir="ltr">{{ number_format($inventoryAssetTotal, 2) }}</p>
        <p class="text-xs text-gray-400 mt-1">Σ الكمية الحالية × تكلفة الصنف — كل المخازن</p>
    </div>

    @if(empty($rows))
    <div class="text-center py-16 text-gray-400">لا توجد حركات في هذه الفترة.</div>
    @else
    <div class="grid grid-cols-1 lg:grid-cols-{{ min(count($rows), 3) }} gap-4">
        @foreach($rows as $cur => $row)
        <div class="card p-5 border-r-4 {{ $row['net_profit'] >= 0 ? 'border-green-400' : 'border-red-400' }}">
            <p class="text-xs font-bold text-gray-400 mb-4 uppercase tracking-widest" dir="ltr">{{ $cur }}</p>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">
                        @if($isCash)
                            نقدي (دفعات {{ $row['client_payment_count'] }} + تحصيل {{ $row['collection_count'] }} + مبيعات نقدية)
                        @else
                            مبيعات ({{ $row['invoice_count'] }} فاتورة + {{ $row['driver_sale_count'] }} بيع سائق)
                        @endif
                    </span>
                    <span class="font-mono text-blue-600" dir="ltr">{{ number_format($row['sales'], 2) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">تكلفة المباع</span>
                    <span class="font-mono text-purple-600" dir="ltr">{{ number_format($row['cogs'] ?? $row['purchases'], 2) }}</span>
                </div>
                @if(!$isCash && ($row['operational_purchases'] ?? 0) > 0)
                <div class="flex justify-between text-xs">
                    <span class="text-gray-400">مشتريات تشغيلية (مرجع — خارج الصافي)</span>
                    <span class="font-mono text-gray-400" dir="ltr">{{ number_format($row['operational_purchases'], 2) }}</span>
                </div>
                @endif
                <div class="flex justify-between border-t border-dashed pt-2">
                    <span class="text-gray-500">مصروفات</span>
                    <span class="font-mono text-red-500" dir="ltr">{{ number_format($row['expenses'], 2) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">رواتب ({{ $row['salary_count'] }})</span>
                    <span class="font-mono text-red-500" dir="ltr">{{ number_format($row['salaries'], 2) }}</span>
                </div>
                <div class="flex justify-between pt-2 border-t font-bold text-base">
                    <span>صافي الربح/الخسارة</span>
                    <span class="font-mono {{ $row['net_profit'] >= 0 ? 'text-green-600' : 'text-red-600' }}" dir="ltr">
                        {{ $row['net_profit'] >= 0 ? '+' : '' }}{{ number_format($row['net_profit'], 2) }} {{ $cur }}
                    </span>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    <p class="text-xs text-gray-400 mt-4">
        المعادلة: {{ $isCash ? 'إيراد نقدي − تكلفة المباع النقدي − مصروفات − رواتب' : 'مبيعات − تكلفة المباع − مصروفات − رواتب' }}
        — فواتير تقييم المخزون مستثناة. العملة الافتراضية: ILS.
    </p>
    @endif
</div>
