<div>
    <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-[#1E293B]">الربح والخسارة بالشيكل</h1>
            <p class="text-sm text-gray-500 mt-1">تحويل بأسعار بنك إسرائيل الיציג — أساس الحساب: {{ $basisLabel }}.</p>
        </div>
        @include('livewire.partials.period-report-export-actions', ['pdfExportUrl' => $pdfExportUrl])
    </div>

    <div class="flex flex-wrap gap-2 mb-4 text-sm">
        <a href="{{ route('reports.profit-loss') }}" wire:navigate class="px-3 py-1.5 rounded border bg-white border-[#E0E0E0] text-gray-600" style="text-decoration:none;">كامل (فواتير)</a>
        <a href="{{ route('reports.profit-loss-cash') }}" wire:navigate class="px-3 py-1.5 rounded border bg-white border-[#E0E0E0] text-gray-600" style="text-decoration:none;">بدون دين (نقدي)</a>
        <a href="{{ route('reports.profit-loss-ils') }}" wire:navigate class="px-3 py-1.5 rounded border bg-[#1E293B] text-white border-[#1E293B]" style="text-decoration:none;">بالشيكل (FX)</a>
    </div>

    <div class="flex gap-2 mb-4 text-xs">
        <a href="{{ route('reports.profit-loss-ils', ['basis' => 'accrual']) }}" wire:navigate
           class="px-2 py-1 rounded {{ $basisMode === 'accrual' ? 'bg-amber-100 text-amber-800' : 'text-gray-500' }}"
           style="text-decoration:none;">أساس: فواتير</a>
        <a href="{{ route('reports.profit-loss-ils', ['basis' => 'cash']) }}" wire:navigate
           class="px-2 py-1 rounded {{ $basisMode === 'cash' ? 'bg-amber-100 text-amber-800' : 'text-gray-500' }}"
           style="text-decoration:none;">أساس: نقدي</a>
    </div>

    @include('livewire.partials.period-report-filters', ['currencyOptions' => $currencyOptions, 'showMethod' => false])

    @if(!empty($totals['error']))
    <div class="card p-4 text-sm text-red-600 mb-4">{{ $totals['error'] }}</div>
    @endif

    @if(!empty($sourceRows))
    <div class="card p-5 mb-4 max-w-lg">
        <p class="text-xs text-gray-400 mb-3">مجمّع بـ ILS (سعر تاريخ {{ $totals['rate_date'] ?? $dateTo }})</p>
        <div class="space-y-2 text-sm">
            <div class="flex justify-between"><span class="text-gray-500">إيرادات/مبيعات</span><span class="font-mono text-blue-600" dir="ltr">{{ number_format($totals['sales'] ?? 0, 2) }} ILS</span></div>
            <div class="flex justify-between"><span class="text-gray-500">تكلفة المباع</span><span class="font-mono text-purple-600" dir="ltr">{{ number_format($totals['purchases'] ?? 0, 2) }} ILS</span></div>
            <div class="flex justify-between"><span class="text-gray-500">مصروفات</span><span class="font-mono text-red-500" dir="ltr">{{ number_format($totals['expenses'] ?? 0, 2) }} ILS</span></div>
            <div class="flex justify-between"><span class="text-gray-500">رواتب</span><span class="font-mono text-red-500" dir="ltr">{{ number_format($totals['salaries'] ?? 0, 2) }} ILS</span></div>
            <div class="flex justify-between pt-2 border-t font-bold text-lg">
                <span>صافي الربح/الخسارة</span>
                <span class="font-mono {{ ($totals['net_profit'] ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}" dir="ltr">
                    {{ ($totals['net_profit'] ?? 0) >= 0 ? '+' : '' }}{{ number_format($totals['net_profit'] ?? 0, 2) }} ILS
                </span>
            </div>
            <div class="flex justify-between pt-2 border-t border-dashed mt-1">
                <span class="text-gray-400 text-xs">أصل المخزون (خارج الربح)</span>
                <span class="font-mono text-[#1B6CA8]" dir="ltr">{{ number_format($totals['inventory_asset'] ?? 0, 2) }} ILS</span>
            </div>
        </div>
    </div>

    @if(!empty($totals['rates']))
    <div class="card p-4 mb-4">
        <p class="text-xs font-bold text-gray-400 mb-2">أسعار التحويل (BOI)</p>
        <div class="flex flex-wrap gap-4 text-sm font-mono" dir="ltr">
            @foreach($totals['rates'] as $cur => $rate)
            <span>{{ $cur }} = {{ number_format($rate, 4) }} ILS</span>
            @endforeach
        </div>
    </div>
    @endif

    <div class="card overflow-hidden">
        <div class="px-4 py-3 border-b text-sm font-bold text-gray-500">تفصيل حسب العملة الأصلية</div>
        <div class="overflow-x-auto [-webkit-overflow-scrolling:touch]">
            <table class="data-table text-sm">
                <thead>
                    <tr>
                        <th dir="ltr">عملة</th>
                        <th>مبيعات/إيراد</th>
                        <th>تكلفة المباع</th>
                        <th>صافي أصلي</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sourceRows as $cur => $row)
                    <tr>
                        <td dir="ltr">{{ $cur }}</td>
                        <td class="font-mono" dir="ltr">{{ number_format($row['sales'], 2) }}</td>
                        <td class="font-mono" dir="ltr">{{ number_format($row['purchases'], 2) }}</td>
                        <td class="font-mono font-semibold {{ $row['net_profit'] >= 0 ? 'text-green-600' : 'text-red-600' }}" dir="ltr">{{ number_format($row['net_profit'], 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @else
    <div class="text-center py-16 text-gray-400">لا توجد حركات في هذه الفترة.</div>
    @endif
</div>
