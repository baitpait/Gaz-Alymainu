<div>
    <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-[#1E293B]">تقرير الرواتب</h1>
            <p class="text-sm text-gray-500 mt-1">رواتب الموظفين حسب شهر الفترة — مستقل عن المصروفات.</p>
        </div>
        @include('livewire.partials.period-report-export-actions', ['pdfExportUrl' => $pdfExportUrl])
    </div>

    @include('livewire.partials.period-report-filters', ['currencyOptions' => $currencyOptions, 'showMethod' => false])

    @if(!empty($totals))
    <div class="flex flex-wrap gap-4 mb-4">
        @foreach($totals as $cur => $total)
        <div class="card px-4 py-2 text-sm">
            <span class="text-gray-500">صافي {{ $cur }} ({{ $total['count'] }}):</span>
            <span class="font-mono font-bold text-[#1B6CA8] mr-2" dir="ltr">{{ number_format($total['net'], 2) }}</span>
        </div>
        @endforeach
    </div>
    @endif

    @if(empty($rows))
    <div class="text-center py-16 text-gray-400">لا توجد رواتب في هذه الفترة.</div>
    @else
    <div class="overflow-x-auto card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>الموظف</th>
                    <th>القسم</th>
                    <th class="w-24" dir="ltr">الشهر</th>
                    <th class="w-20 text-center">أيام العمل</th>
                    <th class="w-28 text-left" dir="ltr">أساسي</th>
                    <th class="w-24 text-left" dir="ltr">مكافأة</th>
                    <th class="w-24 text-left" dir="ltr">خصم</th>
                    <th class="w-28 text-left" dir="ltr">صافي</th>
                    <th class="w-16" dir="ltr">عملة</th>
                    <th>الحالة</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                <tr>
                    <td>{{ $row['employee_name'] }}</td>
                    <td class="text-sm text-gray-500">{{ $row['department'] ?? '—' }}</td>
                    <td dir="ltr">{{ $row['period_label'] }}</td>
                    <td class="text-center text-sm">
                        @if($row['worked_days'] !== null)
                            <span class="font-semibold">{{ $row['worked_days'] }}</span>
                            <span class="text-gray-400 text-xs block" dir="ltr">× {{ number_format($row['daily_rate'] ?? 0, 2) }}</span>
                        @else
                            <span class="text-gray-300">—</span>
                        @endif
                    </td>
                    <td class="font-mono text-sm text-left" dir="ltr">{{ number_format($row['base_amount'], 2) }}</td>
                    <td class="font-mono text-sm text-left" dir="ltr">{{ number_format($row['bonus_amount'], 2) }}</td>
                    <td class="font-mono text-sm text-left" dir="ltr">{{ number_format($row['deduction_amount'], 2) }}</td>
                    <td class="font-mono font-semibold text-left text-[#1B6CA8]" dir="ltr">{{ number_format($row['net_amount'], 2) }}</td>
                    <td dir="ltr">{{ $row['currency'] }}</td>
                    <td class="text-sm">{{ $row['status_label'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
