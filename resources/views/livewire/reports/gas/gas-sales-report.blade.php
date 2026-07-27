<div>
    <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-[#1E293B]">تقرير مبيعات الغاز</h1>
            <p class="text-sm text-gray-500 mt-1">عمليات البيع (نقدي/على الحساب) ضمن الفترة المحددة.</p>
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
            <label class="label">الصنف</label>
            <select wire:model="productId" class="input">
                <option value="">الكل</option>
                @foreach($products as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach
            </select>
        </div>
    </div>

    <div class="flex flex-wrap gap-3 mb-4">
        <div class="card px-4 py-2 text-sm"><span class="text-gray-500">نقدي:</span> <span class="font-mono font-bold text-green-600 mr-2" dir="ltr">{{ number_format($totals['cash'], 2) }}</span></div>
        <div class="card px-4 py-2 text-sm"><span class="text-gray-500">على الحساب:</span> <span class="font-mono font-bold text-[#1B6CA8] mr-2" dir="ltr">{{ number_format($totals['credit'], 2) }}</span></div>
        <div class="card px-4 py-2 text-sm"><span class="text-gray-500">الإجمالي:</span> <span class="font-mono font-bold text-[#1E293B] mr-2" dir="ltr">{{ number_format($totals['grand'], 2) }}</span></div>
        <div class="card px-4 py-2 text-sm"><span class="text-gray-500">الكمية:</span> <span class="font-mono font-bold text-[#1E293B] mr-2" dir="ltr">{{ rtrim(rtrim(number_format($totals['qty'], 2), '0'), '.') }}</span></div>
        <div class="card px-4 py-2 text-sm"><span class="text-gray-500">عدد العمليات:</span> <span class="font-mono font-bold text-[#1E293B] mr-2" dir="ltr">{{ $totals['count'] }}</span></div>
    </div>

    @if($rows->isEmpty())
    <div class="text-center py-16 text-gray-400">لا توجد مبيعات في هذه الفترة.</div>
    @else
    <div class="overflow-x-auto card">
        <table class="data-table">
            <thead><tr>
                <th class="w-28">التاريخ</th>
                <th>السائق</th>
                <th>السيارة</th>
                <th>الصنف</th>
                <th>النوع</th>
                <th class="text-left" dir="ltr">الكمية</th>
                <th class="text-left" dir="ltr">السعر</th>
                <th class="text-left" dir="ltr">الإجمالي</th>
            </tr></thead>
            <tbody>
                @foreach($rows as $r)
                <tr>
                    <td dir="ltr" class="text-sm text-gray-500">{{ $r->sale_date?->format('Y-m-d') }}</td>
                    <td class="text-sm">{{ $r->driver?->full_name ?? '—' }}</td>
                    <td class="text-sm text-gray-500">{{ $r->warehouse?->name ?? '—' }}</td>
                    <td class="font-semibold text-sm">{{ $r->product?->name ?? '—' }}</td>
                    <td>
                        @if($r->payment_type->value === 'cash')
                            <span class="rounded px-2 py-0.5 bg-green-50 text-green-600 text-xs">نقدي</span>
                        @else
                            <span class="rounded px-2 py-0.5 bg-[#1B6CA8]/10 text-[#1B6CA8] text-xs">على الحساب</span>
                        @endif
                    </td>
                    <td class="font-mono text-left text-sm" dir="ltr">{{ rtrim(rtrim(number_format((float) $r->quantity, 2), '0'), '.') }}</td>
                    <td class="font-mono text-left text-sm" dir="ltr">{{ number_format((float) $r->unit_price, 2) }}</td>
                    <td class="font-mono text-left text-sm font-semibold" dir="ltr">{{ number_format((float) $r->total_amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
