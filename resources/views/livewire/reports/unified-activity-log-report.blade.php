<div>
    <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-[#1E293B]">سجل النشاط المالي</h1>
            <p class="text-sm text-gray-500 mt-1">كل الفواتير وأوامر الشراء والدفعات والمصروفات والتسويات — مرتبة زمنياً.</p>
        </div>
        @include('livewire.partials.period-report-export-actions', ['pdfExportUrl' => $pdfExportUrl])
    </div>

    @include('livewire.partials.period-report-filters', [
        'currencyOptions' => $currencyOptions,
        'showMethod' => true,
        'showClient' => true,
        'showSupplier' => true,
        'clientOptions' => $clientOptions,
        'supplierOptions' => $supplierOptions,
    ])

    @if($truncated)
    <div class="mb-4 p-3 bg-amber-50 border border-amber-200 rounded text-sm text-amber-900">
        يُعرض أول {{ \App\Services\Reports\UnifiedActivityLogService::MAX_ROWS }} سجل من {{ $totalCount }}. ضيّق الفترة أو استخدم CSV للتصدير الكامل (حتى {{ \App\Services\Reports\UnifiedActivityLogService::MAX_ROWS }}).
    </div>
    @endif

    @if(empty($rows))
    <div class="text-center py-16 text-gray-400">لا توجد حركات في هذه الفترة.</div>
    @else
    <div class="overflow-x-auto card">
        <table class="data-table text-sm">
            <thead>
                <tr>
                    <th class="w-28">التاريخ</th>
                    <th class="w-32">النوع</th>
                    <th>الطرف</th>
                    <th>المرجع</th>
                    <th class="w-14" dir="ltr">عملة</th>
                    <th class="w-32 text-left" dir="ltr">المبلغ</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                @php
                $badge = match($row['category']) {
                    'sales' => 'bg-blue-50 text-blue-700',
                    'purchases' => 'bg-purple-50 text-purple-700',
                    'cash_in' => 'bg-green-50 text-green-700',
                    'cash_out' => 'bg-red-50 text-red-700',
                    default => 'bg-violet-50 text-violet-700',
                };
                $amtClass = match(true) {
                    $row['category'] === 'cash_in' => 'text-green-600',
                    $row['category'] === 'cash_out' => 'text-red-600',
                    $row['category'] === 'adjustment' => 'text-violet-600',
                    default => 'text-[#1E293B]',
                };
                @endphp
                <tr>
                    <td dir="ltr">{{ $row['date']->format('Y-m-d') }}</td>
                    <td><span class="text-xs px-2 py-0.5 rounded {{ $badge }}">{{ $row['type_label'] }}</span></td>
                    <td>{{ $row['party'] }}</td>
                    <td class="font-mono text-xs">{{ $row['reference'] }}</td>
                    <td dir="ltr">{{ $row['currency'] }}</td>
                    <td class="font-mono font-semibold text-left {{ $amtClass }}" dir="ltr">
                        @if(in_array($row['category'], ['cash_in', 'cash_out', 'adjustment']))
                            {{ $row['signed_amount'] >= 0 ? '+' : '' }}{{ number_format($row['signed_amount'], 2) }}
                        @else
                            {{ number_format($row['amount'], 2) }}
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
