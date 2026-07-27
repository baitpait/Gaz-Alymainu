<div x-data="{ deletingId: null }">

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-bold text-[#1E293B]">المنتجات</h1>
        <p class="text-sm text-gray-400 mt-0.5">{{ $rows->total() }} خدمة</p>
    </div>
    @can('create', \App\Models\Product::class)
    <a href="{{ route('products.create') }}" wire:navigate class="btn btn-primary" style="text-decoration:none;">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        إضافة خدمة
    </a>
    @endcan
</div>

@include('livewire.partials.list-search-form', [
    'searchPlaceholder' => 'بحث بالاسم أو الرمز أو الوصف...',
    'hasActive' => $this->hasActiveListFilters(),
])

<div class="card overflow-hidden">
    <div wire:loading.delay class="h-0.5 bg-[#1B6CA8]/20 relative overflow-hidden">
        <div class="absolute inset-y-0 right-0 w-1/3 bg-[#1B6CA8] animate-pulse"></div>
    </div>
    <table class="data-table">
        <thead><tr>
            <th>الاسم</th>
            <th class="font-mono text-xs" dir="ltr">الرمز</th>
            <th>عملات مكتملة التسعير</th>
            @if(auth()->user()->isAccountant() || auth()->user()->isManager())
            <th class="w-44"></th>
            @endif
        </tr></thead>
        <tbody>
            @forelse($rows as $p)
            <tr>
                <td class="font-semibold">{{ $p->name }}</td>
                <td class="text-gray-500 font-mono text-xs" dir="ltr">{{ $p->product_code ?? '—' }}</td>
                <td class="text-sm text-gray-500">
                    @php
                        $ok = [];
                        foreach (\App\Models\Product::billingCurrencies() as $cc) {
                            if ($p->hasCompletePricingForCurrency($cc)) {
                                $ok[] = $cc;
                            }
                        }
                    @endphp
                    @if(count($ok))
                        <span class="inline-flex flex-wrap gap-1">
                            @foreach($ok as $cc)
                            <span class="rounded px-1.5 py-0.5 bg-[#F7F8FA] border border-[#E2E8F0] font-mono text-xs" dir="ltr">{{ $cc }}</span>
                            @endforeach
                        </span>
                    @else
                        —
                    @endif
                </td>
                @if(auth()->user()->isAccountant() || auth()->user()->isManager())
                <td>
                    <div class="flex items-center gap-1 justify-end flex-wrap">
                        @can('update', $p)
                        <a href="{{ route('products.edit', $p) }}" wire:navigate class="btn btn-ghost py-1 px-2 text-xs text-blue-600 hover:bg-blue-50" style="text-decoration:none;">تعديل</a>
                        @endcan
                        @can('delete', $p)
                        <button type="button" @click="deletingId = {{ $p->id }}" class="btn btn-ghost py-1 px-2 text-xs text-red-500 hover:bg-red-50">حذف</button>
                        @endcan
                    </div>
                </td>
                @endif
            </tr>
            @empty
            <tr><td colspan="{{ auth()->user()->isAccountant() || auth()->user()->isManager() ? 4 : 3 }}">
                <div class="text-center py-16 text-gray-300">
                    <p class="text-sm">{{ $search ? 'لا توجد نتائج' : 'لا توجد خدمات بعد' }}</p>
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
        <div class="w-12 h-12 rounded-full bg-red-50 flex items-center justify-center mx-auto mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        </div>
        <h3 class="text-center font-bold text-[#1E293B] mb-2">حذف الخدمة؟</h3>
        <p class="text-center text-sm text-gray-500 mb-6">سيتم إخفاء الخدمة من القوائم. البنود المرتبطة سابقًا تبقى كما هي على الفواتير.</p>
        <div class="flex gap-2">
            <button type="button" @click="deletingId = null" class="btn btn-secondary flex-1">إلغاء</button>
            <button type="button" class="btn btn-primary flex-1 bg-red-600 hover:bg-red-700 border-red-600"
                    x-on:click="$wire.deleteRecord(deletingId); deletingId = null">
                حذف
            </button>
        </div>
    </div>
</div>

</div>
