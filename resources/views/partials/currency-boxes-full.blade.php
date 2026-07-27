@php
    /**
     * صناديق النقد (نشاط توزيع الغاز):
     * - الصندوق الرئيسي حسب العملة = كل ما سُحب من السائقين (كاش + شيكات).
     * - صندوق كل سائق = النقد/الشيكات المحصّلة ولم تُسحب بعد.
     * - الشيكات تظهر كحركات مالية منفصلة (حسب آلية الدفع + ملاحظات).
     */
    $svc = app(\App\Services\CashBoxService::class);

    $mainByCur = $svc->mainBoxByCurrency();

    $boxCurrencies = collect(array_keys($mainByCur))
        ->merge(\App\Models\Sale::query()->distinct()->pluck('currency_code')->all())
        ->merge(\App\Models\Collection::query()->distinct()->pluck('currency_code')->all())
        ->filter()
        ->unique()
        ->sort()
        ->values();

    $activeDrivers = \App\Models\User::query()
        ->where('role', 'driver')
        ->where('is_active', true)
        ->orderBy('full_name')
        ->get(['id', 'full_name']);

    $driverBoxes = $activeDrivers->map(fn ($d) => [
        'name'   => $d->full_name,
        'cash'   => $svc->balance($d->id),
        'cheque' => $svc->chequeBalance($d->id),
    ]);

    $driversCashHeld   = $driverBoxes->sum('cash');
    $driversChequeHeld = $driverBoxes->sum('cheque');

    // حركات الشيكات: تحصيل شيك + سحب شيك (موحّدة)
    $chequeMoves = collect();

    foreach (\App\Models\Collection::query()->where('method', 'cheque')->with('driver')->latest('collected_at')->limit(50)->get() as $c) {
        $chequeMoves->push([
            'at'      => $c->collected_at,
            'driver'  => $c->driver?->full_name ?? '—',
            'type'    => 'تحصيل شيك',
            'badge'   => 'badge-green',
            'amount'  => (float) $c->amount,
            'cheque'  => $c->cheque_number,
            'notes'   => $c->notes,
        ]);
    }

    foreach (\App\Models\CashHandover::query()->where('method', 'cheque')->with('driver')->latest('handed_at')->limit(50)->get() as $h) {
        $chequeMoves->push([
            'at'      => $h->handed_at,
            'driver'  => $h->driver?->full_name ?? '—',
            'type'    => 'سحب شيك',
            'badge'   => 'badge-blue',
            'amount'  => (float) $h->amount,
            'cheque'  => $h->cheque_number,
            'notes'   => $h->notes,
        ]);
    }

    $chequeMoves = $chequeMoves->sortByDesc('at')->values();

    $recentExpenses = \App\Models\DriverExpense::query()
        ->with('driver')
        ->latest('spent_at')
        ->limit(30)
        ->get();
@endphp

@if($boxCurrencies->isEmpty() && $activeDrivers->isEmpty())
    <div class="card p-6 text-center text-sm text-gray-300">لا توجد حركات نقدية مسجّلة بعد</div>
