<x-layouts.app title="تفاصيل العميل">
@php
    $allInvoices   = $client->invoices;
    $issuedInvs    = $allInvoices->where('status','issued');
    $draftInvs     = $allInvoices->where('status','draft');
    $allPayments   = $client->payments;
    $invByCur      = $issuedInvs->groupBy('currency_code');
    $payByCur      = $allPayments->groupBy('currency_code');
    $allCur        = $invByCur->keys()->merge($payByCur->keys())->unique();
@endphp

<div class="max-w-4xl mx-auto">

    {{-- الرأس --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('clients.index') }}" wire:navigate
           style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;border-radius:8px;background:#fff;border:1px solid #E2E8F0;color:#9CA3AF;text-decoration:none;transition:color .15s;"
           onmouseover="this.style.color='#1E293B'" onmouseout="this.style.color='#9CA3AF'">
            <svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </a>
        <div>
            <h1 class="text-xl font-bold text-[#1E293B]">{{ $client->displayName() }}</h1>
            @if($client->city)<p class="text-sm text-gray-400 mt-0.5">{{ $client->city }}</p>@endif
        </div>
        <div class="mr-auto flex items-center gap-2">
            @if(auth()->user()->isAccountant())
            <a href="{{ route('clients.adjustments.create', $client) }}" wire:navigate class="btn btn-secondary text-xs" style="text-decoration:none;">تسوية</a>
            @endif
            <a href="{{ route('clients.statement', $client) }}" wire:navigate class="btn btn-secondary text-xs" style="text-decoration:none;display:flex;align-items:center;gap:6px;">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                كشف حساب
            </a>
            @if(auth()->user()->isAccountant())
            <a href="{{ route('clients.edit', $client->id) }}" wire:navigate class="btn btn-primary text-xs" style="text-decoration:none;">تعديل</a>
            @endif
        </div>
    </div>

    {{-- بطاقات الإحصاء --}}
    <div style="display:flex;gap:12px;margin-bottom:20px;">
        <div class="card p-4 text-center" style="flex:1;">
            <p class="text-2xl font-bold text-[#1E293B]">{{ $allInvoices->count() }}</p>
            <p class="text-xs text-gray-400 mt-1">إجمالي الفواتير</p>
        </div>
        <div class="card p-4 text-center" style="flex:1;">
            <p class="text-2xl font-bold text-green-600">{{ $issuedInvs->count() }}</p>
            <p class="text-xs text-gray-400 mt-1">فواتير صادرة</p>
        </div>
        <div class="card p-4 text-center" style="flex:1;">
            <p class="text-2xl font-bold text-[#1B6CA8]">{{ $draftInvs->count() }}</p>
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

            {{-- معلومات التواصل --}}
            <div class="card p-4">
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">معلومات التواصل</p>
                <div class="space-y-2">
                    @if($client->email)
                    <a href="mailto:{{ $client->email }}" class="flex items-center gap-2 text-sm hover:text-[#1B6CA8] transition" style="text-decoration:none;">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <span class="text-[#1E293B] truncate" dir="ltr">{{ $client->email }}</span>
                    </a>
                    @endif
                    @if($client->phone_primary)
                    <a href="tel:{{ $client->phone_primary }}" class="flex items-center gap-2 text-sm hover:text-[#1B6CA8] transition" style="text-decoration:none;">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        <span class="text-[#1E293B]" dir="ltr">{{ $client->phone_primary }}</span>
                    </a>
                    @endif
                    @if($client->phone_secondary)
                    <div class="flex items-center gap-2 text-sm text-gray-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        <span dir="ltr">{{ $client->phone_secondary }}</span>
                    </div>
                    @endif
                    @if(!$client->email && !$client->phone_primary)
                    <p class="text-sm text-gray-300">لا توجد معلومات تواصل</p>
                    @endif
                </div>
            </div>

            {{-- الملخص المالي --}}
            @if($allCur->isNotEmpty())
            <div class="card p-4">
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">الملخص المالي</p>
                <div class="space-y-3">
                @foreach($allCur as $cur)
                @php
                    $invoiced = (float)($invByCur->get($cur)?->sum('total_amount') ?? 0);
                    $paid     = (float)($payByCur->get($cur)?->sum('amount') ?? 0);
                    $balance  = $invoiced - $paid;
                    $pct      = $invoiced > 0 ? min(100, round($paid / $invoiced * 100)) : 0;
                @endphp
                <div class="bg-[#F9F9FB] rounded-xl overflow-hidden">
                    <div class="flex items-center justify-between px-3 py-2 border-b border-[#E2E8F0]">
                        <span class="text-xs font-bold text-[#1E293B]" dir="ltr">{{ $cur }}</span>
                        <span class="badge {{ $balance <= 0 ? 'badge-green' : ($balance < $invoiced * 0.5 ? 'badge-yellow' : 'badge-red') }}" style="font-size:9px;">
                            {{ $balance <= 0 ? 'محصّل' : 'مستحق' }}
                        </span>
                    </div>
                    <div class="px-3 py-2 space-y-1">
                        <div class="flex justify-between text-xs">
                            <span class="text-gray-400">الفواتير</span>
                            <span class="font-semibold text-[#1E293B]" dir="ltr">{{ number_format($invoiced, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-gray-400">المدفوع</span>
                            <span class="font-semibold text-green-600" dir="ltr">{{ number_format($paid, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-xs font-bold pt-1 border-t border-[#E2E8F0]">
                            <span class="text-gray-500">الرصيد</span>
                            <span style="color:{{ $balance > 0 ? '#EF4444' : '#16A34A' }};" dir="ltr">{{ number_format(max(0,$balance), 2) }}</span>
                        </div>
                        @if($invoiced > 0)
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

            @if($client->notes)
            <div class="card p-4 bg-amber-50 border-amber-200">
                <p class="text-xs font-bold text-amber-600 mb-1">ملاحظات</p>
                <p class="text-sm text-amber-900">{{ $client->notes }}</p>
            </div>
            @endif

        </div>

        {{-- العمود الأيسر (محتوى رئيسي) --}}
        <div style="flex:1;min-width:0;display:flex;flex-direction:column;gap:20px;">

            {{-- آخر الفواتير --}}
            <div class="card overflow-hidden">
                <div class="flex items-center justify-between px-4 py-3 border-b border-[#E2E8F0]">
                    <p class="text-sm font-bold text-[#1E293B]">آخر الفواتير</p>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-400">{{ $allInvoices->count() }} فاتورة</span>
                        @if(auth()->user()->isAccountant())
                        <a href="{{ route('invoices.create', ['client' => $client->id]) }}" wire:navigate class="btn btn-primary text-xs py-1 px-2" style="text-decoration:none;">فاتورة جديدة</a>
                        @endif
                    </div>
                </div>
                @if($allInvoices->isNotEmpty())
                <div class="overflow-x-auto [-webkit-overflow-scrolling:touch]">
                    <table class="data-table">
                        <thead><tr><th>رقم / تاريخ</th><th>الحالة</th><th>المبلغ</th><th class="w-40"></th></tr></thead>
                        <tbody>
                            @foreach($allInvoices->take(10) as $inv)
                            @php $s = $inv->status; @endphp
                            <tr>
                                <td>
                                    <p class="font-semibold text-[#1E293B] text-sm">{{ $inv->legacy_invoice_no ?? '#'.$inv->id }}</p>
                                    <p class="text-xs text-gray-400" dir="ltr">{{ $inv->document_date?->format('Y-m-d') }}</p>
                                </td>
                                <td>
                                    <span class="badge {{ $s==='issued' ? 'badge-green' : ($s==='draft' ? 'badge-yellow' : 'badge-red') }}">
                                        {{ $s==='issued' ? 'صادرة' : ($s==='draft' ? 'مسودة' : 'ملغاة') }}
                                    </span>
                                </td>
                                <td class="font-bold text-sm" dir="ltr">
                                    {{ number_format((float)$inv->total_amount, 2) }}
                                    <span class="text-xs text-gray-400 font-normal">{{ $inv->currency_code }}</span>
                                </td>
                                <td>
                                    <div class="flex items-center gap-1 justify-end flex-wrap">
                                        <a href="{{ route('invoices.show', $inv) }}" wire:navigate class="btn btn-ghost py-1 px-2 text-xs text-gray-500 hover:bg-gray-50" style="text-decoration:none;">عرض</a>
                                        <a href="{{ route('invoices.print', $inv) }}" target="_blank" class="btn btn-ghost py-1 px-2 text-xs text-[#1B6CA8] hover:bg-amber-50" style="text-decoration:none;">طباعة</a>
                                        @if(auth()->user()->isAccountant())
                                        <a href="{{ route('invoices.edit', $inv) }}" wire:navigate class="btn btn-ghost py-1 px-2 text-xs text-blue-600 hover:bg-blue-50" style="text-decoration:none;">تعديل</a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-10 text-gray-300">
                    <p class="text-sm">لا توجد فواتير لهذا العميل</p>
                </div>
                @endif
            </div>

            {{-- آخر الدفعات --}}
            <div class="card overflow-hidden">
                <div class="flex items-center justify-between px-4 py-3 border-b border-[#E2E8F0]">
                    <p class="text-sm font-bold text-[#1E293B]">آخر الدفعات</p>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-400">{{ $allPayments->count() }} دفعة</span>
                        @if(auth()->user()->isAccountant())
                        <a href="{{ route('payments.create', ['client' => $client->id]) }}" wire:navigate class="btn btn-primary text-xs py-1 px-2" style="text-decoration:none;">دفعة جديدة</a>
                        @endif
                    </div>
                </div>
                @if($allPayments->isNotEmpty())
                <div class="overflow-x-auto [-webkit-overflow-scrolling:touch]">
                    <table class="data-table">
                        <thead><tr><th>التاريخ</th><th>الطريقة</th><th>المبلغ</th><th class="w-32"></th></tr></thead>
                        <tbody>
                            @foreach($allPayments->take(10) as $pay)
                            @php $m = $pay->method ?? ''; @endphp
                            <tr>
                                <td class="text-gray-500 font-mono text-xs" dir="ltr">{{ $pay->paid_at?->format('Y-m-d') ?? '—' }}</td>
                                <td><span class="badge badge-blue">{{ $m==='cash'?'نقداً':($m==='bank'?'بنك':($m==='check'?'شيك':($m==='transfer'?'تحويل':$m))) }}</span></td>
                                <td class="font-bold text-green-600 text-sm" dir="ltr">
                                    {{ number_format((float)$pay->amount, 2) }}
                                    <span class="text-xs text-gray-400 font-normal">{{ $pay->currency_code }}</span>
                                </td>
                                <td>
                                    <div class="flex items-center gap-1 justify-end flex-wrap">
                                        <a href="{{ route('payments.show', $pay) }}" wire:navigate class="btn btn-ghost py-1 px-2 text-xs text-gray-500 hover:bg-gray-50" style="text-decoration:none;">عرض</a>
                                        @if(auth()->user()->isAccountant())
                                        <a href="{{ route('payments.edit', $pay) }}" wire:navigate class="btn btn-ghost py-1 px-2 text-xs text-blue-600 hover:bg-blue-50" style="text-decoration:none;">تعديل</a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-10 text-gray-300">
                    <p class="text-sm">لا توجد دفعات لهذا العميل</p>
                </div>
                @endif
            </div>

        </div>
    </div>

</div>
</x-layouts.app>
