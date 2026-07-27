<div>
    <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-[#3D3D3D]">تقرير التحصيلات</h1>
            <p class="text-sm text-gray-500 mt-1">تحصيلات نقدية وشيكات ضمن الفترة المحددة.</p>
        </div>
        @can('export-period-reports')
        <button type="button" wire:click="exportCsv" class="btn btn-secondary">تصدير CSV</button>
        @endcan
    </div>

    <div class="card p-4 mb-4 grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
        <div>
            <label class="label">من</label>
            <input type="date" wire:model="dateFrom" class="input" dir="ltr">
        </div>
        <div>
            <label class="label">إلى</label>
            <input type="date" wire:model="dateTo" class="input" dir="ltr">
        </div>
        <div>
            <label class="label">السائق</label>
            <select wire:model="driverId" class="input">
                <option value="">الكل</option>
                @foreach($drivers as $d)<option value="{{ $d->id }}">{{ $d->full_name }}</option>@endforeach
            </select>
        </div>
        <div>
            <label class="label">الطريقة</label>
            <select wire:model="method" class="input">
                <option value="">الكل</option>
                <option value="cash">نقدي</option>
                <option value="cheque">شيك</option>
            </select>
        </div>
    </div>

    <div class="flex flex-wrap gap-3 mb-4">
        <div class="card px-4 py-2 text-sm"><span class="text-gray-500">نقدي:</span> <span class="font-mono font-bold text-green-600 mr-2" dir="ltr">{{ number_format($totals['cash'], 2) }}</span></div>
        <div class="card px-4 py-2 text-sm"><span class="text-gray-500">شيكات:</span> <span class="font-mono font-bold text-blue-600 mr-2" dir="ltr">{{ number_format($totals['cheque'], 2) }}</span></div>
        <div class="card px-4 py-2 text-sm"><span class="text-gray-500">الإجمالي:</span> <span class="font-mono font-bold text-[#3D3D3D] mr-2" dir="ltr">{{ number_format($totals['grand'], 2) }}</span></div>
        <div class="card px-4 py-2 text-sm"><span class="text-gray-500">عدد العمليات:</span> <span class="font-mono font-bold text-[#3D3D3D] mr-2" dir="ltr">{{ $totals['count'] }}</span></div>
    </div>

    @if($rows->isEmpty())
    <div class="text-center py-16 text-gray-400">لا توجد تحصيلات في هذه الفترة.</div>
    @else
    <div class="overflow-x-auto card">
        <table class="data-table">
            <thead><tr>
                <th class="w-28">التاريخ</th>
                <th>السائق</th>
                <th>الطريقة</th>
                <th class="text-left" dir="ltr">المبلغ</th>
                <th>رقم الشيك</th>
                <th>ملاحظات</th>
            </tr></thead>
            <tbody>
                @foreach($rows as $r)
                <tr>
                    <td dir="ltr" class="text-sm text-gray-500">{{ $r->collection_date?->format('Y-m-d') }}</td>
                    <td class="text-sm">{{ $r->driver?->full_name ?? '—' }}</td>
                    <td>
                        @if($r->method->value === 'cash')
                            <span class="rounded px-2 py-0.5 bg-green-50 text-green-600 text-xs">نقدي</span>
                        @else
                            <span class="rounded px-2 py-0.5 bg-blue-50 text-blue-600 text-xs">شيك</span>
                        @endif
                    </td>
                    <td class="font-mono text-left text-sm font-semibold" dir="ltr">{{ number_format((float) $r->amount, 2) }}</td>
                    <td class="text-sm text-gray-500" dir="ltr">{{ $r->cheque_number ?? '—' }}</td>
                    <td class="text-sm text-gray-500">{{ $r->notes ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
