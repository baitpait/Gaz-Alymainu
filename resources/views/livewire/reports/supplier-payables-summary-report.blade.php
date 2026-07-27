<div>
    <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-[#1E293B]">ملخص ذمم الموردين</h1>
            <p class="text-sm text-gray-500 mt-1">متبقٍ لكل مورد حتى تاريخ «إلى» — بدون تصنيف عمرية.</p>
        </div>
        @include('livewire.partials.period-report-export-actions', ['pdfExportUrl' => $pdfExportUrl])
    </div>

    @include('livewire.partials.as-of-summary-filters', [
        'currencyOptions' => $currencyOptions,
        'showSupplier' => true,
        'supplierOptions' => $supplierOptions,
    ])

    @if(!empty($totals))
    <div class="flex flex-wrap gap-4 mb-4">
        @foreach($totals as $cur => $total)
        <div class="card px-4 py-2 text-sm">
            <span class="text-gray-500">إجمالي {{ $cur }}:</span>
            <span class="font-mono font-bold text-violet-600 mr-2" dir="ltr">{{ number_format($total, 2) }}</span>
        </div>
        @endforeach
    </div>
    @endif

    @if(empty($rows))
    <div class="text-center py-16 text-gray-400">لا توجد ذمم مستحقة وفق الفلاتر.</div>
    @else
    <div class="overflow-x-auto card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>المورد</th>
                    <th class="w-32">الهاتف</th>
                    <th class="w-16" dir="ltr">عملة</th>
                    <th class="w-28 text-left" dir="ltr">أوامر شراء</th>
                    <th class="w-28 text-left" dir="ltr">دفعات</th>
                    <th class="w-28 text-left" dir="ltr">تسويات</th>
                    <th class="w-32 text-left" dir="ltr">المتبقي</th>
                    <th class="w-24"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                <tr>
                    <td>{{ $row['supplier_name'] }}</td>
                    <td dir="ltr" class="text-sm text-gray-600">{{ $row['phone'] ?? '—' }}</td>
                    <td dir="ltr">{{ $row['currency'] }}</td>
                    <td class="font-mono text-left text-sm" dir="ltr">{{ number_format($row['total_ordered'], 2) }}</td>
                    <td class="font-mono text-left text-sm" dir="ltr">{{ number_format($row['total_paid'], 2) }}</td>
                    <td class="font-mono text-left text-sm" dir="ltr">{{ number_format($row['total_adjusted'], 2) }}</td>
                    <td class="font-mono font-semibold text-left text-violet-600" dir="ltr">{{ number_format($row['balance'], 2) }}</td>
                    <td>
                        <a href="{{ route('suppliers.statement', ['supplier' => $row['supplier_id'], 'date_to' => $dateTo]) }}" wire:navigate
                           class="text-xs text-[#1B6CA8] hover:underline">كشف</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
