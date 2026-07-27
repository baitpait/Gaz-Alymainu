<div>
    <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-[#1E293B]">تسويات العملاء — الفترة</h1>
            <p class="text-sm text-gray-500 mt-1">تسويات ذمة العملاء (ليست تدفقاً نقدياً) ضمن الفترة.</p>
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
        @foreach($totals as $cur => $total)
        <div class="card px-4 py-2 text-sm">
            <span class="text-gray-500">إجمالي {{ $cur }}:</span>
            <span class="font-mono font-bold text-[#7C3AED] mr-2" dir="ltr">{{ number_format($total, 2) }}</span>
        </div>
        @endforeach
    </div>
    @endif

    @if(empty($rows))
    <div class="text-center py-16 text-gray-400">لا توجد تسويات في هذه الفترة.</div>
    @else
    <div class="overflow-x-auto card">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="w-28">التاريخ</th>
                    <th>العميل</th>
                    <th>النوع</th>
                    <th>السبب</th>
                    <th class="w-16" dir="ltr">عملة</th>
                    <th class="w-32 text-left" dir="ltr">المبلغ</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                <tr>
                    <td dir="ltr">{{ $row['date']->format('Y-m-d') }}</td>
                    <td>{{ $row['client_name'] }}</td>
                    <td>{{ $row['type_label'] }}</td>
                    <td class="text-gray-600 text-sm">{{ $row['reason'] ?? '—' }}</td>
                    <td dir="ltr">{{ $row['currency'] }}</td>
                    <td class="font-mono font-semibold text-left text-[#7C3AED]" dir="ltr">{{ number_format($row['amount'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
