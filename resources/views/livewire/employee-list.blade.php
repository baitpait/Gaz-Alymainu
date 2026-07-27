<div>
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-bold text-[#1E293B]">الموظفون</h1>
        <p class="text-sm text-gray-400 mt-0.5">{{ $rows->total() }} موظف</p>
    </div>
    @can('create', App\Models\Employee::class)
    <a href="{{ route('employees.create') }}" wire:navigate class="btn btn-primary" style="text-decoration:none;">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        إضافة موظف
    </a>
    @endcan
</div>

<form wire:submit.prevent="applyListFilters" class="bg-white border border-[#E0E0E0] rounded p-4 mb-6">
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 items-end">
        <div class="lg:col-span-2">
            <label class="block text-xs font-medium text-[#1E293B] mb-1">بحث</label>
            <input type="text" wire:model="searchDraft" placeholder="اسم، رقم وظيفي، قسم..."
                   class="border border-[#E0E0E0] rounded px-3 py-2 text-sm w-full focus:outline-none focus:border-[#1B6CA8]">
        </div>
        <div>
            <label class="block text-xs font-medium text-[#1E293B] mb-1">الحالة</label>
            <select wire:model="active" class="border border-[#E0E0E0] rounded px-3 py-2 text-sm w-full">
                <option value="">الكل</option>
                <option value="1">نشط</option>
                <option value="0">غير نشط</option>
            </select>
        </div>
        <div class="flex gap-2">
            @include('livewire.partials.list-filter-actions', ['applyMethod' => 'applyListFilters', 'clearMethod' => 'clearListFilters', 'showClear' => true])
        </div>
    </div>
</form>

<div class="card overflow-hidden">
    <table class="data-table">
        <thead>
            <tr>
                <th>الاسم</th>
                <th>القسم</th>
                <th>النوع</th>
                <th>الراتب الأساسي</th>
                <th>الحالة</th>
                <th class="w-32"></th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $emp)
            <tr>
                <td>
                    <div class="font-medium">{{ $emp->displayName() }}</div>
                    @if($emp->employee_code)<div class="text-xs text-gray-400" dir="ltr">{{ $emp->employee_code }}</div>@endif
                </td>
                <td class="text-sm text-gray-600">{{ $emp->department ?? '—' }}</td>
                <td>
                    <span class="badge {{ $emp->pay_frequency === 'part_time' ? 'badge-yellow' : 'badge-green' }}">
                        {{ App\Models\Employee::employmentTypeLabel($emp->pay_frequency) }}
                    </span>
                </td>
                <td class="font-mono text-sm" dir="ltr">{{ number_format((float)$emp->base_salary_amount, 2) }} {{ $emp->base_salary_currency }}</td>
                <td>
                    <span class="badge {{ $emp->is_active ? 'badge-green' : 'badge-yellow' }}">{{ $emp->is_active ? 'نشط' : 'متوقف' }}</span>
                </td>
                <td>
                    <div class="flex items-center gap-1 justify-end">
                        <a href="{{ route('employees.show', $emp) }}" wire:navigate class="btn btn-ghost py-1 px-2 text-xs">عرض</a>
                        @can('update', $emp)
                        <a href="{{ route('employees.edit', $emp) }}" wire:navigate class="btn btn-ghost py-1 px-2 text-xs text-blue-600">تعديل</a>
                        <button type="button" wire:click="confirmDelete({{ $emp->id }})" class="btn btn-ghost py-1 px-2 text-xs text-red-500">حذف</button>
                        @endcan
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center py-16 text-gray-300">لا يوجد موظفون</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="p-4 border-t border-[#E2E8F0]"><x-list-pagination :paginator="$rows" /></div>
</div>

@if($confirmDeleteId !== null)
<div class="fixed inset-0 z-[60] flex items-center justify-center">
    <div class="fixed inset-0 bg-black/40 backdrop-blur-[2px]" wire:click="cancelDelete"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm mx-4 z-10 p-6">
        <h3 class="text-base font-bold text-center mb-4">حذف الموظف؟</h3>
        <div class="flex gap-2">
            <button wire:click="cancelDelete" class="btn btn-secondary flex-1">إلغاء</button>
            <button wire:click="delete" class="btn btn-danger flex-1">حذف</button>
        </div>
    </div>
</div>
@endif
</div>
