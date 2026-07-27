<div>
    <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-[#1E293B]">أعمار ذمم الموردين</h1>
            <p class="text-sm text-gray-500 mt-1">
                متبقٍ للمورد لكل عملة، مع أيام التأخير من تاريخ أقدم أمر شراء غير مسدّد (FIFO).
            </p>
        </div>
        <div class="flex flex-wrap items-end gap-3">
            @can('export-period-reports')
            <a href="{{ $pdfExportUrl }}" target="_blank" rel="noopener"
               class="px-4 py-2 text-sm bg-[#1E293B] text-white rounded hover:bg-[#2a2a2a] font-medium self-end"
               style="text-decoration:none;">
                تصدير PDF
            </a>
            <button type="button" wire:click="exportCsv"
                    class="px-4 py-2 text-sm bg-white border border-[#E0E0E0] rounded hover:bg-[#F5F5F5] font-medium self-end">
                تصدير CSV
            </button>
            @endcan
        </div>
    </div>

    <form wire:submit.prevent="applyReportFilters" class="bg-white border border-[#E0E0E0] rounded p-4 mb-6">
        <div class="flex flex-wrap items-end justify-between gap-3 mb-3">
            <h2 class="text-sm font-bold text-[#1E293B]">تصفية النتائج</h2>
        </div>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
            <div>
                <label class="block text-xs font-medium text-[#1E293B] mb-1">العملة</label>
                <select wire:model="currency"
                        class="border border-[#E0E0E0] rounded px-3 py-2 text-sm w-full focus:outline-none focus:border-[#1B6CA8]">
                    <option value="">كل العملات</option>
                    @foreach($currencyOptions as $c)
                        <option value="{{ $c }}" dir="ltr">{{ $c }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-[#1E293B] mb-1">فئة التأخير</label>
                <select wire:model="agingBucket"
                        class="border border-[#E0E0E0] rounded px-3 py-2 text-sm w-full focus:outline-none focus:border-[#1B6CA8]"
                        @disabled($daysMin !== '' || $daysMax !== '')>
                    <option value="">الكل</option>
                    <option value="0_30">0–30 يوم</option>
                    <option value="31_60">31–60 يوم</option>
                    <option value="61_90">61–90 يوم</option>
                    <option value="91_plus">91+ يوم</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-[#1E293B] mb-1">أيام من (حد أدنى)</label>
                <input type="number" min="0" step="1" wire:model="daysMin" dir="ltr"
                       placeholder="—"
                       @disabled($agingBucket !== '')
                       class="border border-[#E0E0E0] rounded px-3 py-2 text-sm w-full font-mono focus:outline-none focus:border-[#1B6CA8] disabled:bg-[#F5F5F5]">
            </div>
            <div>
                <label class="block text-xs font-medium text-[#1E293B] mb-1">أيام إلى (حد أقصى)</label>
                <input type="number" min="0" step="1" wire:model="daysMax" dir="ltr"
                       placeholder="—"
                       @disabled($agingBucket !== '')
                       class="border border-[#E0E0E0] rounded px-3 py-2 text-sm w-full font-mono focus:outline-none focus:border-[#1B6CA8] disabled:bg-[#F5F5F5]">
            </div>
            <div>
                <label class="block text-xs font-medium text-[#1E293B] mb-1">حد أدنى للمبلغ</label>
                <input type="number" min="0" step="0.01" wire:model="minBalance" dir="ltr"
                       placeholder="0"
                       class="border border-[#E0E0E0] rounded px-3 py-2 text-sm w-full font-mono focus:outline-none focus:border-[#1B6CA8]">
            </div>
            <div class="sm:col-span-2 lg:col-span-1 xl:col-span-2">
                <label class="block text-xs font-medium text-[#1E293B] mb-1">بحث (اسم أو هاتف)</label>
                <input type="search" wire:model="search"
                       placeholder="ابحث..."
                       class="border border-[#E0E0E0] rounded px-3 py-2 text-sm w-full focus:outline-none focus:border-[#1B6CA8]">
            </div>
        </div>
        <div class="flex flex-wrap gap-2 mt-4">
            @include('livewire.partials.list-filter-actions', [
                'applyMethod' => 'applyReportFilters',
                'clearMethod' => 'clearFilters',
                'showClear' => $this->hasActiveReportFilters(),
            ])
        </div>
    </form>

    @if(count($rows) === 0)
        <div class="text-center py-16 text-gray-400 bg-white border border-[#E0E0E0] rounded">
            لا توجد ذمم مستحقة للموردين مطابقة للفلتر.
        </div>
    @else
        <div class="overflow-x-auto bg-white border border-[#E0E0E0] rounded mb-6">
            <table class="w-full text-sm">
                <thead class="bg-[#F5F5F5] text-[#1E293B]">
                    <tr>
                        <th class="text-right px-3 py-2 font-semibold border-b border-[#E0E0E0]">المورد</th>
                        <th class="text-right px-3 py-2 font-semibold border-b border-[#E0E0E0]" dir="ltr">الهاتف</th>
                        <th class="text-right px-3 py-2 font-semibold border-b border-[#E0E0E0]" dir="ltr">العملة</th>
                        <th class="text-left px-3 py-2 font-semibold border-b border-[#E0E0E0]" dir="ltr">المبلغ المستحق</th>
                        <th class="text-center px-3 py-2 font-semibold border-b border-[#E0E0E0]">أيام من أول ذمة</th>
                        <th class="text-right px-3 py-2 font-semibold border-b border-[#E0E0E0]" dir="ltr">تاريخ أول ذمة</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $r)
                    <tr class="border-b border-[#E0E0E0] last:border-0 hover:bg-[#FAFAFA]">
                        <td class="px-3 py-2">
                            <a href="{{ route('suppliers.statement', $r['supplier_id']) }}" class="text-[#1B6CA8] font-medium hover:underline">
                                {{ $r['supplier_name'] }}
                            </a>
                        </td>
                        <td class="px-3 py-2 font-mono text-gray-700" dir="ltr">{{ $r['phone'] ?? '—' }}</td>
                        <td class="px-3 py-2 font-mono" dir="ltr">{{ $r['currency_code'] }}</td>
                        <td class="px-3 py-2 font-mono text-right font-semibold text-purple-700" dir="ltr">{{ number_format((float) $r['balance'], 2) }}</td>
                        <td class="px-3 py-2 text-center font-mono">{{ $r['days_from_first_unpaid'] }}</td>
                        <td class="px-3 py-2 font-mono text-gray-600" dir="ltr">{{ $r['first_unpaid_document_date'] ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div class="bg-white border border-[#E0E0E0] rounded p-4">
                <h2 class="text-sm font-bold text-[#1E293B] mb-3">إجمالي الذمم</h2>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500">عدد الموردين</dt>
                        <dd class="font-mono font-semibold">{{ $summary['supplier_count'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500">المبلغ المستحق</dt>
                        <dd class="font-mono font-semibold text-[#1E293B]" dir="ltr">{{ number_format((float) $summary['total_balance'], 2) }}</dd>
                    </div>
                </dl>
            </div>

            <div class="bg-white border border-[#E0E0E0] rounded p-4">
                <h2 class="text-sm font-bold text-[#1E293B] mb-3">تصنيف عمرية</h2>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-gray-500 border-b border-[#E0E0E0]">
                            <th class="text-right py-1 font-medium">الدُفعة</th>
                            <th class="text-left py-1 font-medium" dir="ltr">المبلغ</th>
                            <th class="text-left py-1 font-medium" dir="ltr">تراكمي</th>
                        </tr>
                    </thead>
                    <tbody class="font-mono">
                        <tr class="border-b border-[#F0F0F0]">
                            <td class="py-2 text-right">0–30 يوم</td>
                            <td class="py-2 text-left" dir="ltr">{{ number_format((float) $summary['buckets']['0_30'], 2) }}</td>
                            <td class="py-2 text-left font-semibold" dir="ltr">{{ number_format((float) $summary['cumulative']['through_30'], 2) }}</td>
                        </tr>
                        <tr class="border-b border-[#F0F0F0]">
                            <td class="py-2 text-right">31–60 يوم</td>
                            <td class="py-2 text-left" dir="ltr">{{ number_format((float) $summary['buckets']['31_60'], 2) }}</td>
                            <td class="py-2 text-left font-semibold" dir="ltr">{{ number_format((float) $summary['cumulative']['through_60'], 2) }}</td>
                        </tr>
                        <tr class="border-b border-[#F0F0F0]">
                            <td class="py-2 text-right">61–90 يوم</td>
                            <td class="py-2 text-left" dir="ltr">{{ number_format((float) $summary['buckets']['61_90'], 2) }}</td>
                            <td class="py-2 text-left font-semibold" dir="ltr">{{ number_format((float) $summary['cumulative']['through_90'], 2) }}</td>
                        </tr>
                        <tr>
                            <td class="py-2 text-right">91+ يوم</td>
                            <td class="py-2 text-left" dir="ltr">{{ number_format((float) $summary['buckets']['91_plus'], 2) }}</td>
                            <td class="py-2 text-left font-semibold" dir="ltr">{{ number_format((float) $summary['cumulative']['all'], 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
