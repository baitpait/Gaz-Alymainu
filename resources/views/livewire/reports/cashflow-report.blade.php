<div>
    <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-[#1E293B]">كشف التدفق النقدي</h1>
            <p class="text-sm text-gray-500 mt-1">دفعات العملاء (+) ودفعات الموردين (−) والمصروفات (−) ضمن الفترة المحددة.</p>
        </div>
        @include('livewire.partials.period-report-export-actions', ['pdfExportUrl' => $pdfExportUrl])
    </div>

    @include('livewire.partials.period-report-filters', ['currencyOptions' => $currencyOptions])

    @if(!empty($summary))
    <div class="grid grid-cols-1 lg:grid-cols-{{ min(count($summary), 3) }} gap-4 mb-6">
        @foreach($summary as $cur => $s)
        <div class="card p-4">
            <p class="text-xs font-bold text-gray-400 mb-3" dir="ltr">{{ $cur }}</p>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between"><span class="text-gray-500">وارد (عملاء)</span><span class="font-mono text-green-600" dir="ltr">+{{ number_format($s['inflow'], 2) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">صادر (موردين)</span><span class="font-mono text-red-500" dir="ltr">−{{ number_format($s['supplier_outflow'], 2) }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">صادر (مصروفات)</span><span class="font-mono text-red-500" dir="ltr">−{{ number_format($s['expense_outflow'], 2) }}</span></div>
                <div class="flex justify-between pt-2 border-t font-bold"><span>صافي الفترة</span><span class="font-mono {{ $s['net'] >= 0 ? 'text-[#1B6CA8]' : 'text-red-600' }}" dir="ltr">{{ number_format($s['net'], 2) }}</span></div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    @if(empty($rows))
    <div class="text-center py-16 text-gray-400">لا توجد حركات نقدية في هذه الفترة.</div>
    @else
    <div class="overflow-x-auto card">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="w-28">التاريخ</th>
                    <th class="w-32">النوع</th>
                    <th>الطرف</th>
                    <th>المرجع</th>
                    <th class="w-24">طريقة الدفع</th>
                    <th class="w-16" dir="ltr">عملة</th>
                    <th class="w-32 text-left" dir="ltr">المبلغ</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                <tr>
                    <td dir="ltr">{{ $row['date']->format('Y-m-d') }}</td>
                    <td>
                        <span class="text-xs px-2 py-0.5 rounded {{ $row['type'] === 'client_payment' ? 'bg-green-50 text-green-700' : ($row['type'] === 'supplier_payment' ? 'bg-purple-50 text-purple-700' : 'bg-red-50 text-red-700') }}">
                            {{ $row['type_label'] }}
                        </span>
                    </td>
                    <td>{{ $row['party'] }}</td>
                    <td class="font-mono text-sm">{{ $row['reference'] }}</td>
                    <td>{{ $row['method_label'] }}</td>
                    <td dir="ltr">{{ $row['currency'] }}</td>
                    <td class="font-mono font-semibold text-left {{ $row['signed_amount'] >= 0 ? 'text-green-600' : 'text-red-600' }}" dir="ltr">
                        {{ $row['signed_amount'] >= 0 ? '+' : '' }}{{ number_format($row['signed_amount'], 2) }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
