<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-[#3D3D3D]">صندوق السائق وتسوية الكاش</h1>
        <p class="text-sm text-gray-500 mt-1">لكل سائق: نقدي + تحصيل نقدي − مصروفات − مسحوب = صافي الفترة، مع الرصيد الحالي غير المُسلَّم.</p>
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
                <th class="text-left" dir="ltr">مبيعات نقدية</th>
                <th class="text-left" dir="ltr">على الحساب</th>
                <th class="text-left" dir="ltr">تحصيل نقدي</th>
                <th class="text-left" dir="ltr">شيكات</th>
                <th class="text-left" dir="ltr">مصروفات</th>
                <th class="text-left" dir="ltr">مسحوب</th>
                <th class="text-left" dir="ltr">صافي الفترة</th>
                <th class="text-left" dir="ltr">الرصيد الحالي</th>
            </tr></thead>
            <tbody>
                @foreach($rows as $r)
                <tr>
                    <td class="font-semibold text-sm">{{ $r['driver'] }}</td>
                    <td class="font-mono text-left text-sm text-green-600" dir="ltr">{{ number_format($r['cash_sales'], 2) }}</td>
                    <td class="font-mono text-left text-sm text-[#C9A227]" dir="ltr">{{ number_format($r['credit_sales'], 2) }}</td>
                    <td class="font-mono text-left text-sm text-green-600" dir="ltr">{{ number_format($r['cash_collections'], 2) }}</td>
                    <td class="font-mono text-left text-sm text-blue-600" dir="ltr">{{ number_format($r['cheque_collections'], 2) }}</td>
                    <td class="font-mono text-left text-sm text-red-600" dir="ltr">{{ number_format($r['expenses'], 2) }}</td>
                    <td class="font-mono text-left text-sm text-red-600" dir="ltr">{{ number_format($r['withdrawn'], 2) }}</td>
                    <td class="font-mono text-left text-sm font-semibold" dir="ltr">{{ number_format($r['net'], 2) }}</td>
                    <td class="font-mono text-left text-sm font-bold text-[#3D3D3D]" dir="ltr">{{ number_format($r['balance'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="font-bold bg-[#F9F9FB]">
                    <td>الإجمالي</td>
                    <td class="font-mono text-left" dir="ltr">{{ number_format($totals['cash_sales'], 2) }}</td>
                    <td class="font-mono text-left" dir="ltr">{{ number_format($totals['credit_sales'], 2) }}</td>
                    <td class="font-mono text-left" dir="ltr">{{ number_format($totals['cash_collections'], 2) }}</td>
                    <td class="font-mono text-left" dir="ltr">{{ number_format($totals['cheque_collections'], 2) }}</td>
                    <td class="font-mono text-left" dir="ltr">{{ number_format($totals['expenses'], 2) }}</td>
                    <td class="font-mono text-left" dir="ltr">{{ number_format($totals['withdrawn'], 2) }}</td>
                    <td class="font-mono text-left" dir="ltr">{{ number_format($totals['net'], 2) }}</td>
                    <td class="font-mono text-left" dir="ltr">{{ number_format($totals['balance'], 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endif
</div>
