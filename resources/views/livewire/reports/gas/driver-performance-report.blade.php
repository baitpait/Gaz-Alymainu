<div>
    <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-[#3D3D3D]">أداء السائقين</h1>
            <p class="text-sm text-gray-500 mt-1">ملخص لكل سائق ضمن الفترة: الكميات والمبيعات والتحصيلات.</p>
        </div>
        @can('export-period-reports')
        <button type="button" wire:click="exportCsv" class="btn btn-secondary">تصدير CSV</button>
        @endcan
    </div>

    <div class="card p-4 mb-4 grid grid-cols-1 sm:grid-cols-2 gap-3 items-end max-w-md">
        <div>
            <label class="label">من</label>
            <input type="date" wire:model="dateFrom" class="input" dir="ltr">
        </div>
        <div>
            <label class="label">إلى</label>
            <input type="date" wire:model="dateTo" class="input" dir="ltr">
        </div>
    </div>

    @if(empty($rows))
    <div class="text-center py-16 text-gray-400">لا يوجد سائقون.</div>
    @else
    <div class="overflow-x-auto card">
        <table class="data-table">
            <thead><tr>
                <th>السائق</th>
                <th class="text-left" dir="ltr">عدد المبيعات</th>
                <th class="text-left" dir="ltr">الكمية</th>
                <th class="text-left" dir="ltr">نقدي</th>
                <th class="text-left" dir="ltr">على الحساب</th>
                <th class="text-left" dir="ltr">إجمالي المبيعات</th>
                <th class="text-left" dir="ltr">التحصيلات</th>
            </tr></thead>
            <tbody>
                @foreach($rows as $r)
                <tr>
                    <td class="font-semibold text-sm">{{ $r['driver'] }}</td>
                    <td class="font-mono text-left text-sm" dir="ltr">{{ $r['count'] }}</td>
                    <td class="font-mono text-left text-sm" dir="ltr">{{ rtrim(rtrim(number_format($r['qty'], 2), '0'), '.') }}</td>
                    <td class="font-mono text-left text-sm text-green-600" dir="ltr">{{ number_format($r['cash_sales'], 2) }}</td>
                    <td class="font-mono text-left text-sm text-[#C9A227]" dir="ltr">{{ number_format($r['credit_sales'], 2) }}</td>
                    <td class="font-mono text-left text-sm font-semibold" dir="ltr">{{ number_format($r['total_sales'], 2) }}</td>
                    <td class="font-mono text-left text-sm text-blue-600" dir="ltr">{{ number_format($r['collections'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="font-bold bg-[#F9F9FB]">
                    <td>الإجمالي</td>
                    <td class="font-mono text-left" dir="ltr">{{ $totals['count'] }}</td>
                    <td class="font-mono text-left" dir="ltr">{{ rtrim(rtrim(number_format($totals['qty'], 2), '0'), '.') }}</td>
                    <td class="font-mono text-left" dir="ltr">{{ number_format($totals['cash_sales'], 2) }}</td>
                    <td class="font-mono text-left" dir="ltr">{{ number_format($totals['credit_sales'], 2) }}</td>
                    <td class="font-mono text-left" dir="ltr">{{ number_format($totals['total_sales'], 2) }}</td>
                    <td class="font-mono text-left" dir="ltr">{{ number_format($totals['collections'], 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endif
</div>
