<div>
    <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-[#1E293B]">تقرير المبيعات</h1>
            <p class="text-sm text-gray-500 mt-1">فواتير صادرة ضمن الفترة — مع حالة الدفع (FIFO).</p>
        </div>
        @include('livewire.partials.period-report-export-actions', ['pdfExportUrl' => $pdfExportUrl])
    </div>

    @include('livewire.partials.period-report-filters', [
        'currencyOptions' => $currencyOptions,
        'showMethod' => false,
        'showClient' => true,
        'clientOptions' => $clientOptions,
    ])

    @if(!empty($totals))
    <div class="flex flex-wrap gap-4 mb-4">
        @foreach($totals as $cur => $t)
        <div class="card px-4 py-2 text-sm">
            <span class="text-gray-500">{{ $cur }}:</span>
            <span class="font-mono font-bold text-blue-600 mr-2" dir="ltr">{{ number_format($t['total'], 2) }}</span>
            <span class="text-gray-400">({{ $t['count'] }} فاتورة)</span>
        </div>
        @endforeach
    </div>
    @endif

    @if(empty($rows))
    <div class="text-center py-16 text-gray-400">لا توجد فواتير صادرة في هذه الفترة.</div>
    @else
    <div class="overflow-x-auto card">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="w-28">التاريخ</th>
                    <th>العميل</th>
                    <th>رقم الفاتورة</th>
                    <th class="w-16" dir="ltr">عملة</th>
                    <th class="w-32 text-left" dir="ltr">المبلغ</th>
                    <th class="w-28">حالة الدفع</th>
                    <th class="w-20 text-center">بنود</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                <tr>
                    <td dir="ltr">{{ $row['date']->format('Y-m-d') }}</td>
                    <td>{{ $row['client_name'] }}</td>
                    <td class="font-mono text-sm">{{ $row['reference'] }}</td>
                    <td dir="ltr">{{ $row['currency'] }}</td>
                    <td class="font-mono font-semibold text-left text-blue-600" dir="ltr">{{ number_format($row['amount'], 2) }}</td>
                    <td>
                        @php
                        $badge = match($row['payment_status']) {
                            'paid' => 'bg-green-50 text-green-700',
                            'partial' => 'bg-amber-50 text-amber-700',
                            default => 'bg-red-50 text-red-700',
                        };
                        @endphp
                        <span class="text-xs px-2 py-0.5 rounded {{ $badge }}">{{ $row['payment_label'] }}</span>
                    </td>
                    <td class="text-center text-gray-500">{{ $row['line_count'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
