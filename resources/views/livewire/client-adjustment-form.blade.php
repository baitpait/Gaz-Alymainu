<div>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;">
    <div style="display:flex;align-items:center;gap:12px;">
        <a href="{{ route('clients.statement', $client) }}" wire:navigate
           style="width:36px;height:36px;display:flex;align-items:center;justify-content:center;border-radius:50%;border:1px solid #E2E8F0;background:#fff;color:#6B7280;text-decoration:none;">
            <svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </a>
        <div>
            <h1 style="font-size:18px;font-weight:800;color:#1E293B;">
                {{ $recordId ? 'تعديل تسوية' : 'تسوية على الذمة' }}
            </h1>
            <p style="font-size:12px;color:#9CA3AF;margin-top:2px;">{{ $client->displayName() }}</p>
        </div>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="{{ route('clients.statement', $client) }}" wire:navigate class="btn btn-secondary" style="font-size:13px;text-decoration:none;">إلغاء</a>
        <button wire:click="save" wire:loading.attr="disabled" class="btn btn-primary" style="font-size:13px;min-width:120px;">
            <span wire:loading.remove wire:target="save">حفظ التسوية</span>
            <span wire:loading wire:target="save">جاري الحفظ...</span>
        </button>
    </div>
</div>

<div class="card" style="max-width:680px;margin:0 auto;padding:28px;">
    <p class="text-sm text-gray-500 mb-4">تُستخدم لتسجيل خصم أو إعفاء على رصيد العميل <strong>دون تعديل الفواتير</strong> ودون نقد. تظهر في كشف الحساب وتُنقص الرصيد المستحق.</p>

    <div style="display:flex;flex-direction:column;gap:18px;">
        <div>
            <label class="label">المبلغ <span class="text-red-400">*</span></label>
            <input wire:model="amount" type="number" step="0.01" min="0.01" dir="ltr" class="input font-mono">
            @error('amount')<p class="field-error">{{ $message }}</p>@enderror
        </div>

        <div style="display:flex;gap:16px;flex-wrap:wrap;">
            <div style="flex:1;min-width:140px;">
                <label class="label">العملة <span class="text-red-400">*</span></label>
                <select wire:model="currency_code" class="input select">
                    <option value="ILS">ILS</option>
                    <option value="USD">USD</option>
                    <option value="EUR">EUR</option>
                    <option value="JOD">JOD</option>
                </select>
                @error('currency_code')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div style="flex:1;min-width:140px;">
                <label class="label">التاريخ <span class="text-red-400">*</span></label>
                <input wire:model="adjustment_date" type="date" class="input">
                @error('adjustment_date')<p class="field-error">{{ $message }}</p>@enderror
            </div>
        </div>

        <div>
            <label class="label">نوع التسوية <span class="text-red-400">*</span></label>
            <select wire:model="type" class="input select">
                @foreach($typeLabels as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            @error('type')<p class="field-error">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="label">السبب</label>
            <input wire:model="reason" type="text" class="input" placeholder="مثال: إعفاء متبقي عند التحصيل">
            @error('reason')<p class="field-error">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="label">ملاحظات</label>
            <textarea wire:model="notes" rows="3" class="input"></textarea>
            @error('notes')<p class="field-error">{{ $message }}</p>@enderror
        </div>
    </div>
</div>
</div>