@else

    {{-- الصناديق الرئيسية حسب العملة --}}
    <div class="grid grid-cols-1 lg:grid-cols-{{ max(1, min($boxCurrencies->count(), 3)) }} gap-4 mb-6">
        @forelse($boxCurrencies as $cur)
            @php
                $mainCash   = (float) ($mainByCur[$cur]['cash'] ?? 0);
                $mainCheque = (float) ($mainByCur[$cur]['cheque'] ?? 0);
            @endphp
            <div class="card p-5 border-r-4 border-[#1B6CA8]">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-sm font-bold text-[#1E293B]">الصندوق الرئيسي</span>
                    <span class="text-xs font-bold text-gray-400 uppercase tracking-widest" dir="ltr">{{ $cur }}</span>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-green-50 rounded-xl p-3">
                        <p class="text-[10px] font-bold text-green-500 mb-1">نقد الصندوق</p>
                        <p class="text-xl font-black text-green-700 leading-none" dir="ltr">{{ number_format($mainCash, 2) }}</p>
                    </div>
                    <div class="bg-blue-50 rounded-xl p-3">
                        <p class="text-[10px] font-bold text-blue-500 mb-1">شيكات الصندوق</p>
                        <p class="text-xl font-black text-blue-700 leading-none" dir="ltr">{{ number_format($mainCheque, 2) }}</p>
                    </div>
                </div>
            </div>
        @empty
            <div class="card p-5 border-r-4 border-[#1B6CA8]">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-sm font-bold text-[#1E293B]">الصندوق الرئيسي</span>
                    <span class="text-xs font-bold text-gray-400 uppercase tracking-widest" dir="ltr">ILS</span>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-green-50 rounded-xl p-3">
                        <p class="text-[10px] font-bold text-green-500 mb-1">نقد الصندوق</p>
                        <p class="text-xl font-black text-green-700 leading-none" dir="ltr">0.00</p>
                    </div>
                    <div class="bg-blue-50 rounded-xl p-3">
                        <p class="text-[10px] font-bold text-blue-500 mb-1">شيكات الصندوق</p>
                        <p class="text-xl font-black text-blue-700 leading-none" dir="ltr">0.00</p>
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    {{-- صناديق السائقين (لم يُسحب بعد) --}}
    <div class="card overflow-hidden mb-6">
        <div class="flex items-center justify-between px-5 py-3 border-b border-[#E2E8F0] bg-[#F9F9FB]">
            <p class="text-sm font-bold text-[#1E293B]">صناديق السائقين (لم يُسحب بعد)</p>
            <p class="text-xs text-gray-400" dir="ltr">
                نقد: {{ number_format($driversCashHeld, 2) }} · شيكات: {{ number_format($driversChequeHeld, 2) }}
            </p>
        </div>
        <table class="data-table">
            <thead><tr>
                <th>السائق</th>
                <th class="text-left" dir="ltr">الرصيد النقدي</th>
                <th class="text-left" dir="ltr">رصيد الشيكات</th>
            </tr></thead>
            <tbody>
                @forelse($driverBoxes as $box)
                <tr>
                    <td class="font-medium">{{ $box['name'] }}</td>
                    <td class="text-left font-mono text-sm font-semibold {{ $box['cash'] > 0 ? 'text-green-600' : 'text-gray-400' }}" dir="ltr">{{ number_format($box['cash'], 2) }}</td>
                    <td class="text-left font-mono text-sm font-semibold {{ $box['cheque'] > 0 ? 'text-blue-600' : 'text-gray-400' }}" dir="ltr">{{ number_format($box['cheque'], 2) }}</td>
                </tr>
                @empty
                <tr><td colspan="3"><div class="text-center py-10 text-gray-300 text-sm">لا يوجد سائقون نشطون</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- الحركات المالية — الشيكات --}}
    <div class="card overflow-hidden">
        <div class="px-5 py-3 border-b border-[#E2E8F0] bg-[#F9F9FB]">
            <p class="text-sm font-bold text-[#1E293B]">الحركات المالية — الشيكات</p>
        </div>
        <table class="data-table">
            <thead><tr>
                <th>الوقت</th>
                <th>السائق</th>
                <th>النوع</th>
                <th class="text-left" dir="ltr">المبلغ</th>
                <th>رقم الشيك</th>
                <th>ملاحظات</th>
            </tr></thead>
            <tbody>
                @forelse($chequeMoves as $m)
                <tr>
                    <td class="text-sm text-gray-500" dir="ltr">{{ $m['at']?->format('Y-m-d H:i') }}</td>
                    <td class="text-sm">{{ $m['driver'] }}</td>
                    <td><span class="badge {{ $m['badge'] }}">{{ $m['type'] }}</span></td>
                    <td class="text-left font-mono text-sm font-semibold" dir="ltr">{{ number_format($m['amount'], 2) }}</td>
                    <td class="text-sm text-gray-500 font-mono" dir="ltr">{{ $m['cheque'] ?? '—' }}</td>
                    <td class="text-sm text-gray-500">{{ $m['notes'] ?? '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="6"><div class="text-center py-10 text-gray-300 text-sm">لا توجد حركات شيكات</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- مصروفات السائقين (تُخصم من الصندوق النقدي) --}}
    <div class="card overflow-hidden mt-6">
        <div class="px-5 py-3 border-b border-[#E2E8F0] bg-[#F9F9FB]">
            <p class="text-sm font-bold text-[#1E293B]">مصروفات السائقين</p>
        </div>
        <table class="data-table">
            <thead><tr>
                <th>الوقت</th>
                <th>السائق</th>
                <th>التصنيف</th>
                <th class="text-left" dir="ltr">المبلغ</th>
                <th>ملاحظات</th>
            </tr></thead>
            <tbody>
                @forelse($recentExpenses as $ex)
                <tr>
                    <td class="text-sm text-gray-500" dir="ltr">{{ $ex->spent_at?->format('Y-m-d H:i') }}</td>
                    <td class="text-sm">{{ $ex->driver?->full_name ?? '—' }}</td>
                    <td><span class="badge badge-yellow">{{ $ex->category?->label() ?? '—' }}</span></td>
                    <td class="text-left font-mono text-sm font-semibold text-red-600" dir="ltr">{{ number_format((float) $ex->amount, 2) }}</td>
                    <td class="text-sm text-gray-500">{{ $ex->notes ?? '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="5"><div class="text-center py-10 text-gray-300 text-sm">لا توجد مصروفات</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

@endif
