<div>

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-bold text-[#1E293B]">المستخدمون</h1>
        <p class="text-sm text-gray-400 mt-0.5">{{ $rows->total() }} مستخدم مسجّل</p>
    </div>
    <a href="{{ route('users.create') }}" wire:navigate class="btn btn-primary" style="text-decoration:none;">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        إضافة مستخدم
    </a>
</div>

@include('livewire.partials.list-search-form', [
    'searchPlaceholder' => 'بحث بالاسم أو البريد...',
    'hasActive' => $this->hasActiveListFilters(),
])

{{-- الجدول --}}
<div class="card overflow-hidden">
    <div wire:loading.delay class="h-0.5 bg-[#1B6CA8]/20 relative overflow-hidden">
        <div class="absolute inset-y-0 right-0 w-1/3 bg-[#1B6CA8] animate-pulse"></div>
    </div>
    <div class="overflow-x-auto [-webkit-overflow-scrolling:touch]">
        <table class="data-table">
            <thead><tr>
                <th>المستخدم</th>
                <th>البريد الإلكتروني</th>
                <th>الصلاحية</th>
                <th>الحالة</th>
                <th class="w-32"></th>
            </tr></thead>
            <tbody>
                @forelse($rows as $user)
                <tr>
                    <td>
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold shrink-0
                                {{ $user->role === 'manager' ? 'bg-[#1B6CA8]/15 text-[#1B6CA8]' : ($user->role === 'accountant' ? 'bg-blue-50 text-blue-600' : 'bg-gray-100 text-gray-500') }}">
                                {{ mb_substr($user->full_name, 0, 1) }}
                            </div>
                            <div>
                                <p class="font-semibold text-[#1E293B] text-sm">{{ $user->full_name }}</p>
                                @if($user->id === auth()->id())
                                <span class="text-[10px] text-[#1B6CA8] font-semibold">أنت</span>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="text-gray-500 text-sm" dir="ltr">{{ $user->email }}</td>
                    <td>
                        @php
                            $roleLabel = match($user->role) { 'manager' => 'مدير', 'accountant' => 'محاسب', default => 'مشاهد' };
                            $roleBadge = match($user->role) { 'manager' => 'badge-yellow', 'accountant' => 'badge-blue', default => 'badge-gray' };
                        @endphp
                        <span class="badge {{ $roleBadge }}">{{ $roleLabel }}</span>
                    </td>
                    <td>
                        <button wire:click="toggleActive({{ $user->id }})"
                                class="flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full transition
                                       {{ $user->is_active ? 'bg-green-50 text-green-600 hover:bg-green-100' : 'bg-gray-100 text-gray-400 hover:bg-gray-200' }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $user->is_active ? 'bg-green-500' : 'bg-gray-300' }}"></span>
                            {{ $user->is_active ? 'مفعّل' : 'معطّل' }}
                        </button>
                    </td>
                    <td>
                        <div class="flex items-center gap-1 justify-end">
                            <a href="{{ route('users.edit', $user->id) }}" wire:navigate class="btn btn-ghost py-1 px-2 text-xs text-blue-600 hover:bg-blue-50" style="text-decoration:none;">تعديل</a>
                            @if($user->id !== auth()->id())
                            <button wire:click="confirmDelete({{ $user->id }})" class="btn btn-ghost py-1 px-2 text-xs text-red-500 hover:bg-red-50">حذف</button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5">
                    <div class="text-center py-16 text-gray-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <p class="text-sm">{{ $search ? 'لا توجد نتائج' : 'لا يوجد مستخدمون بعد' }}</p>
                    </div>
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <x-list-pagination :paginator="$rows" />
</div>


{{-- ══ تأكيد الحذف ══ --}}
@if($confirmDeleteId !== null)
<div wire:key="delete-{{ $confirmDeleteId }}"
     class="fixed inset-0 z-[60] flex items-center justify-center">
    <div class="fixed inset-0 bg-black/40 backdrop-blur-[2px]" wire:click="cancelDelete"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm mx-4 z-10 p-6">
        <div class="w-12 h-12 rounded-full bg-red-50 flex items-center justify-center mx-auto mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        </div>
        <h3 class="text-base font-bold text-center mb-1">حذف المستخدم</h3>
        <p class="text-sm text-gray-400 text-center mb-5">هل أنت متأكد؟ لا يمكن التراجع.</p>
        <div class="flex gap-2">
            <button wire:click="cancelDelete" class="btn btn-secondary flex-1">إلغاء</button>
            <button wire:click="delete" class="btn btn-danger flex-1">حذف</button>
        </div>
    </div>
</div>
@endif

</div>
