<x-layouts.app title="تفاصيل المورد">
@php
    $allOrders     = $supplier->purchaseOrders;
    $issuedOrders  = $allOrders->where('status', 'issued');
    $draftOrders   = $allOrders->where('status', 'draft');
    $allPayments   = $supplier->payments;
    $orderByCur    = $issuedOrders->groupBy('currency_code');
    $payByCur      = $allPayments->groupBy('currency_code');
    $allCur        = $orderByCur->keys()->merge($payByCur->keys())->unique();
@endphp

<div class="max-w-4xl mx-auto">

    {{-- الرأس --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('suppliers.index') }}" wire:navigate
           style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;border-radius:8px;background:#fff;border:1px solid #E2E8F0;color:#9CA3AF;text-decoration:none;transition:color .15s;"
           onmouseover="this.style.color='#1E293B'" onmouseout="this.style.color='#9CA3AF'">
            <svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </a>
        <div>
            <h1 class="text-xl font-bold text-[#1E293B]">{{ $supplier->displayName() }}</h1>
            @if($supplier->city)<p class="text-sm text-gray-400 mt-0.5">{{ $supplier->city }}</p>@endif
        </div>
        <div class="mr-auto flex items-center gap-2">
            @if(auth()->user()->isAccountant())
            <a href="{{ route('suppliers.adjustments.create', $supplier) }}" wire:navigate class="btn btn-secondary text-xs" style="text-decoration:none;">تسوية</a>
            @endif
            <a href="{{ route('suppliers.statement', $supplier) }}" wire:navigate class="btn btn-secondary text-xs" style="text-decoration:none;display:flex;align-items:center;gap:6px;">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                كشف حساب
            </a>
            @if(auth()->user()->isAccountant())
            <a href="{{ route('suppliers.edit', $supplier->id) }}" wire:navigate class="btn btn-primary text-xs" style="text-decoration:none;">تعديل</a>
            @endif
        </div>
    </div>

    {{-- بطاقات الإحصاء --}}
    <div style="display:flex;gap:12px;margin-bottom:20px;">
        <div class="card p-4 text-center" style="flex:1;">
            <p class="text-2xl font-bold text-[#1E293B]">{{ $allOrders->count() }}</p>
            <p class="text-xs text-gray-400 mt-1">إجمالي أوامر الشراء</p>
        </div>
        <div class="card p-4 text-center" style="flex:1;">
            <p class="text-2xl font-bold text-green-600">{{ $issuedOrders->count() }}</p>
            <p class="text-xs text-gray-400 mt-1">أوامر صادرة</p>
        </div>
        <div class="card p-4 text-center" style="flex:1;">
            <p class="text-2xl font-bold text-[#1B6CA8]">{{ $draftOrders->count() }}</p>
            <p class="text-xs text-gray-400 mt-1">مسودات</p>
        </div>
        <div class="card p-4 text-center" style="flex:1;">
            <p class="text-2xl font-bold text-blue-600">{{ $allPayments->count() }}</p>
            <p class="text-xs text-gray-400 mt-1">دفعات</p>
        </div>
    </div>

    {{-- المحتوى الرئيسي: عمودان --}}
    <div style="display:flex;gap:20px;align-items:flex-start;">

        {{-- العمود الأيمن (sidebar) --}}
        <div style="width:260px;flex-shrink:0;display:flex;flex-direction:column;gap:14px;">

            <div class="card p-4">
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">معلومات التواصل</p>
                <div class="space-y-2">
                    @if($supplier->email)
                    <a href="mailto:{{ $supplier->email }}" class="flex items-center gap-2 text-sm hover:text-[#1B6CA8] transition" style="text-decoration:none;">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <span class="text-[#1E293B] truncate" dir="ltr">{{ $supplier->email }}</span>
                    </a>
                    @endif
                    @if($supplier->phone_primary)
                    <a href="tel:{{ $supplier->phone_primary }}" class="flex items-center gap-2 text-sm hover:text-[#1B6CA8] transition" style="text-decoration:none;">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        <span class="text-[#1E293B]" dir="ltr">{{ $supplier->phone_primary }}</span>
                    </a>
                    @endif
                    @if($supplier->phone_secondary)
                    <div class="flex items-center gap-2 text-sm text-gray-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        <span dir="ltr">{{ $supplier->phone_secondary }}</span>
                    </div>
                    @endif
                    @if(!$supplier->email && !$supplier->phone_primary)
                    <p class="text-sm text-gray-300">لا توجد معلومات تواصل</p>
                    @endif
                </div>
            </div>

            @if($allCur->isNotEmpty())
            <div class="card p-4">
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">الملخص المالي</p>
                <div class="space-y-3">
                @foreach($allCur as $cur)
                @php
                    $ordered = (float) ($orderByCur->get($cur)?->sum('total_amount') ?? 0);
                    $paid    = (float) ($payByCur->get($cur)?->sum('amount') ?? 0);
                    $balance = $ordered - $paid;
                    $pct     = $ordered > 0 ? min(100, round($paid / $ordered * 100)) : 0;
                @endphp
                <div class="bg-[#F9F9FB] rounded-xl overflow-hidden">
                    <div class="flex items-center justify-between px-3 py-2 border-b border-[#E2E8F0]">
                        <span class="text-xs font-bold text-[#1E293B]" dir="ltr">{{ $cur }}</span>
                        <span class="badge {{ $balance <= 0 ? 'badge-green' : ($balance < $ordered * 0.5 ? 'badge-yellow' : 'badge-red') }}" style="font-size:9px;">
                            {{ $balance <= 0 ? 'مسدّد' : 'مستحق للمورد' }}
                        </span>
                    </div>
                    <div class="px-3 py-2 space-y-1">
                        <div class="flex justify-between text-xs">
                            <span class="text-gray-400">أوامر الشراء</span>
                            <span class="font-semibold text-[#1E293B]" dir="ltr">{{ number_format($ordered, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-gray-400">المدفوع</span>
                            <span class="font-semibold text-green-600" dir="ltr">{{ number_format($paid, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-xs font-bold pt-1 border-t border-[#E2E8F0]">
                            <span class="text-gray-500">المتبقي</span>
                            <span style="color:{{ $balance > 0 ? '#EF4444' : '#16A34A' }};" dir="ltr">{{ number_format(max(0, $balance), 2) }}</span>
                        </div>
                        @if($ordered > 0)
                        <div class="pt-1">
                            <div class="h-1.5 bg-gray-200 rounded-full overflow-hidden">
                                <div style="width:{{ $pct }}%;height:100%;border-radius:99px;background:{{ $pct>=100 ? '#16A34A' : ($pct>=60 ? '#1B6CA8' : '#EF4444') }};"></div>
                            </div>
                            <p class="text-[10px] text-gray-400 mt-1 text-left" dir="ltr">{{ $pct }}%</p>
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach
                </div>
            </div>
            @endif

            @if($supplier->notes)
            <div class="card p-4 bg-amber-50 border-amber-200">
                <p class="text-xs font-bold text-amber-600 mb-1">ملاحظات</p>
                <p class="text-sm text-amber-900">{{ $supplier->notes }}</p>
            </div>
            @endif

        </div>

        {{-- العمود الأيسر (محتوى رئيسي) --}}
        <div style="flex:1;min-width:0;display:flex;flex-direction:column;gap:20px;">

            <div class="card overflow-hidden">
                <div class="flex items-center justify-between px-4 py-3 border-b border-[#E2E8F0]">
                    <p class="text-sm font-bold text-[#1E293B]">آخر أوامر الشراء</p>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-400">{{ $allOrders->count() }} أمر</span>
                        @if(auth()->user()->isAccountant())
                        <a href="{{ route('purchase-orders.create', ['supplier' => $supplier->id]) }}" wire:navigate class="btn btn-primary text-xs py-1 px-2" style="text-decoration:none;">أمر شراء جديد</a>
                        @endif
                    </div>
                </div>
                @if($allOrders->isNotEmpty())
                <table class="data-table">
                    <thead><tr><th>رقم / تاريخ</th><th>الحالة</th><th>المبلغ</th><th class="w-32"></th></tr></thead>
                    <tbody>
                        @foreach($allOrders->take(10) as $po)
                        @php $s = $po->status; @endphp
                        <tr>
                            <td>
                                <p class="font-semibold text-[#1E293B] text-sm">{{ $po->legacy_po_no ?? '#'.$po->id }}</p>
                                <p class="text-xs text-gray-400" dir="ltr">{{ $po->document_date?->format('Y-m-d') }}</p>
                            </td>
                            <td>
                                <span class="badge {{ $s==='issued' ? 'badge-green' : ($s==='draft' ? 'badge-yellow' : 'badge-red') }}">
                                    {{ $s==='issued' ? 'صادر' : ($s==='draft' ? 'مسودة' : 'ملغى') }}
                                </span>
                            </td>
                            <td class="font-bold text-sm" dir="ltr">
                                {{ number_format((float) $po->total_amount, 2) }}
                                <span class="text-xs text-gray-400 font-normal">{{ $po->currency_code }}</span>
                            </td>
                            <td>
                                <div class="flex items-center gap-1 justify-end flex-wrap">
                                    <a href="{{ route('purchase-orders.show', $po) }}" wire:navigate class="btn btn-ghost py-1 px-2 text-xs text-gray-500 hover:bg-gray-50" style="text-decoration:none;">عرض</a>
                                    @if(auth()->user()->isAccountant())
                                    <a href="{{ route('purchase-orders.edit', $po) }}" wire:navigate class="btn btn-ghost py-1 px-2 text-xs text-blue-600 hover:bg-blue-50" style="text-decoration:none;">تعديل</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                <div class="text-center py-10 text-gray-300">
                    <p class="text-sm">لا توجد أوامر شراء لهذا المورد</p>
                </div>
                @endif
            </div>

            <div class="card overflow-hidden">
                <div class="flex items-center justify-between px-4 py-3 border-b border-[#E2E8F0]">
                    <p class="text-sm font-bold text-[#1E293B]">آخر الدفعات</p>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-400">{{ $allPayments->count() }} دفعة</span>
                        @if(auth()->user()->isAccountant())
                        <a href="{{ route('supplier-payments.create', ['supplier' => $supplier->id]) }}" wire:navigate class="btn btn-primary text-xs py-1 px-2" style="text-decoration:none;">دفعة جديدة</a>
                        @endif
                    </div>
                </div>
                @if($allPayments->isNotEmpty())
                <table class="data-table">
                    <thead><tr><th>التاريخ</th><th>الطريقة</th><th>المبلغ</th><th class="w-32"></th></tr></thead>
                    <tbody>
                        @foreach($allPayments->take(10) as $pay)
                        @php $m = $pay->method ?? ''; @endphp
                        <tr>
                            <td class="text-gray-500 font-mono text-xs" dir="ltr">{{ $pay->paid_at?->format('Y-m-d') ?? '—' }}</td>
                            <td><span class="badge badge-blue">{{ $m==='cash'?'نقداً':($m==='bank'?'بنك':($m==='check'?'شيك':($m==='transfer'?'تحويل':$m))) }}</span></td>
                            <td class="font-bold text-green-600 text-sm" dir="ltr">
                                {{ number_format((float) $pay->amount, 2) }}
                                <span class="text-xs text-gray-400 font-normal">{{ $pay->currency_code }}</span>
                            </td>
                            <td>
                                <div class="flex items-center gap-1 justify-end flex-wrap">
                                    <a href="{{ route('supplier-payments.show', $pay) }}" wire:navigate class="btn btn-ghost py-1 px-2 text-xs text-gray-500 hover:bg-gray-50" style="text-decoration:none;">عرض</a>
                                    @if(auth()->user()->isAccountant())
                                    <a href="{{ route('supplier-payments.edit', $pay) }}" wire:navigate class="btn btn-ghost py-1 px-2 text-xs text-blue-600 hover:bg-blue-50" style="text-decoration:none;">تعديل</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                <div class="text-center py-10 text-gray-300">
                    <p class="text-sm">لا توجد دفعات لهذا المورد</p>
                </div>
                @endif
            </div>

        </div>
    </div>

</div>
</x-layouts.app>
