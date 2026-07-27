<div>
    {{-- رأس الصفحة --}}
    <div class="flex items-start justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-[#1E293B]">كشف حساب</h1>
            <p class="text-[#1B6CA8] font-semibold mt-1">{{ $client->displayName() }}</p>
        </div>
        <div class="flex gap-2 flex-wrap">
            @if(auth()->user()->isAccountant())
            <a href="{{ route('clients.adjustments.create', $client) }}" wire:navigate
               class="px-4 py-2 text-sm bg-white border border-[#E0E0E0] rounded hover:bg-[#F5F5F5] font-medium text-[#7C3AED]">
                + تسوية على الذمة
            </a>
            @endif
            @if(auth()->user()->isManager())
            <livewire:party-currency-converter party-type="client" :party-id="$client->id" />
            @endif
            @can('exportStatement', $client)
            <button wire:click="exportCsv"
                    class="px-4 py-2 text-sm bg-white border border-[#E0E0E0] rounded hover:bg-[#F5F5F5] font-medium">
                تصدير CSV
            </button>
            <a href="{{ route('clients.statement.pdf', $client) }}{{ ($dateFrom || $dateTo) ? '?'.http_build_query(array_filter(['date_from' => $dateFrom ?: null, 'date_to' => $dateTo ?: null])) : '' }}"
               target="_blank"
               class="px-4 py-2 text-sm bg-[#1B6CA8] text-white rounded hover:opacity-90 font-medium">
                طباعة PDF
            </a>
            @endcan
        </div>
    </div>

    {{-- فلاتر التاريخ --}}
    <form wire:submit.prevent="applyStatementFilters" class="bg-white border border-[#E0E0E0] rounded p-4 mb-6 flex flex-wrap gap-4 items-end">
        <div>
            <label class="block text-xs font-medium text-[#1E293B] mb-1">من تاريخ</label>
            <input type="date" wire:model="dateFrom"
                   class="border border-[#E0E0E0] rounded px-3 py-1.5 text-sm focus:outline-none focus:border-[#1B6CA8]">
        </div>
        <div>
            <label class="block text-xs font-medium text-[#1E293B] mb-1">إلى تاريخ</label>
            <input type="date" wire:model="dateTo"
                   class="border border-[#E0E0E0] rounded px-3 py-1.5 text-sm focus:outline-none focus:border-[#1B6CA8]">
        </div>
        @include('livewire.partials.list-filter-actions', [
            'applyMethod' => 'applyStatementFilters',
            'clearMethod' => 'resetDates',
            'showClear' => (bool) ($dateFrom || $dateTo),
        ])
    </form>

    {{-- لا بيانات --}}
    @if(empty($statement))
        <div class="text-center py-16 text-gray-400">
            لا توجد حركات مالية مسجّلة لهذا العميل.
        </div>
    @endif

    {{-- قسم لكل عملة --}}
    @foreach($statement as $currency => $section)
    @php $payMethods = ['cash' => 'نقداً', 'bank' => 'بنك', 'check' => 'شيك', 'transfer' => 'تحويل']; @endphp
    <div class="mb-8" id="currency-{{ $currency }}">

        <h2 class="text-lg font-bold text-[#1E293B] mb-3">
            عملة: <span dir="ltr" class="text-[#1B6CA8] font-mono">{{ $currency }}</span>
        </h2>

        {{-- ملخص: إجمالي الفواتير − الدفعات --}}
        <div class="bg-[#FAFAFA] border border-[#E0E0E0] rounded-lg p-4 mb-4 max-w-md">
            <div class="flex justify-between text-sm mb-2">
                <span class="text-gray-600">إجمالي الفواتير</span>
                <span class="font-mono font-semibold" dir="ltr">{{ number_format($section['total_invoiced'], 2) }} {{ $currency }}</span>
            </div>
            <div class="flex justify-between text-sm mb-2">
                <span class="text-gray-600">إجمالي الدفعات</span>
                <span class="font-mono font-semibold text-[#16A34A]" dir="ltr">{{ number_format($section['total_paid'], 2) }} {{ $currency }}</span>
            </div>
            <div class="flex justify-between text-sm mb-3">
                <span class="text-gray-600">إجمالي التسويات</span>
                <span class="font-mono font-semibold text-[#7C3AED]" dir="ltr">{{ number_format($section['total_adjusted'], 2) }} {{ $currency }}</span>
            </div>
            <div class="border-t border-[#E0E0E0] pt-3 flex justify-between font-bold">
                <span>الرصيد المستحق</span>
                <span class="font-mono {{ $section['balance'] > 0 ? 'text-[#DC2626]' : ($section['balance'] < 0 ? 'text-[#16A34A]' : 'text-[#1E293B]') }}" dir="ltr">
                    {{ number_format($section['balance'], 2) }} {{ $currency }}
                </span>
            </div>
            <p class="text-xs text-gray-400 mt-2">الرصيد = الفواتير − الدفعات − التسويات</p>
        </div>

        {{-- حركة الحساب: فواتير (مع البنود) + دفعات --}}
        @if(!empty($section['timeline']))
        <div class="mb-4">
            <h3 class="text-sm font-semibold text-[#1E293B] mb-2 bg-[#F5F5F5] px-3 py-1.5 rounded-t border border-[#E0E0E0]">
                حركة الحساب
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm border border-[#E0E0E0] rounded-b">
                    <thead class="bg-[#F5F5F5] text-[#1E293B]">
                        <tr>
                            <th class="text-right px-3 py-2 font-semibold border-b border-[#E0E0E0] w-28">التاريخ</th>
                            <th class="text-right px-3 py-2 font-semibold border-b border-[#E0E0E0]">العملية</th>
                            <th class="text-left px-3 py-2 font-semibold border-b border-[#E0E0E0] w-36" dir="ltr">المبلغ ({{ $currency }})</th>
                            <th class="text-right px-3 py-2 font-semibold border-b border-[#E0E0E0] w-44"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($section['timeline'] as $event)
                            @if($event['type'] === 'invoice')
                                @php
                                    $inv = $event['model'];
                                    $invNo = $inv->legacy_invoice_no ?? '#'.$inv->id;
                                @endphp
                                <tr class="bg-[#FAFAFA] border-b border-[#E0E0E0]">
                                    <td class="px-3 py-2 text-gray-600" dir="ltr">{{ $event['date']->format('Y-m-d') }}</td>
                                    <td class="px-3 py-2">
                                        <span class="font-bold text-[#1E293B]">فاتورة {{ $invNo }}</span>
                                        <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded mr-2">صادرة</span>
                                    </td>
                                    <td class="px-3 py-2 font-mono font-semibold" dir="ltr">+{{ number_format($event['amount'], 2) }}</td>
                                    <td class="px-3 py-2">
                                        <div class="flex items-center gap-1 justify-end flex-wrap">
                                            <a href="{{ route('invoices.show', $inv) }}" wire:navigate class="btn btn-ghost py-1 px-2 text-xs text-gray-500 hover:bg-gray-50" style="text-decoration:none;">عرض</a>
                                            <a href="{{ route('invoices.print', $inv) }}" target="_blank" class="btn btn-ghost py-1 px-2 text-xs text-[#1B6CA8] hover:bg-amber-50" style="text-decoration:none;">طباعة</a>
                                            @if(auth()->user()->isAccountant())
                                            <a href="{{ route('invoices.edit', $inv) }}" wire:navigate class="btn btn-ghost py-1 px-2 text-xs text-blue-600 hover:bg-blue-50" style="text-decoration:none;">تعديل</a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @if($inv->lines->isNotEmpty())
                                <tr class="border-b border-[#E0E0E0]">
                                    <td colspan="4" class="p-0">
                                        <table class="w-full text-xs">
                                            <thead>
                                                <tr class="bg-[#1B6CA8] text-white">
                                                    <th class="text-right px-3 py-1.5 font-semibold">البند</th>
                                                    <th class="text-right px-3 py-1.5 font-semibold">التفاصيل</th>
                                                    <th class="text-center px-3 py-1.5 font-semibold w-20">الكمية</th>
                                                    <th class="text-left px-3 py-1.5 font-semibold w-28" dir="ltr">سعر الوحدة</th>
                                                    <th class="text-left px-3 py-1.5 font-semibold w-28" dir="ltr">الإجمالي</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($inv->lines as $line)
                                                <tr class="border-t border-[#E8E8E8] bg-white">
                                                    <td class="px-3 py-2">
                                                        <span class="font-medium text-[#1E293B]">{{ $line->title }}</span>
                                                    </td>
                                                    <td class="px-3 py-2 text-gray-500">
                                                        {{ $line->displayDetails() ?? '—' }}
                                                    </td>
                                                    <td class="px-3 py-2 text-center text-gray-500">
                                                        {{ rtrim(rtrim(number_format((float) $line->quantity, 2), '0'), '.') }}
                                                    </td>
                                                    <td class="px-3 py-2 font-mono" dir="ltr">
                                                        {{ number_format((float) $line->unit_price, 2) }}
                                                    </td>
                                                    <td class="px-3 py-2 font-mono font-semibold" dir="ltr">
                                                        {{ number_format((float) $line->line_total, 2) }}
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                                @endif
                                @if($inv->notes)
                                <tr class="border-b border-[#E0E0E0] bg-amber-50/50">
                                    <td colspan="4" class="px-3 py-2 text-xs text-amber-900">
                                        <span class="font-semibold">ملاحظات:</span> {{ $inv->notes }}
                                    </td>
                                </tr>
                                @endif
                            @elseif($event['type'] === 'payment')
                                @php
                                    $pay = $event['model'];
                                    $payRef = $pay->bank_reference ?? ('#'.$pay->id);
                                    $methodLabel = $payMethods[$pay->method] ?? $pay->method ?? '—';
                                @endphp
                                <tr class="bg-[#FFFDF5] border-b border-[#E0E0E0]">
                                    <td class="px-3 py-2 text-gray-600" dir="ltr">{{ $event['date']->format('Y-m-d') }}</td>
                                    <td class="px-3 py-2">
                                        <span class="font-semibold text-[#1E293B]">دفعة {{ $payRef }}</span>
                                        <span class="text-xs text-gray-500 mr-2">({{ $methodLabel }})</span>
                                        @if($pay->notes)
                                        <p class="text-xs text-gray-400 mt-0.5">{{ $pay->notes }}</p>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 font-mono font-semibold text-[#16A34A]" dir="ltr">−{{ number_format($event['amount'], 2) }}</td>
                                    <td class="px-3 py-2">
                                        <div class="flex items-center gap-1 justify-end flex-wrap">
                                            <a href="{{ route('payments.show', $pay) }}" wire:navigate class="btn btn-ghost py-1 px-2 text-xs text-gray-500 hover:bg-gray-50" style="text-decoration:none;">عرض</a>
                                            @if(auth()->user()->isAccountant())
                                            <a href="{{ route('payments.edit', $pay) }}" wire:navigate class="btn btn-ghost py-1 px-2 text-xs text-blue-600 hover:bg-blue-50" style="text-decoration:none;">تعديل</a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @else
                                @php $adj = $event['model']; @endphp
                                <tr class="bg-[#F5F3FF] border-b border-[#E0E0E0]">
                                    <td class="px-3 py-2 text-gray-600" dir="ltr">{{ $event['date']->format('Y-m-d') }}</td>
                                    <td class="px-3 py-2">
                                        <span class="font-semibold text-[#7C3AED]">تسوية #{{ $adj->id }}</span>
                                        <span class="text-xs bg-purple-100 text-purple-700 px-2 py-0.5 rounded mr-2">{{ $adj->typeLabel() }}</span>
                                        @if($adj->reason)
                                        <p class="text-xs text-gray-500 mt-0.5">{{ $adj->reason }}</p>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 font-mono font-semibold text-[#7C3AED]" dir="ltr">−{{ number_format($event['amount'], 2) }}</td>
                                    <td class="px-3 py-2">
                                        @if(auth()->user()->isAccountant())
                                        <a href="{{ route('clients.adjustments.edit', [$client, $adj]) }}" wire:navigate class="btn btn-ghost py-1 px-2 text-xs text-blue-600 hover:bg-blue-50" style="text-decoration:none;">تعديل</a>
                                        @endif
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
    @endforeach

</div>
