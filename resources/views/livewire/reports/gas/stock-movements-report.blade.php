<div>
    <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-[#1E293B]">حركات المخزون</h1>
            <p class="text-sm text-gray-500 mt-1">شراء/تحميل/إرجاع/تحويل/بيع/تسوية ضمن الفترة المحددة.</p>
        </div>
        @can('export-period-reports')
        <button type="button" wire:click="exportCsv" class="btn btn-secondary">تصدير CSV</button>
        @endcan
    </div>

    <div class="card p-4 mb-4 grid grid-cols-1 sm:grid-cols-5 gap-3 items-end">
        <div>
            <label class="label">من</label>
            <input type="date" wire:model="dateFrom" class="input" dir="ltr">
        </div>
        <div>
            <label class="label">إلى</label>
            <input type="date" wire:model="dateTo" class="input" dir="ltr">
        </div>
        <div>
            <label class="label">النوع</label>
            <select wire:model="type" class="input">
                <option value="">الكل</option>
                @foreach($typeOptions as $val => $lbl)<option value="{{ $val }}">{{ $lbl }}</option>@endforeach
            </select>
        </div>
        <div>
            <label class="label">المخزن</label>
            <select wire:model="warehouseId" class="input">
                <option value="">الكل</option>
                @foreach($warehouses as $w)<option value="{{ $w->id }}">{{ $w->name }}</option>@endforeach
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
        <div class="card px-4 py-2 text-sm"><span class="text-gray-500">عدد الحركات:</span> <span class="font-mono font-bold text-[#1E293B] mr-2" dir="ltr">{{ $count }}</span></div>
    </div>

    @if($rows->isEmpty())
    <div class="text-center py-16 text-gray-400">لا توجد حركات في هذه الفترة.</div>
    @else
    <div class="overflow-x-auto card">
        <table class="data-table">
            <thead><tr>
                <th class="w-36">التاريخ</th>
                <th>النوع</th>
                <th>من</th>
                <th>إلى</th>
                <th>الصنف</th>
                <th class="text-left" dir="ltr">الكمية</th>
                <th>السائق</th>
            </tr></thead>
            <tbody>
                @foreach($rows as $r)
                <tr>
                    <td dir="ltr" class="text-sm text-gray-500">{{ \App\Support\AppDateTime::format($r->moved_at) }}</td>
                    <td class="text-sm font-semibold">{{ $r->type->label() }}</td>
                    <td class="text-sm text-gray-500">{{ $r->fromWarehouse?->name ?? '—' }}</td>
                    <td class="text-sm text-gray-500">{{ $r->toWarehouse?->name ?? '—' }}</td>
                    <td class="text-sm">{{ $r->product?->name ?? '—' }}</td>
                    <td class="font-mono text-left text-sm font-semibold" dir="ltr">{{ rtrim(rtrim(number_format((float) $r->quantity, 2), '0'), '.') }}</td>
                    <td class="text-sm text-gray-500">{{ $r->driver?->full_name ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
