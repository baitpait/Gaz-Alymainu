<x-layouts.app :title="$invoice->legacy_invoice_no ?? 'فاتورة #'.$invoice->id">

<div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('invoices.index') }}" wire:navigate
           class="w-9 h-9 flex items-center justify-center rounded-full border border-[#E2E8F0] bg-white text-[#6B7280] hover:bg-[#F3F4F6]">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
        <div>
            @php $s = $invoice->status; @endphp
            <div class="flex items-center gap-2 flex-wrap">
                <h1 class="text-xl font-bold text-[#1E293B]">{{ $invoice->legacy_invoice_no ?? 'فاتورة #'.$invoice->id }}</h1>
                <span class="badge {{ $s==='issued' ? 'badge-green' : ($s==='draft' ? 'badge-yellow' : 'badge-red') }}">
                    {{ $s==='issued' ? 'صادرة' : ($s==='draft' ? 'مسودة' : 'ملغاة') }}
                </span>
                @if($paymentStatus ?? null)
                @include('livewire.partials.invoice-payment-status-badge', ['paymentStatus' => $paymentStatus])
                @endif
            </div>
            <p class="text-sm text-[#1B6CA8] font-semibold mt-0.5">{{ $invoice->client?->displayName() ?? '—' }}</p>
        </div>
    </div>
    <div class="flex items-center gap-2">
        <x-document-export-buttons
            :print-url="route('invoices.print', $invoice->id)"
            :pdf-url="route('invoices.pdf', $invoice->id)"
        />
        @if(auth()->user()->isAccountant())
        <a href="{{ route('invoices.edit', $invoice->id) }}" wire:navigate
           class="btn btn-primary text-xs" style="text-decoration:none;">تعديل</a>
        @endif
    </div>
</div>

<div style="display:flex;gap:20px;align-items:flex-start;">

    {{-- الشريط الجانبي --}}
    <div style="width:240px;flex-shrink:0;">
        <div class="card p-4 space-y-3">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wide mb-1">تفاصيل الفاتورة</p>

            <div class="flex justify-between text-sm">
                <span class="text-gray-500">تاريخ الفاتورة</span>
                <span class="font-medium" dir="ltr">{{ $invoice->document_date?->format('Y-m-d') ?? '—' }}</span>
            </div>

            @if($invoice->due_date)
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">تاريخ الاستحقاق</span>
                <span class="font-medium {{ $invoice->due_date->isPast() && $s==='issued' ? 'text-red-500' : '' }}" dir="ltr">
                    {{ $invoice->due_date->format('Y-m-d') }}
                </span>
            </div>
            @endif

            <div class="flex justify-between text-sm">
                <span class="text-gray-500">العميل</span>
                <a href="{{ route('clients.show', $invoice->client_id) }}" wire:navigate
                   class="font-medium text-[#1B6CA8] hover:underline">
                    {{ $invoice->client?->displayName() ?? '—' }}
                </a>
            </div>

            <div class="flex justify-between text-sm">
                <span class="text-gray-500">العملة</span>
                <span class="font-mono font-semibold" dir="ltr">{{ $invoice->currency_code }}</span>
            </div>

            @if($invoice->recordedBy)
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">أُدخل بواسطة</span>
                <span class="font-medium">{{ $invoice->recordedBy->full_name }}</span>
            </div>
            @endif

            @if($invoice->notes)
            <div class="pt-2 border-t border-[#E2E8F0]">
                <p class="text-xs text-gray-500 mb-1">ملاحظات</p>
                <p class="text-sm text-[#1E293B] bg-amber-50 rounded-lg p-2">{{ $invoice->notes }}</p>
            </div>
            @endif
        </div>
    </div>

    {{-- المحتوى الرئيسي --}}
    <div style="flex:1;min-width:0;">
        <div class="card overflow-hidden">

            {{-- بنود الفاتورة --}}
            @if($invoice->lines->isNotEmpty())
            <div class="overflow-x-auto [-webkit-overflow-scrolling:touch]">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>البند</th>
                            <th class="w-24 text-center">الكمية</th>
                            <th class="w-28 text-left" dir="ltr">سعر الوحدة</th>
                            <th class="w-28 text-left" dir="ltr">المجموع</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoice->lines as $line)
                        <tr>
                            <td>
                                <div class="font-semibold text-[#1E293B]">{{ $line->title }}</div>
                                @if($line->description)
                                <div class="text-xs text-gray-400 mt-0.5">{{ $line->description }}</div>
                                @endif
                            </td>
                            <td class="text-center text-gray-500">
                                {{ rtrim(rtrim(number_format((float)$line->quantity, 2), '0'), '.') }}
                            </td>
                            <td class="font-mono text-sm" dir="ltr">
                                {{ number_format((float)$line->unit_price, 2) }}
                            </td>
                            <td class="font-mono font-semibold text-sm" dir="ltr">
                                {{ number_format((float)$line->line_total, 2) }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-center py-16 text-gray-300">
                <p class="text-sm">لا توجد بنود</p>
            </div>
            @endif

            {{-- الإجمالي --}}
            <div class="px-5 py-4 border-t border-[#E2E8F0] space-y-2">
                @php $subtotal = $invoice->lines->sum(fn($l) => (float)$l->line_total); @endphp

                @if($invoice->lines->isNotEmpty())
                <div class="flex justify-end gap-8 text-sm text-gray-500">
                    <span>المجموع الفرعي</span>
                    <span class="font-mono w-28 text-left" dir="ltr">{{ number_format($subtotal, 2) }} {{ $invoice->currency_code }}</span>
                </div>
                @endif

                @if($invoice->discount_amount && $invoice->discount_amount > 0)
                <div class="flex justify-end gap-8 text-sm text-orange-500">
                    <span>الخصم</span>
                    <span class="font-mono w-28 text-left" dir="ltr">- {{ number_format((float)$invoice->discount_amount, 2) }} {{ $invoice->currency_code }}</span>
                </div>
                @endif

                <div class="flex justify-end gap-8 border-t border-[#E2E8F0] pt-2">
                    <span class="font-bold text-[#1E293B]">الإجمالي</span>
                    <span class="font-mono font-bold text-lg text-[#1E293B] w-28 text-left" dir="ltr">
                        {{ number_format((float)$invoice->total_amount, 2) }}
                        <span class="text-xs font-normal text-gray-400">{{ $invoice->currency_code }}</span>
                    </span>
                </div>
            </div>

        </div>
    </div>

</div>

</x-layouts.app>
