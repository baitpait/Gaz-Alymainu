<x-layouts.app :title="'فاتورة مشتريات — '.($purchaseOrder->legacy_po_no ?? '#'.$purchaseOrder->id)">
<div class="max-w-4xl mx-auto">

    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('purchase-orders.index', ['po_supplier' => $purchaseOrder->supplier_id]) }}" wire:navigate
           class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-[#E2E8F0] text-[#9CA3AF] hover:text-[#1E293B] transition"
           style="text-decoration:none;">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </a>
        <div>
            <h1 class="text-xl font-bold text-[#1E293B]">فاتورة مشتريات</h1>
            <p class="text-sm text-[#1B6CA8] font-semibold mt-0.5">{{ $purchaseOrder->supplier?->displayName() ?? '—' }}</p>
        </div>
    </div>

    <div class="card overflow-hidden mb-6">
        <div class="p-6 border-b border-[#E2E8F0] flex flex-wrap justify-between gap-4">
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-gray-500">رقم المستند</dt>
                    <dd class="font-mono font-semibold mt-1" dir="ltr">{{ $purchaseOrder->legacy_po_no ?? '#'.$purchaseOrder->id }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">تاريخ المستند</dt>
                    <dd class="font-mono mt-1" dir="ltr">{{ $purchaseOrder->document_date?->format('Y-m-d') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">الاستحقاق</dt>
                    <dd class="font-mono mt-1" dir="ltr">{{ $purchaseOrder->due_date?->format('Y-m-d') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">حالة المستند</dt>
                    <dd class="mt-1">
                        @php $s = $purchaseOrder->status; @endphp
                        <span class="badge {{ $s==='issued' ? 'badge-green' : ($s==='draft' ? 'badge-yellow' : 'badge-red') }}">
                            {{ $s==='issued' ? 'صادر' : ($s==='draft' ? 'مسودة' : 'ملغى') }}
                        </span>
                    </dd>
                </div>
                @if($paymentStatus ?? null)
                <div>
                    <dt class="text-gray-500">حالة الدفع</dt>
                    <dd class="mt-1">
                        @include('livewire.partials.invoice-payment-status-badge', ['paymentStatus' => $paymentStatus])
                    </dd>
                </div>
                @endif
            </dl>
            <div class="text-left">
                <p class="text-xs text-gray-500">الإجمالي</p>
                <p class="text-2xl font-bold text-[#1E293B] font-mono" dir="ltr">
                    {{ number_format((float) $purchaseOrder->total_amount, 2) }}
                    <span class="text-sm font-normal text-gray-400">{{ $purchaseOrder->currency_code }}</span>
                </p>
            </div>
        </div>
        @if($purchaseOrder->notes)
        <div class="px-6 py-4 bg-amber-50 border-b border-amber-100 text-sm text-amber-900 whitespace-pre-wrap">{{ $purchaseOrder->notes }}</div>
        @endif
    </div>

    <div class="card overflow-hidden">
        <h2 class="text-base font-semibold text-[#1E293B] px-5 py-4 border-b border-[#E2E8F0]">البنود</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>البند</th>
                    <th class="text-left" dir="ltr">الكمية</th>
                    <th class="text-left" dir="ltr">سعر الوحدة</th>
                    <th class="text-left" dir="ltr">الإجمالي</th>
                </tr>
            </thead>
            <tbody>
                @forelse($purchaseOrder->lines as $line)
                <tr>
                    <td class="text-gray-400 text-xs">{{ $loop->iteration }}</td>
                    <td class="font-medium">{{ $line->title }}</td>
                    <td class="font-mono text-xs" dir="ltr">{{ (int) $line->quantity }}</td>
                    <td class="font-mono text-xs" dir="ltr">{{ number_format((float) $line->unit_price, 2) }}</td>
                    <td class="font-mono text-xs font-semibold" dir="ltr">{{ number_format((float) $line->line_total, 2) }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center py-10 text-gray-300 text-sm">لا توجد بنود مسجّلة</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="flex justify-end gap-2 mt-6">
        <x-document-export-buttons
            :print-url="route('purchase-orders.print', $purchaseOrder)"
            :pdf-url="route('purchase-orders.pdf', $purchaseOrder)"
        />
        @can('update', $purchaseOrder)
        <a href="{{ route('purchase-orders.edit', $purchaseOrder) }}" wire:navigate class="btn btn-primary" style="text-decoration:none;">تعديل</a>
        @endcan
        <a href="{{ route('suppliers.statement', $purchaseOrder->supplier_id) }}" wire:navigate class="btn btn-secondary" style="text-decoration:none;">كشف حساب المورد</a>
        <a href="{{ route('purchase-orders.index', ['po_supplier' => $purchaseOrder->supplier_id]) }}" wire:navigate class="btn btn-secondary" style="text-decoration:none;">رجوع</a>
    </div>
</div>
</x-layouts.app>
