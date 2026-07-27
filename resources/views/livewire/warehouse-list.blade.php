<div x-data="{ deletingId: null }">

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-bold text-[#3D3D3D]">المخازن والسيارات</h1>
        <p class="text-sm text-gray-400 mt-0.5">{{ $rows->total() }} مخزن/سيارة</p>
    </div>
    @can('create', \App\Models\Warehouse::class)
    <a href="{{ route('warehouses.create') }}" wire:navigate class="btn btn-primary" style="text-decoration:none;">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        إضافة مخزن
    </a>
    @endcan
</div>

@include('livewire.partials.list-search-form', [
    'searchPlaceholder' => 'بحث بالاسم أو الرمز أو رقم اللوحة...',
    'hasActive' => $this->hasActiveListFilters(),
])

<div class="card overflow-hidden">
    <div wire:loading.delay class="h-0.5 bg-[#C9A227]/20 relative overflow-hidden">
        <div class="absolute inset-y-0 right-0 w-1/3 bg-[#C9A227] animate-pulse"></div>
    </div>
    <table class="data-table">
        <thead><tr>
            <th>الاسم</th>
            <th>النوع</th>
            <th>السائق / اللوحة</th>
            <th>الحالة</th>
            @if(auth()->user()->isAccountant())
            <th class="w-44"></th>
            @endif
        </tr></thead>
        <tbody>
            @forelse($rows as $w)
            <tr>
                <td class="font-semibold">
                    {{ $w->name }}
                    @if($w->code)<span class="text-gray-400 font-mono text-xs mr-1" dir="ltr">{{ $w->code }}</span>@endif
                </td>
                <td>
                    @if($w->isVehicle())
                        <span class="rounded px-2 py-0.5 bg-blue-50 text-blue-600 text-xs">{{ $w->type->label() }}</span>
                    @else
                        <span class="rounded px-2 py-0.5 bg-[#F7F8FA] border border-[#E2E4E9] text-xs">{{ $w->type->label() }}</span>
                    @endif
                </td>
                <td class="text-sm text-gray-500">
                    @if($w->isVehicle())
                        {{ $w->driver?->full_name ?? '—' }}
                        @if($w->vehicle_plate)<span class="font-mono text-xs mr-1" dir="ltr">({{ $w->vehicle_plate }})</span>@endif
                    @else
                        —
                    @endif
                </td>
                <td>
                    @if($w->is_active)
                        <span class="text-green-600 text-xs">نشط</span>
                    @else
                        <span class="text-gray-400 text-xs">غير نشط</span>
                    @endif
                </td>
                @if(auth()->user()->isAccountant())
                <td>
                    <div class="flex items-center gap-1 justify-end flex-wrap">
                        <a href="{{ route('warehouses.stock', $w) }}" wire:navigate class="btn btn-ghost py-1 px-2 text-xs text-[#C9A227] hover:bg-[#C9A227]/10" style="text-decoration:none;">المخزون</a>
                        @can('update', $w)
                        <a href="{{ route('warehouses.edit', $w) }}" wire:navigate class="btn btn-ghost py-1 px-2 text-xs text-blue-600 hover:bg-blue-50" style="text-decoration:none;">تعديل</a>
                        @endcan
                        @can('delete', $w)
                        <button type="button" @click="deletingId = {{ $w->id }}" class="btn btn-ghost py-1 px-2 text-xs text-red-500 hover:bg-red-50">حذف</button>
                        @endcan
                    </div>
                </td>
                @endif
            </tr>
            @empty
            <tr><td colspan="{{ auth()->user()->isAccountant() ? 5 : 4 }}">
                <div class="text-center py-16 text-gray-300">
                    <p class="text-sm">{{ $search ? 'لا توجد نتائج' : 'لا توجد مخازن بعد' }}</p>
                </div>
            </td></tr>
            @endforelse
        </tbody>
    </table>

    <x-list-pagination :paginator="$rows" />
</div>

<div x-show="deletingId !== null" x-cloak
     class="fixed inset-0 z-[60] flex items-center justify-center">
    <div class="fixed inset-0 bg-black/40 backdrop-blur-[2px]" @click="deletingId = null"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm mx-4 z-10 p-6">
        <h3 class="text-center font-bold text-[#3D3D3D] mb-2">حذف المخزن؟</h3>
        <p class="text-center text-sm text-gray-500 mb-6">لن يمكن الحذف إن كان يحتوي أرصدة أو حركات مرتبطة.</p>
        <div class="flex gap-2">
            <button type="button" @click="deletingId = null" class="btn btn-secondary flex-1">إلغاء</button>
            <button type="button" class="btn btn-primary flex-1 bg-red-600 hover:bg-red-700 border-red-600"
                    x-on:click="$wire.deleteRecord(deletingId); deletingId = null">حذف</button>
        </div>
    </div>
</div>

</div>
