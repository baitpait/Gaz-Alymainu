<div>

{{-- ── شريط العنوان العلوي ── --}}
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;">
    <div style="display:flex;align-items:center;gap:12px;">
        <a href="{{ route('invoices.index') }}" wire:navigate
           style="width:36px;height:36px;display:flex;align-items:center;justify-content:center;border-radius:50%;border:1px solid #E2E8F0;background:#fff;color:#6B7280;text-decoration:none;transition:all .15s;"
           onmouseover="this.style.background='#F3F4F6'" onmouseout="this.style.background='#fff'">
            <svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
        <div>
            <h1 style="font-size:18px;font-weight:800;color:#1E293B;line-height:1.2;">
                {{ $invoiceId ? 'تعديل الفاتورة' : 'فاتورة جديدة' }}
            </h1>
            @if($invoiceId)
            <p style="font-size:12px;color:#9CA3AF;margin-top:2px;">تعديل الفاتورة رقم #{{ $invoiceId }}</p>
            @endif
        </div>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="{{ route('invoices.index') }}" wire:navigate class="btn btn-secondary" style="font-size:13px;text-decoration:none;">إلغاء</a>
        <button wire:click="save" wire:loading.attr="disabled" class="btn btn-primary" style="font-size:13px;min-width:120px;">
            <svg wire:loading wire:target="save" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
            </svg>
            <span wire:loading.remove wire:target="save">حفظ الفاتورة</span>
            <span wire:loading wire:target="save">جاري الحفظ...</span>
        </button>
    </div>
</div>

