@php
    /**
     * صناديق النقد (نشاط توزيع الغاز + دفتر الصندوق الرئيسي):
     * - الصندوق الرئيسي = دخول (سحب سائقين + دفعات عملاء كاش/شيك)
     *   − خروج (موردين كاش/شيك + رواتب كاش/شيك + مصروفات شركة نقداً).
     * - bank/transfer لا يدخلان رصيد الكاش/الشيك المادي.
     * - صندوق كل سائق = النقد/الشيكات المحصّلة ولم تُسحب بعد.
     */
    $svc = app(\App\Services\CashBoxService::class);

    $mainByCur = $svc->mainBoxByCurrency();
    $ledger = $svc->mainBoxLedger(80);

    $boxCurrencies = collect(array_keys($mainByCur))
        ->merge(\App\Models\Sale::query()->distinct()->pluck('currency_code')->all())
        ->merge(\App\Models\Collection::query()->distinct()->pluck('currency_code')->all())
        ->merge(\App\Models\ClientPayment::query()->distinct()->pluck('currency_code')->all())
        ->merge(\App\Models\SupplierPayment::query()->distinct()->pluck('currency_code')->all())
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

    $recentExpenses = \App\Models\DriverExpense::query()
        ->with('driver')
        ->latest('spent_at')
        ->limit(30)
        ->get();
@endphp

@php
    if ($boxCurrencies->isEmpty()) {
        $boxCurrencies = collect(['ILS']);
    }
@endphp

    {{-- الصناديق الرئيسية حسب العملة --}}
    <div class="grid grid-cols-1 lg:grid-cols-{{ max(1, min($boxCurrencies->count(), 3)) }} gap-4 mb-6">
        @foreach($boxCurrencies as $cur)
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
                        <p class="text-xl font-black {{ $mainCash < 0 ? 'text-red-700' : 'text-green-700' }} leading-none" dir="ltr">{{ number_format($mainCash, 2) }}</p>
                    </div>
                    <div class="bg-blue-50 rounded-xl p-3">
                        <p class="text-[10px] font-bold text-blue-500 mb-1">شيكات الصندوق</p>
                        <p class="text-xl font-black {{ $mainCheque < 0 ? 'text-red-700' : 'text-blue-700' }} leading-none" dir="ltr">{{ number_format($mainCheque, 2) }}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <p class="text-xs text-gray-400 mb-6">
        المعادلة: (سحب سائقين + دفعات عملاء كاش/شيك) − (دفع موردين كاش/شيك + رواتب كاش/شيك + مصروفات شركة نقداً). التحويل البنكي لا يدخل هذا الرصيد.
    </p>

    {{-- صناديق السائقين (لم يُسحب بعد) --}}
    <div class="card overflow-hidden mb-6">
        <div class="flex items-center justify-between px-5 py-3 border-b border-[#E2E8F0] bg-[#F9F9FB]">
            <p class="text-sm font-bold text-[#1E293B]">صناديق السائقين (لم يُسحب بعد)</p>
            <p class="text-xs text-gray-400" dir="ltr">
                نقد: {{ number_format($driversCashHeld, 2) }} · شيكات: {{ number_format($driversChequeHeld, 2) }}
            </p>
        </div>
        <div class="overflow-x-auto [-webkit-overflow-scrolling:touch]">
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
    </div>

    {{-- دفتر حركات الصندوق الرئيسي --}}
    <div class="card overflow-hidden">
        <div class="px-5 py-3 border-b border-[#E2E8F0] bg-[#F9F9FB]">
            <p class="text-sm font-bold text-[#1E293B]">حركات الصندوق الرئيسي</p>
        </div>
        <div class="overflow-x-auto [-webkit-overflow-scrolling:touch]">
            <table class="data-table">
                <thead><tr>
                    <th>الوقت</th>
                    <th>النوع</th>
                    <th>الطرف</th>
                    <th>العملة</th>
                    <th class="text-left" dir="ltr">المبلغ</th>
                    <th>المرجع</th>
                    <th>ملاحظات</th>
                </tr></thead>
                <tbody>
                    @forelse($ledger as $m)
                    <tr>
                        <td class="text-sm text-gray-500" dir="ltr">{{ $m['at']?->format('Y-m-d H:i') ?? '—' }}</td>
                        <td><span class="badge {{ $m['signed_amount'] >= 0 ? 'badge-green' : 'badge-yellow' }}">{{ $m['type_label'] }}</span></td>
                        <td class="text-sm">{{ $m['party'] }}</td>
                        <td class="text-xs font-mono text-gray-400" dir="ltr">{{ $m['currency'] }}</td>
                        <td class="text-left font-mono text-sm font-semibold {{ $m['signed_amount'] >= 0 ? 'text-green-600' : 'text-red-600' }}" dir="ltr">
                            {{ $m['signed_amount'] >= 0 ? '+' : '' }}{{ number_format($m['signed_amount'], 2) }}
                        </td>
                        <td class="text-sm text-gray-500 font-mono" dir="ltr">{{ $m['reference'] }}</td>
                        <td class="text-sm text-gray-500">{{ $m['notes'] ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="7"><div class="text-center py-10 text-gray-300 text-sm">لا توجد حركات في الصندوق الرئيسي</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- مصروفات السائقين (تُخصم من صندوق السائق قبل السحب) --}}
    <div class="card overflow-hidden mt-6">
        <div class="px-5 py-3 border-b border-[#E2E8F0] bg-[#F9F9FB]">
            <p class="text-sm font-bold text-[#1E293B]">مصروفات السائقين (من صندوق السائق)</p>
        </div>
        <div class="overflow-x-auto [-webkit-overflow-scrolling:touch]">
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
    </div>
