<div>

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-bold text-[#1E293B]">السائقون</h1>
        <p class="text-sm text-gray-400 mt-0.5">{{ $rows->total() }} سائق</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('drivers.map') }}" wire:navigate class="btn btn-secondary" style="text-decoration:none;">خريطة المواقع</a>
        <a href="{{ route('drivers.create') }}" wire:navigate class="btn btn-primary" style="text-decoration:none;">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            إضافة سائق
        </a>
    </div>
</div>

@include('livewire.partials.list-search-form', [
    'searchPlaceholder' => 'بحث بالاسم أو البريد...',
    'hasActive' => $this->hasActiveListFilters(),
])

<div class="card overflow-hidden">
    <div wire:loading.delay class="h-0.5 bg-[#1B6CA8]/20 relative overflow-hidden">
        <div class="absolute inset-y-0 right-0 w-1/3 bg-[#1B6CA8] animate-pulse"></div>
    </div>
    <div class="overflow-x-auto [-webkit-overflow-scrolling:touch]">
        <table class="data-table">
            <thead><tr>
                <th>الاسم</th>
                <th dir="ltr" class="text-right">البريد</th>
                <th>الهاتف</th>
                <th>السيارة</th>
                <th>الحالة</th>
                <th class="w-28"></th>
            </tr></thead>
            <tbody>
                @forelse($rows as $u)
                <tr>
                    <td class="font-semibold">{{ $u->full_name }}</td>
                    <td class="text-gray-500 text-sm" dir="ltr">{{ $u->email }}</td>
                    <td class="text-gray-500 text-sm" dir="ltr">{{ $u->employee?->phone_primary ?? '—' }}</td>
                    <td class="text-gray-500 text-sm">{{ $u->assignedVehicle?->name ?? '—' }}</td>
                    <td>
                        @if($u->is_active)
                            <span class="text-green-600 text-xs">نشط</span>
                        @else
                            <span class="text-gray-400 text-xs">موقوف</span>
                        @endif
                    </td>
                    <td>
                        <div class="flex items-center gap-1 justify-end">
                            <a href="{{ route('drivers.edit', $u) }}" wire:navigate class="btn btn-ghost py-1 px-2 text-xs text-blue-600 hover:bg-blue-50" style="text-decoration:none;">تعديل</a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6">
                    <div class="text-center py-16 text-gray-300">
                        <p class="text-sm">{{ $search ? 'لا توجد نتائج' : 'لا يوجد سائقون بعد' }}</p>
                    </div>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <x-list-pagination :paginator="$rows" />
</div>

</div>
