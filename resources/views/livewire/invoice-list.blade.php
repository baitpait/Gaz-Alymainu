<div>

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-bold text-[#1E293B]">الفواتير</h1>
        <p class="text-sm text-gray-400 mt-0.5">{{ $rows->total() }} فاتورة مسجّلة</p>
    </div>
    @if(auth()->user()->isAccountant())
    <a href="{{ route('invoices.create') }}" wire:navigate class="btn btn-primary" style="font-size:13px;text-decoration:none;">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        فاتورة جديدة
    </a>
    @endif
</div>

<form wire:submit.prevent="applyInvoiceFilters" class="card p-4 mb-5">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between lg:gap-6">
        <div class="flex min-w-0 flex-1 flex-col gap-3">
            <div class="min-w-0">
                <label class="label">بحث</label>
                <input wire:model="search" type="search" placeholder="بحث باسم العميل أو رقم الفاتورة..." class="input w-full">
            </div>
            <div class="grid min-w-0 grid-cols-1 gap-3 sm:grid-cols-2 {{ count($invoiceCurrencies) > 0 ? 'md:grid-cols-3' : '' }}">
                <div class="min-w-0">
                    <label class="label">الحالة</label>
                    <select wire:model="filterStatus" class="input w-full">
                        <option value="">الكل</option>
                        <option value="draft">مسودة</option>
                        <option value="issued">صادرة</option>
                        <option value="void">ملغاة</option>
                    </select>
                </div>
                <div class="min-w-0">
                    <label class="label">العميل</label>
                    <select wire:model="filterClientId" class="input w-full">
                        <option value="">كل العملاء</option>
                        @foreach($clients as $c)
                            <option value="{{ $c->id }}">{{ $c->displayName() }}</option>
                        @endforeach
                    </select>
                </div>
                @if(count($invoiceCurrencies) > 0)
                <div class="min-w-0 sm:col-span-2 md:col-span-1">
                    <label class="label">العملة</label>
                    <select wire:model="filterCurrency" class="input w-full">
                        <option value="">كل العملات</option>
                        @foreach($invoiceCurrencies as $code)
                            <option value="{{ $code }}">{{ $code }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
            </div>
            {{-- صف 2: التاريخان في نفس الصف من sm فما فوق --}}
            <div class="grid min-w-0 grid-cols-1 gap-3 sm:grid-cols-2">
                <div class="min-w-0">
                    <label class="label">من تاريخ الفاتورة</label>
                    <input wire:model="filterDateFrom" type="date" class="input w-full" dir="ltr">
                </div>
                <div class="min-w-0">
                    <label class="label">إلى تاريخ الفاتورة</label>
                    <input wire:model="filterDateTo" type="date" class="input w-full" dir="ltr">
                </div>
            </div>
        </div>
        @include('livewire.partials.list-filter-actions', [
            'applyMethod' => 'applyInvoiceFilters',
            'clearMethod' => 'clearInvoiceFilters',
            'showClear' => $this->hasActiveInvoiceFilters(),
        ])
    </div>
</form>

<div class="card overflow-hidden">
    <div wire:loading.delay class="h-0.5 bg-[#1B6CA8]/20 relative overflow-hidden">
        <div class="absolute inset-y-0 right-0 w-1/3 bg-[#1B6CA8] animate-pulse"></div>
    </div>
    <div class="overflow-x-auto [-webkit-overflow-scrolling:touch]">
        <table class="data-table">
            <thead><tr>
                <th>التاريخ</th><th>رقم الفاتورة</th><th>العميل</th><th>المستند</th><th>الدفع</th><th>المبلغ</th><th class="w-28"></th>
            </tr></thead>
            <tbody>
                @forelse($rows as $inv)
                <tr>
                    <td class="text-gray-500 font-mono text-xs" dir="ltr">{{ $inv->document_date?->format('Y-m-d') ?? '—' }}</td>
                    <td class="font-medium">{{ $inv->legacy_invoice_no ?? '#'.$inv->id }}</td>
                    <td>{{ $inv->client?->displayName() ?? '—' }}</td>
                    <td>
                        @php $s = $inv->status; @endphp
                        <span class="badge {{ $s==='issued' ? 'badge-green' : ($s==='draft' ? 'badge-yellow' : 'badge-red') }}">
                            {{ $s==='issued' ? 'صادرة' : ($s==='draft' ? 'مسودة' : 'ملغاة') }}
                        </span>
                    </td>
                    <td>
                        @include('livewire.partials.invoice-payment-status-badge', [
                            'paymentStatus' => $paymentStatuses[$inv->id] ?? null,
                        ])
                    </td>
                    <td class="font-mono font-semibold text-xs" dir="ltr">
                        {{ number_format((float)$inv->total_amount,2) }}
                        <span class="text-gray-400 font-normal">{{ $inv->currency_code }}</span>
                    </td>
                    <td>
                        <div class="flex items-center gap-1 justify-end">
                            <a href="{{ route('invoices.show', $inv->id) }}" wire:navigate class="btn btn-ghost py-1 px-2 text-xs text-gray-500 hover:bg-gray-50" style="text-decoration:none;">عرض</a>
                            <x-document-export-buttons
                                :print-url="route('invoices.print', $inv->id)"
                                :pdf-url="route('invoices.pdf', $inv->id)"
                            />
                            @if(auth()->user()->isAccountant())
                            <a href="{{ route('invoices.edit', $inv->id) }}" wire:navigate class="btn btn-ghost py-1 px-2 text-xs text-blue-600 hover:bg-blue-50" style="text-decoration:none;">تعديل</a>
                            @endif
                            @if(auth()->user()->isManager())
                            <button wire:click="confirmDelete({{ $inv->id }})" class="btn btn-ghost py-1 px-2 text-xs text-red-500 hover:bg-red-50">حذف</button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7">
                    <div class="text-center py-16 text-gray-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <p class="text-sm">{{ $search ? 'لا توجد نتائج' : 'لا توجد فواتير بعد' }}</p>
                    </div>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <x-list-pagination :paginator="$rows" />
</div>

{{-- ══ نافذة العرض التفصيلي ══ --}}
@if($viewingId !== null)
<div wire:key="view-{{ $viewingId }}"
     class="fixed inset-0 z-50 flex items-end sm:items-center justify-center">
    <div class="fixed inset-0 bg-black/40 backdrop-blur-[2px]" wire:click="closeView"></div>
    <div class="relative bg-white rounded-t-2xl sm:rounded-2xl shadow-2xl w-full max-w-lg mx-0 sm:mx-4 z-10 max-h-[90vh] overflow-y-auto">
        @if($viewingRecord)
        @php $inv = $viewingRecord; $s = $inv->status; @endphp
        <div class="p-6">
            <div class="flex items-start justify-between mb-5">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <h2 class="text-lg font-bold text-[#1E293B]">{{ $inv->legacy_invoice_no ?? 'فاتورة #'.$inv->id }}</h2>
                        <span class="badge {{ $s==='issued' ? 'badge-green' : ($s==='draft' ? 'badge-yellow' : 'badge-red') }}">
                            {{ $s==='issued' ? 'صادرة' : ($s==='draft' ? 'مسودة' : 'ملغاة') }}
                        </span>
                    </div>
                    <p class="text-sm text-[#1B6CA8] font-semibold">{{ $inv->client?->displayName() ?? '—' }}</p>
                </div>
                <button wire:click="closeView" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 text-gray-400 transition shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <dl class="divide-y divide-[#E2E8F0]">
                <div class="flex justify-between py-3">
                    <dt class="text-sm text-gray-500">تاريخ الفاتورة</dt>
                    <dd class="text-sm font-medium text-[#1E293B]" dir="ltr">{{ $inv->document_date?->format('Y-m-d') ?? '—' }}</dd>
                </div>
                @if($inv->due_date)
                <div class="flex justify-between py-3">
                    <dt class="text-sm text-gray-500">تاريخ الاستحقاق</dt>
                    <dd class="text-sm font-medium {{ $inv->due_date->isPast() && $s === 'issued' ? 'text-red-500' : 'text-[#1E293B]' }}" dir="ltr">{{ $inv->due_date->format('Y-m-d') }}</dd>
                </div>
                @endif
                <div class="flex justify-between py-3">
                    <dt class="text-sm text-gray-500">العميل</dt>
                    <dd class="text-sm font-medium text-[#1E293B]">{{ $inv->client?->displayName() ?? '—' }}</dd>
                </div>
                @if($inv->notes)
                <div class="py-3">
                    <dt class="text-sm text-gray-500 mb-1">ملاحظات</dt>
                    <dd class="text-sm text-[#1E293B] bg-amber-50 rounded-lg p-2">{{ $inv->notes }}</dd>
                </div>
                @endif
            </dl>

            {{-- بنود الفاتورة --}}
            @if($inv->lines->isNotEmpty())
            <div class="mt-4 mb-2">
                <p class="text-xs font-semibold text-[#1B6CA8] mb-2 uppercase tracking-wide">بنود الفاتورة</p>
                <table class="w-full text-xs border-collapse">
                    <thead>
                        <tr class="bg-[#1E293B] text-white">
                            <th class="text-right px-3 py-2 rounded-r-md">البند</th>
                            <th class="text-center px-2 py-2">الكمية</th>
                            <th class="text-left px-3 py-2 rounded-l-md" dir="ltr">المجموع</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($inv->lines as $line)
                        <tr class="{{ $loop->even ? 'bg-gray-50' : '' }}">
                            <td class="px-3 py-2 border-b border-[#E2E8F0]">
                                <div class="font-semibold text-[#1E293B]">{{ $line->title }}</div>
                                @if($line->description)<div class="text-gray-400 text-[10px] mt-0.5">{{ $line->description }}</div>@endif
                            </td>
                            <td class="px-2 py-2 text-center border-b border-[#E2E8F0] text-gray-500">{{ rtrim(rtrim(number_format((float)$line->quantity, 2), '0'), '.') }}</td>
                            <td class="px-3 py-2 border-b border-[#E2E8F0] font-mono font-semibold text-[#1E293B]" dir="ltr">{{ number_format((float)$line->line_total, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif

            {{-- الإجمالي --}}
            <div class="mt-3 pt-3 border-t border-[#E2E8F0] space-y-1">
                @if($inv->discount_amount && $inv->discount_amount > 0)
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">الخصم</span>
                    <span class="text-orange-500 font-medium" dir="ltr">- {{ number_format((float)$inv->discount_amount, 2) }} {{ $inv->currency_code }}</span>
                </div>
                @endif
                <div class="flex justify-between">
                    <span class="text-sm font-bold text-[#1E293B]">الإجمالي</span>
                    <span class="text-base font-bold text-[#1E293B]" dir="ltr">
                        {{ number_format((float)$inv->total_amount, 2) }}
                        <span class="text-xs font-normal text-gray-400">{{ $inv->currency_code }}</span>
                    </span>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-4 border-t border-[#E2E8F0] mt-4">
                <button wire:click="closeView" class="btn btn-secondary text-xs">إغلاق</button>
                <x-document-export-buttons
                    :print-url="route('invoices.print', $inv->id)"
                    :pdf-url="route('invoices.pdf', $inv->id)"
                />
                @if(auth()->user()->isAccountant())
                <a href="{{ route('invoices.edit', $inv->id) }}" wire:navigate class="btn btn-primary text-xs" style="text-decoration:none;">تعديل</a>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>
@endif

{{-- ══ نافذة الإضافة / التعديل (تم نقلها إلى صفحة كاملة) ══ --}}
@if(false)
<div wire:key="modal-{{ $editingId ?? 'new' }}"
     class="fixed inset-0 z-50 flex items-center justify-center">
    <div class="fixed inset-0 bg-black/40 backdrop-blur-[2px]" wire:click="closeModal"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl z-10"
         style="width:calc(100vw - 40px);max-width:900px;height:90vh;display:flex;flex-direction:column;">

        {{-- ── رأس ثابت ── --}}
        <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-bottom:1px solid #E2E8F0;flex-shrink:0;">
            <h2 style="font-size:15px;font-weight:800;color:#1E293B;">
                {{ $editingId ? 'تعديل الفاتورة' : 'فاتورة جديدة' }}
            </h2>
            <button wire:click="closeModal" style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;border-radius:50%;border:none;background:transparent;cursor:pointer;color:#9CA3AF;" onmouseover="this.style.background='#F3F4F6'" onmouseout="this.style.background='transparent'">
                <svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- ── جسم النافذة (عمودان) ── --}}
        <div style="display:flex;flex:1;overflow:hidden;min-height:0;">

            {{-- العمود الأيمن: معلومات الفاتورة ── --}}
            <div style="width:260px;flex-shrink:0;border-left:1px solid #E2E8F0;overflow-y:auto;padding:16px;display:flex;flex-direction:column;gap:10px;background:#FAFAFA;">

                <p style="font-size:10px;font-weight:700;color:#9CA3AF;letter-spacing:.06em;text-transform:uppercase;margin-bottom:2px;">معلومات الفاتورة</p>

                @include('livewire.partials.client-select-with-search', [
                    'clients' => $clients,
                    'inputStyle' => 'font-size:12px;padding:6px 10px;',
                    'selectStyle' => 'font-size:12px;padding:6px 10px;',
                    'placeholder' => '— اختر —',
                    'hintStyle' => 'font-size:10px;',
                ])

                <div style="display:flex;gap:8px;">
                    <div style="flex:1;">
                        <label class="label">رقم الفاتورة</label>
                        <input wire:model="legacy_invoice_no" type="text" dir="ltr" class="input" style="font-size:12px;padding:6px 10px;">
                    </div>
                    <div style="flex:1;">
                        <label class="label">الحالة <span class="text-red-400">*</span></label>
                        <select wire:model="status" class="input select" style="font-size:12px;padding:6px 10px;">
                            <option value="draft">مسودة</option>
                            <option value="issued">صادرة</option>
                            <option value="cancelled">ملغاة</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="label">تاريخ الفاتورة <span class="text-red-400">*</span></label>
                    <input wire:model="document_date" type="date" class="input" style="font-size:12px;padding:6px 10px;">
                    @error('document_date')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="label">تاريخ الاستحقاق</label>
                    <input wire:model="due_date" type="date" class="input" style="font-size:12px;padding:6px 10px;">
                    @error('due_date')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="label">العملة <span class="text-red-400">*</span></label>
                    <select wire:model="currency_code" class="input select" style="font-size:12px;padding:6px 10px;">
                        <option value="ILS">ILS — شيكل</option>
                        <option value="USD">USD — دولار</option>
                        <option value="JOD">JOD — دينار</option>
                        <option value="EUR">EUR — يورو</option>
                    </select>
                </div>

                <div>
                    <label class="label">ملاحظات</label>
                    <textarea wire:model="notes" rows="3" class="input" style="font-size:12px;padding:6px 10px;resize:none;"></textarea>
                </div>

                {{-- الإجمالي في الشريط الجانبي --}}
                <div style="margin-top:auto;background:#fff;border:1px solid #E2E8F0;border-radius:12px;padding:12px;margin-top:8px;">
                    @php $subtotal = collect($lines)->sum(fn($l) => (float)($l['line_total'] ?? 0)); @endphp
                    <div style="display:flex;justify-content:space-between;font-size:12px;color:#6B7280;margin-bottom:5px;">
                        <span>المجموع الفرعي</span>
                        <span dir="ltr">{{ number_format($subtotal, 2) }}</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:6px;margin-bottom:5px;">
                        <label style="font-size:11px;color:#6B7280;white-space:nowrap;">خصم:</label>
                        <input wire:model.live="discount_amount" type="number" step="0.01" min="0" dir="ltr"
                               placeholder="0.00" class="input" style="font-size:12px;padding:4px 8px;flex:1;">
                    </div>
                    @if(empty($lines))
                    <div style="display:flex;align-items:center;gap:6px;margin-bottom:5px;">
                        <label style="font-size:11px;color:#6B7280;white-space:nowrap;">الإجمالي:</label>
                        <input wire:model.live="total_amount" type="number" step="0.01" min="0" dir="ltr"
                               placeholder="0.00" class="input" style="font-size:12px;padding:4px 8px;flex:1;">
                    </div>
                    @error('total_amount')<p class="field-error">{{ $message }}</p>@enderror
                    @endif
                    <div style="border-top:1px solid #E2E8F0;padding-top:8px;margin-top:4px;display:flex;justify-content:space-between;align-items:center;">
                        <span style="font-size:12px;font-weight:700;color:#1E293B;">الإجمالي</span>
                        <span style="font-size:16px;font-weight:900;color:#1B6CA8;" dir="ltr">
                            {{ number_format((float)$total_amount, 2) }}
                            <span style="font-size:11px;font-weight:500;color:#9CA3AF;">{{ $currency_code }}</span>
                        </span>
                    </div>
                </div>

            </div>

            {{-- العمود الأيسر: بنود الفاتورة ── --}}
            <div style="flex:1;overflow-y:auto;padding:16px;display:flex;flex-direction:column;gap:10px;min-width:0;">

                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <p style="font-size:10px;font-weight:700;color:#9CA3AF;letter-spacing:.06em;text-transform:uppercase;">بنود الفاتورة</p>
                    <button type="button" wire:click="addLine"
                            style="display:flex;align-items:center;gap:4px;font-size:12px;font-weight:700;color:#1B6CA8;background:transparent;border:none;cursor:pointer;padding:4px 8px;border-radius:8px;"
                            onmouseover="this.style.background='#FFFBEB'" onmouseout="this.style.background='transparent'">
                        <svg xmlns="http://www.w3.org/2000/svg" style="width:13px;height:13px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                        إضافة بند
                    </button>
                </div>

                @if(empty($lines))
                <div style="border:2px dashed #E2E8F0;border-radius:12px;padding:40px 20px;text-align:center;flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;">
                    <svg xmlns="http://www.w3.org/2000/svg" style="width:40px;height:40px;color:#E2E8F0;margin-bottom:10px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    <p style="font-size:13px;color:#D1D5DB;margin-bottom:12px;">لا توجد بنود بعد</p>
                    <button type="button" wire:click="addLine"
                            style="background:#1B6CA8;color:#fff;border:none;border-radius:8px;padding:8px 20px;font-size:13px;font-weight:700;cursor:pointer;">
                        + أضف أول بند
                    </button>
                </div>
                @else

                {{-- جدول البنود --}}
                <div style="border:1px solid #E2E8F0;border-radius:12px;overflow:hidden;">
                    {{-- رأس الجدول --}}
                    <div style="display:flex;background:#F9F9FB;border-bottom:1px solid #E2E8F0;font-size:11px;font-weight:600;color:#9CA3AF;">
                        <div style="flex:1;padding:8px 10px;">البند / الوصف</div>
                        <div style="width:90px;padding:8px 8px;text-align:center;">سعر الوحدة</div>
                        <div style="width:70px;padding:8px 8px;text-align:center;">الكمية</div>
                        <div style="width:85px;padding:8px 8px;text-align:center;">المجموع</div>
                        <div style="width:30px;"></div>
                    </div>

                    @foreach($lines as $i => $line)
                    <div wire:key="line-{{ $i }}" style="display:flex;align-items:flex-start;border-top:1px solid #F1F5F9;padding:6px 0;{{ $loop->even ? 'background:#FAFAFA;' : '' }}">

                        {{-- البند --}}
                        <div style="flex:1;padding:0 8px;">
                            <input wire:model.live="lines.{{ $i }}.title"
                                   type="text" placeholder="اسم البند *"
                                   class="input" style="padding:5px 8px;font-size:12px;margin-bottom:3px;font-weight:600;">
                            <input wire:model="lines.{{ $i }}.description"
                                   type="text" placeholder="وصف (اختياري)"
                                   class="input" style="padding:4px 8px;font-size:11px;background:#F9F9FB;color:#6B7280;">
                            @error("lines.{$i}.title")<p class="field-error">{{ $message }}</p>@enderror
                        </div>

                        {{-- السعر --}}
                        <div style="width:90px;padding:0 6px;">
                            <input wire:model.live="lines.{{ $i }}.unit_price"
                                   type="number" step="0.01" min="0" dir="ltr" placeholder="0.00"
                                   class="input" style="padding:5px 6px;font-size:12px;text-align:center;">
                            @error("lines.{$i}.unit_price")<p class="field-error" style="font-size:9px;">{{ $message }}</p>@enderror
                        </div>

                        {{-- الكمية --}}
                        <div style="width:70px;padding:0 6px;">
                            <input wire:model.live="lines.{{ $i }}.quantity"
                                   type="number" step="1" min="0" dir="ltr" placeholder="1"
                                   class="input" style="padding:5px 6px;font-size:12px;text-align:center;">
                            @error("lines.{$i}.quantity")<p class="field-error" style="font-size:9px;">{{ $message }}</p>@enderror
                        </div>

                        {{-- المجموع --}}
                        <div style="width:85px;padding:0 6px;display:flex;align-items:center;justify-content:center;padding-top:5px;">
                            <span style="font-weight:700;font-size:13px;color:#1E293B;" dir="ltr">
                                {{ number_format((float)($line['line_total'] ?? 0), 2) }}
                            </span>
                        </div>

                        {{-- حذف --}}
                        <div style="width:30px;display:flex;align-items:center;justify-content:center;padding-top:4px;">
                            <button type="button" wire:click="removeLine({{ $i }})"
                                    style="width:22px;height:22px;display:flex;align-items:center;justify-content:center;border-radius:50%;border:none;background:transparent;cursor:pointer;color:#D1D5DB;"
                                    onmouseover="this.style.background='#FEE2E2';this.style.color='#EF4444'"
                                    onmouseout="this.style.background='transparent';this.style.color='#D1D5DB'">
                                <svg xmlns="http://www.w3.org/2000/svg" style="width:12px;height:12px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>
                    @endforeach
                </div>

                <button type="button" wire:click="addLine"
                        style="width:100%;padding:8px;border:1.5px dashed #E2E8F0;border-radius:10px;font-size:12px;font-weight:600;color:#1B6CA8;background:transparent;cursor:pointer;transition:all .15s;"
                        onmouseover="this.style.borderColor='#1B6CA8';this.style.background='#FFFBEB'"
                        onmouseout="this.style.borderColor='#E2E8F0';this.style.background='transparent'">
                    + إضافة بند
                </button>
                @endif

            </div>
        </div>

        {{-- ── شريط الأزرار ── --}}
        <div style="display:flex;justify-content:flex-end;gap:10px;padding:12px 20px;border-top:1px solid #E2E8F0;flex-shrink:0;background:#fff;">
            <button wire:click="closeModal" class="btn btn-secondary" style="font-size:13px;">إلغاء</button>
            <button wire:click="save" wire:loading.attr="disabled" class="btn btn-primary" style="font-size:13px;">
                <svg wire:loading wire:target="save" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path></svg>
                <span wire:loading.remove wire:target="save">حفظ الفاتورة</span>
                <span wire:loading wire:target="save">جاري الحفظ...</span>
            </button>
        </div>

    </div>
</div>
@endif
{{-- ══ نهاية نافذة الإضافة / التعديل ══ --}}

@if($confirmDeleteId !== null)
<div wire:key="delete-{{ $confirmDeleteId }}"
     class="fixed inset-0 z-[60] flex items-center justify-center">
    <div class="fixed inset-0 bg-black/40 backdrop-blur-[2px]" wire:click="cancelDelete"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm mx-4 z-10 p-6">
        <div class="w-12 h-12 rounded-full bg-red-50 flex items-center justify-center mx-auto mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        </div>
        <h3 class="text-base font-bold text-center mb-1">حذف الفاتورة</h3>
        <p class="text-sm text-gray-400 text-center mb-5">هل أنت متأكد؟ لا يمكن التراجع.</p>
        <div class="flex gap-2">
            <button wire:click="cancelDelete" class="btn btn-secondary flex-1">إلغاء</button>
            <button wire:click="delete" class="btn btn-danger flex-1">حذف</button>
        </div>
    </div>
</div>
@endif

</div>