{{-- ترتيب: 1) معلومات الفاتورة 2) البنود بعرض كامل 3) الملاحظات بعرض كامل وموسّعة 4) الإجمالي --}}
<div style="display:flex;flex-direction:column;gap:20px;width:100%;">

    <div class="card" style="padding:20px;display:flex;flex-direction:column;gap:14px;">
        <p style="font-size:11px;font-weight:700;color:#9CA3AF;letter-spacing:.06em;text-transform:uppercase;">معلومات الفاتورة</p>

        @include('livewire.partials.client-select-with-search', ['clients' => $clients])

        <div style="display:flex;flex-wrap:wrap;gap:14px;">
            <div style="flex:1;min-width:min(100%,200px);">
                <label class="label">رقم الفاتورة</label>
                <input wire:model="legacy_invoice_no" type="text" dir="ltr" class="input" placeholder="اختياري">
            </div>
            <div style="flex:1;min-width:min(100%,200px);">
                <label class="label">حالة المستند <span class="text-red-400">*</span></label>
                <select wire:model.live="status" class="input select">
                    <option value="draft">مسودة</option>
                    <option value="issued">صادرة</option>
                    <option value="cancelled">ملغاة</option>
                </select>
            </div>
            <div style="flex:1;min-width:min(100%,200px);">
                <label class="label">تاريخ الفاتورة <span class="text-red-400">*</span></label>
                <input wire:model="document_date" type="date" class="input">
                @error('document_date')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div style="flex:1;min-width:min(100%,200px);">
                <label class="label">تاريخ الاستحقاق</label>
                <input wire:model="due_date" type="date" class="input">
                @error('due_date')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div style="flex:1;min-width:min(100%,200px);">
                <label class="label">العملة <span class="text-red-400">*</span></label>
                <select wire:model.live="currency_code" class="input select">
                    <option value="ILS">ILS — شيكل إسرائيلي</option>
                    <option value="USD">USD — دولار أمريكي</option>
                    <option value="JOD">JOD — دينار أردني</option>
                    <option value="EUR">EUR — يورو</option>
                </select>
            </div>
        </div>
    </div>

    @if(!$invoiceId && $status === 'issued')
    <div class="card" style="padding:20px;display:flex;flex-direction:column;gap:14px;">
        <p style="font-size:11px;font-weight:700;color:#9CA3AF;letter-spacing:.06em;text-transform:uppercase;">حالة الدفع</p>

        <div style="display:flex;flex-wrap:wrap;gap:10px;">
            <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;">
                <input type="radio" wire:model.live="payment_collection" value="unpaid" style="accent-color:#1B6CA8;">
                غير مدفوعة
            </label>
            <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;">
                <input type="radio" wire:model.live="payment_collection" value="partial" style="accent-color:#1B6CA8;">
                جزئية
            </label>
            <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;">
                <input type="radio" wire:model.live="payment_collection" value="paid" style="accent-color:#1B6CA8;">
                مدفوعة بالكامل
            </label>
        </div>
        @error('payment_collection')<p class="field-error">{{ $message }}</p>@enderror

        @if(in_array($payment_collection, ['partial', 'paid'], true))
        <div style="display:flex;flex-wrap:wrap;gap:14px;padding-top:4px;border-top:1px solid #F1F5F9;">
            @if($payment_collection === 'partial')
            <div style="flex:1;min-width:min(100%,180px);">
                <label class="label">مبلغ الدفعة <span class="text-red-400">*</span></label>
                <input wire:model="payment_amount" type="number" step="0.01" min="0.01" dir="ltr" class="input" placeholder="0.00">
                @error('payment_amount')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            @endif
            <div style="flex:1;min-width:min(100%,180px);">
                <label class="label">تاريخ الدفع <span class="text-red-400">*</span></label>
                <input wire:model="paid_at" type="date" class="input">
                @error('paid_at')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div style="flex:1;min-width:min(100%,180px);">
                <label class="label">طريقة الدفع <span class="text-red-400">*</span></label>
                <select wire:model="payment_method" class="input select">
                    <option value="cash">نقدي</option>
                    <option value="bank">بنكي</option>
                    <option value="check">شيك</option>
                    <option value="transfer">تحويل</option>
                </select>
                @error('payment_method')<p class="field-error">{{ $message }}</p>@enderror
            </div>
        </div>
        <p style="font-size:11px;color:#9CA3AF;margin:0;">تُسجَّل الدفعة على حساب العميل — حالة الدفع تُعرض لاحقاً من مجموع الدفعات.</p>
        @endif
    </div>
    @elseif(!$invoiceId && $status !== 'issued')
    <div class="card" style="padding:14px 20px;background:#F9FAFB;border-style:dashed;">
        <p style="font-size:12px;color:#6B7280;margin:0;">
            <strong>حالة الدفع:</strong> متاحة عند اختيار «صادرة» في حالة المستند.
        </p>
    </div>
    @endif

    @if($invoiceId && $status === 'issued' && $computedPaymentStatus)
    <div class="card" style="padding:20px;display:flex;flex-direction:column;gap:10px;">
        <p style="font-size:11px;font-weight:700;color:#9CA3AF;letter-spacing:.06em;text-transform:uppercase;">حالة الدفع (محسوبة)</p>
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
            @include('livewire.partials.invoice-payment-status-badge', ['paymentStatus' => $computedPaymentStatus])
            <span style="font-size:12px;color:#6B7280;" dir="ltr">
                {{ number_format($computedPaymentStatus['allocated'], 2) }} / {{ number_format($computedPaymentStatus['total'], 2) }} {{ $currency_code }}
            </span>
        </div>
        <p style="font-size:11px;color:#9CA3AF;margin:0;">تُحدَّث تلقائياً من دفعات العميل — سجّل دفعات إضافية من «الدفعات».</p>
    </div>
    @endif

    <div class="card" style="width:100%;padding:20px;display:flex;flex-direction:column;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                <p style="font-size:13px;font-weight:700;color:#1E293B;">بنود الفاتورة</p>
                <button type="button" wire:click="addLine"
                        style="display:flex;align-items:center;gap:5px;font-size:12px;font-weight:700;color:#1B6CA8;background:transparent;border:none;cursor:pointer;padding:5px 10px;border-radius:8px;"
                        onmouseover="this.style.background='#FFFBEB'" onmouseout="this.style.background='transparent'">
                    <svg xmlns="http://www.w3.org/2000/svg" style="width:13px;height:13px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                    </svg>
                    إضافة بند
                </button>
            </div>

            <div style="border:1px solid #E2E8F0;border-radius:12px;overflow:hidden;">
                <div style="display:flex;background:#F9F9FB;border-bottom:1px solid #E2E8F0;font-size:11px;font-weight:600;color:#9CA3AF;">
                    <div style="width:220px;padding:8px 10px;">الصنف (بحث)</div>
                    <div style="flex:2;padding:8px 12px;">البند / الوصف</div>
                    <div style="width:130px;padding:8px 10px;text-align:center;">سعر الوحدة</div>
                    <div style="width:100px;padding:8px 10px;text-align:center;">الكمية</div>
                    <div style="width:120px;padding:8px 10px;text-align:left;" dir="ltr">المجموع</div>
                    <div style="width:40px;"></div>
                </div>

                @forelse($lines as $i => $line)
                <div wire:key="line-{{ $i }}"
                     style="display:flex;align-items:flex-start;padding:8px 12px;border-bottom:1px solid #F1F5F9;{{ $loop->last ? 'border-bottom:none;' : '' }}{{ $loop->even ? 'background:#FAFAFA;' : '' }}">

                    <div style="width:220px;padding:0 8px 0 0;position:relative;">
                        <input type="search" autocomplete="off" dir="rtl"
                               wire:model.live.debounce.300ms="lines.{{ $i }}.product_search"
                               wire:focus="onProductSearchFocus({{ $i }})"
                               placeholder="اكتب للبحث — بند بدون صنف اتركه فارغًا للعنوان فقط"
                               class="input" style="padding:6px 8px;font-size:12px;width:100%;">
                        @if($productAutocompleteLine === $i && (count($productAutocompleteHits) > 0 || trim($line['product_search'] ?? '') !== ''))
                        <div class="absolute z-50 mt-1 right-0 w-[min(100vw-2rem,20rem)] max-h-56 overflow-y-auto rounded-lg border border-[#E2E8F0] bg-white shadow-lg text-sm"
                             style="top:100%;">
                            @foreach($productAutocompleteHits as $hit)
                            <button type="button" wire:key="hit-{{ $i }}-{{ $hit['id'] }}"
                                    wire:click="selectProductFromAutocomplete({{ $i }}, {{ $hit['id'] }})"
                                    class="w-full text-right px-3 py-2 hover:bg-[#FFFBEB] border-b border-[#F1F5F9] last:border-0">
                                <span class="font-semibold text-[#1E293B]">{{ $hit['name'] }}</span>
                                @if(!empty($hit['product_code']))
                                <span class="text-gray-400 font-mono text-xs mr-1" dir="ltr">({{ $hit['product_code'] }})</span>
                                @endif
                            </button>
                            @endforeach
                            @if(trim($line['product_search'] ?? '') !== '')
                            @can('create', \App\Models\Product::class)
                            <button type="button" wire:click="openQuickAddForLine({{ $i }})"
                                    class="w-full text-right px-3 py-2 text-[#1B6CA8] font-semibold hover:bg-amber-50 border-t border-[#E2E8F0]">
                                + إضافة «{{ \Illuminate\Support\Str::limit(trim($line['product_search']), 40) }}» كخدمة جديدة…
                            </button>
                            @endcan
                            @endif
                        </div>
                        @endif
                        @error("lines.{$i}.product_id")<p class="field-error" style="font-size:10px;">{{ $message }}</p>@enderror
                    </div>

                    <div style="flex:2;padding:0 8px 0 0;">
                        <input wire:model.live="lines.{{ $i }}.title"
                               type="text" placeholder="اسم البند *"
                               class="input" style="padding:7px 10px;font-size:13px;margin-bottom:4px;font-weight:600;">
                        <input wire:model="lines.{{ $i }}.description"
                               type="text" placeholder="وصف إضافي (اختياري)"
                               class="input" style="padding:6px 10px;font-size:12px;background:#F9F9FB;color:#6B7280;">
                        @error("lines.{$i}.title")<p class="field-error">{{ $message }}</p>@enderror
                    </div>

                    <div style="width:130px;padding:0 8px;">
                        <input wire:model.live="lines.{{ $i }}.unit_price"
                               type="number" step="0.01" min="0" dir="ltr" placeholder="0.00"
                               class="input" style="padding:7px 8px;font-size:13px;text-align:center;">
                        @error("lines.{$i}.unit_price")<p class="field-error" style="font-size:10px;">{{ $message }}</p>@enderror
                    </div>

                    <div style="width:100px;padding:0 8px;">
                        <input wire:model.live="lines.{{ $i }}.quantity"
                               type="number" step="0.01" min="0" dir="ltr" placeholder="1"
                               class="input" style="padding:7px 8px;font-size:13px;text-align:center;">
                        @error("lines.{$i}.quantity")<p class="field-error" style="font-size:10px;">{{ $message }}</p>@enderror
                    </div>

                    <div style="width:120px;padding:0 8px;display:flex;align-items:center;justify-content:flex-start;padding-top:7px;" dir="ltr">
                        <span style="font-weight:700;font-size:14px;color:#1E293B;">
                            {{ number_format((float)($line['line_total'] ?? 0), 2) }}
                        </span>
                    </div>

                    <div style="width:40px;display:flex;align-items:center;justify-content:center;padding-top:6px;">
                        <button type="button" wire:click="removeLine({{ $i }})"
                                style="width:26px;height:26px;display:flex;align-items:center;justify-content:center;border-radius:50%;border:none;background:transparent;cursor:pointer;color:#D1D5DB;transition:all .15s;"
                                onmouseover="this.style.background='#FEE2E2';this.style.color='#EF4444'"
                                onmouseout="this.style.background='transparent';this.style.color='#D1D5DB'">
                            <svg xmlns="http://www.w3.org/2000/svg" style="width:13px;height:13px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>

                </div>
                @empty
                <div style="padding:44px 20px;text-align:center;">
                    <p style="font-size:13px;color:#9CA3AF;margin-bottom:14px;">لم تُضف أي بنود بعد</p>
                    <button type="button" wire:click="addLine"
                            style="background:#1B6CA8;color:#fff;border:none;border-radius:10px;padding:10px 28px;font-size:13px;font-weight:700;cursor:pointer;">
                        + أضف أول بند
                    </button>
                </div>
                @endforelse
            </div>

            @if(count($lines) > 0)
            <button type="button" wire:click="addLine"
                    style="width:100%;margin-top:12px;padding:10px;border:1.5px dashed #E2E8F0;border-radius:10px;font-size:13px;font-weight:600;color:#1B6CA8;background:transparent;cursor:pointer;transition:all .15s;"
                    onmouseover="this.style.borderColor='#1B6CA8';this.style.background='#FFFBEB'"
                    onmouseout="this.style.borderColor='#E2E8F0';this.style.background='transparent'">
                + إضافة بند آخر
            </button>
            @endif

    </div>

    <div class="card" style="width:100%;padding:24px;display:flex;flex-direction:column;gap:12px;">
        <label class="label" style="font-size:14px;font-weight:700;color:#1E293B;margin:0;">ملاحظات</label>
        <textarea wire:model="notes" rows="10"
                  placeholder="أي ملاحظات أو تفاصيل إضافية..."
                  class="input" style="width:100%;min-height:20rem;resize:vertical;font-size:14px;line-height:1.6;padding:14px 16px;"></textarea>
    </div>

    <div class="card" style="padding:20px;display:flex;flex-direction:column;gap:10px;">
        <p style="font-size:11px;font-weight:700;color:#9CA3AF;letter-spacing:.06em;text-transform:uppercase;">الإجمالي</p>

        <div style="display:flex;justify-content:space-between;font-size:13px;color:#6B7280;padding-bottom:8px;border-bottom:1px solid #F1F5F9;">
            <span>المجموع الفرعي</span>
            <span dir="ltr" style="font-weight:600;color:#1E293B;">{{ number_format($subtotal, 2) }} {{ $currency_code }}</span>
        </div>

        <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;">
            <label style="font-size:13px;color:#6B7280;white-space:nowrap;">الخصم</label>
            <input wire:model.live="discount_amount" type="number" step="0.01" min="0" dir="ltr"
                   placeholder="0.00" class="input" style="width:130px;max-width:100%;padding:6px 10px;font-size:13px;text-align:center;">
        </div>

        @if(!$hasTitledLines)
        <div>
            <label class="label">الإجمالي الكلي <span class="text-red-400">*</span></label>
            <input wire:model.live="total_amount" type="number" step="0.01" min="0" dir="ltr"
                   placeholder="0.00" class="input" style="font-size:14px;padding:8px 12px;">
            @error('total_amount')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        @endif

        @error('lines')<p class="field-error">{{ $message }}</p>@enderror

        <div style="background:linear-gradient(135deg,#1B6CA8,#e0b83a);border-radius:12px;padding:14px 16px;display:flex;justify-content:space-between;align-items:center;">
            <span style="font-size:13px;font-weight:700;color:rgba(255,255,255,.85);">الإجمالي</span>
            <span style="font-size:20px;font-weight:900;color:#fff;" dir="ltr">
                {{ number_format((float)$total_amount, 2) }}
                <span style="font-size:12px;font-weight:500;opacity:.8;">{{ $currency_code }}</span>
            </span>
        </div>
    </div>

    <button type="button" wire:click="save" wire:loading.attr="disabled"
            class="btn btn-primary" style="width:100%;font-size:14px;padding:12px;justify-content:center;">
        <svg wire:loading wire:target="save" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
        </svg>
        <span wire:loading.remove wire:target="save">
            <svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;display:inline;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            حفظ الفاتورة
        </span>
        <span wire:loading wire:target="save">جاري الحفظ...</span>
    </button>

