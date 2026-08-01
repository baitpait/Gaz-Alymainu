<div>
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-bold text-[#1E293B]">الرواتب</h1>
        <p class="text-sm text-gray-400 mt-0.5">{{ $rows->total() }} سجل</p>
    </div>
    @can('create', App\Models\SalaryPayment::class)
    <a href="{{ route('salary-payments.create') }}" wire:navigate class="btn btn-primary" style="text-decoration:none;">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        تسجيل راتب
    </a>
    @endcan
</div>

<form wire:submit.prevent="applyListFilters" class="bg-white border border-[#E0E0E0] rounded p-4 mb-6">
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5 items-end">
        <div class="lg:col-span-2">
            <label class="block text-xs font-medium text-[#1E293B] mb-1">بحث</label>
            <input type="text" wire:model="searchDraft" placeholder="اسم موظف أو رقم وظيفي..."
                   class="border border-[#E0E0E0] rounded px-3 py-2 text-sm w-full focus:outline-none focus:border-[#1B6CA8]">
        </div>
        <div>
            <label class="block text-xs font-medium text-[#1E293B] mb-1">الموظف</label>
            <select wire:model="employeeId" class="border border-[#E0E0E0] rounded px-3 py-2 text-sm w-full">
                <option value="">الكل</option>
                @foreach($employees as $id => $name)
                <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-[#1E293B] mb-1">الشهر</label>
            <input type="month" wire:model="period" dir="ltr" class="border border-[#E0E0E0] rounded px-3 py-2 text-sm w-full">
        </div>
        <div>
            <label class="block text-xs font-medium text-[#1E293B] mb-1">الحالة</label>
            <select wire:model="status" class="border border-[#E0E0E0] rounded px-3 py-2 text-sm w-full">
                <option value="">الكل</option>
                <option value="draft">مسودة</option>
                <option value="paid">مدفوع</option>
                <option value="cancelled">ملغى</option>
            </select>
        </div>
        <div class="flex gap-2 sm:col-span-2 lg:col-span-5 lg:justify-end">
            @include('livewire.partials.list-filter-actions', ['applyMethod' => 'applyListFilters', 'clearMethod' => 'clearListFilters', 'showClear' => true])
        </div>
    </div>
</form>

<div class="card overflow-hidden">
    <div class="overflow-x-auto [-webkit-overflow-scrolling:touch]">
        <table class="data-table">
            <thead>
                <tr>
                    <th>الموظف</th>
                    <th>الشهر</th>
                    <th>الصافي</th>
                    <th>الحالة</th>
                    <th class="w-32"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $pay)
                <tr>
                    <td>
                        <div class="font-medium">{{ $pay->employee?->displayName() ?? '—' }}</div>
                        @if($pay->employee?->department)<div class="text-xs text-gray-400">{{ $pay->employee->department }}</div>@endif
                    </td>
                    <td class="font-mono text-sm" dir="ltr">{{ $pay->periodLabel() }}</td>
                    <td class="font-mono font-semibold text-sm text-[#1B6CA8]" dir="ltr">{{ number_format((float)$pay->net_amount, 2) }} {{ $pay->currency_code }}</td>
                    <td>{{ App\Models\SalaryPayment::statusLabel($pay->status) }}</td>
                    <td>
                        <div class="flex items-center gap-1 justify-end">
                            <a href="{{ route('salary-payments.show', $pay) }}" wire:navigate class="btn btn-ghost py-1 px-2 text-xs">عرض</a>
                            @can('update', $pay)
                            <a href="{{ route('salary-payments.edit', $pay) }}" wire:navigate class="btn btn-ghost py-1 px-2 text-xs text-blue-600">تعديل</a>
                            <button type="button" wire:click="confirmDelete({{ $pay->id }})" class="btn btn-ghost py-1 px-2 text-xs text-red-500">حذف</button>
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center py-16 text-gray-300">لا توجد سجلات رواتب</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-4 border-t border-[#E2E8F0]"><x-list-pagination :paginator="$rows" /></div>
</div>

@if($confirmDeleteId !== null)
<div class="fixed inset-0 z-[60] flex items-center justify-center">
    <div class="fixed inset-0 bg-black/40 backdrop-blur-[2px]" wire:click="cancelDelete"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm mx-4 z-10 p-6">
        <h3 class="text-base font-bold text-center mb-4">حذف سجل الراتب؟</h3>
        <div class="flex gap-2">
            <button wire:click="cancelDelete" class="btn btn-secondary flex-1">إلغاء</button>
            <button wire:click="delete" class="btn btn-danger flex-1">حذف</button>
        </div>
    </div>
</div>
@endif
</div>