</div>

@if($showQuickAddProductModal)
<div class="fixed inset-0 z-[70] flex items-center justify-center p-4" style="background:rgba(0,0,0,.45);" wire:click.self="closeQuickAddProductModal">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 max-h-[90vh] overflow-y-auto" wire:click.stop role="dialog" aria-modal="true" aria-labelledby="quick-add-title">
        <h3 id="quick-add-title" class="text-lg font-bold text-[#1E293B] mb-1">إضافة خدمة سريعة</h3>
        <p class="text-xs text-gray-500 mb-4">يُحفظ في كتالوج المنتجات. التسعير لعملة الفاتورة الحالية: <span class="font-mono" dir="ltr">{{ $currency_code }}</span></p>

        <div class="space-y-3">
            <div>
                <label class="label">اسم الخدمة <span class="text-red-400">*</span></label>
                <input wire:model="quickAddName" type="text" class="input" maxlength="255">
                @error('quickAddName')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label">رمز الخدمة</label>
                <input wire:model="quickAddProductCode" type="text" class="input font-mono" dir="ltr" maxlength="64" placeholder="اختياري">
                @error('quickAddProductCode')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label">سعر البيع ({{ $currency_code }}) <span class="text-red-400">*</span></label>
                <input wire:model="quickAddSalePrice" type="number" step="0.0001" min="0" dir="ltr" class="input font-mono">
                @error('quickAddSalePrice')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label">الحد الأدنى للبيع ({{ $currency_code }}) <span class="text-red-400">*</span></label>
                <input wire:model="quickAddMinSalePrice" type="number" step="0.0001" min="0" dir="ltr" class="input font-mono">
                @error('quickAddMinSalePrice')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label">تكلفة الخدمة ({{ $currency_code }}) <span class="text-red-400">*</span></label>
                <input wire:model="quickAddServiceCost" type="number" step="0.0001" min="0" dir="ltr" class="input font-mono">
                @error('quickAddServiceCost')<p class="field-error">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="flex gap-2 mt-6 justify-end flex-wrap">
            <button type="button" wire:click="closeQuickAddProductModal" class="btn btn-secondary">إلغاء</button>
            <button type="button" wire:click="saveQuickAddProduct" wire:loading.attr="disabled" class="btn btn-primary">حفظ وربط البند</button>
        </div>
    </div>
</div>
@endif

</div>
