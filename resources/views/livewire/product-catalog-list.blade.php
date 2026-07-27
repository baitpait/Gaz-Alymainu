<div>

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-bold text-[#1E293B]">كتالوج المنتجات</h1>
        <p class="text-sm text-gray-400 mt-0.5">{{ $rows->total() }} عنصر في الأرشيف</p>
    </div>
</div>

<p class="text-sm text-gray-500 mb-4 leading-relaxed">
    قائمة بأسماء وأسعار من <strong>النظام القديم</strong> (ملف Products.xml عند الاستيراد). بنود الفواتير الحالية <strong>نص حر</strong> ولا ترتبط آلياً بهذا الجدول.
</p>

@include('livewire.partials.list-search-form', [
    'searchPlaceholder' => 'بحث بالاسم أو الكود أو الوصف...',
    'hasActive' => $this->hasActiveListFilters(),
])

<div class="card overflow-hidden">
    <div wire:loading.delay class="h-0.5 bg-[#1B6CA8]/20 relative overflow-hidden"><div class="absolute inset-y-0 right-0 w-1/3 bg-[#1B6CA8] animate-pulse"></div></div>
    <table class="data-table">
        <thead>
            <tr>
                <th class="w-16">#</th>
                <th>الاسم</th>
                <th>الكود</th>
                <th class="text-left" dir="ltr">سعر البيع</th>
                <th class="text-left" dir="ltr">سعر الشراء</th>
                <th>التصنيف</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
            @php $f = $row->flat(); @endphp
            <tr>
                <td class="text-gray-400 font-mono text-xs">{{ $row->id }}</td>
                <td class="font-semibold">{{ $row->displayName() }}</td>
                <td class="font-mono text-xs" dir="ltr">{{ $row->productCode() }}</td>
                <td class="font-mono text-xs" dir="ltr">{{ $f['UnitPrice'] ?? '—' }}</td>
                <td class="font-mono text-xs" dir="ltr">{{ $f['BuyPrice'] ?? '—' }}</td>
                <td class="text-sm text-gray-500">{{ trim((string) ($f['Category'] ?? '')) !== '' ? $f['Category'] : '—' }}</td>
            </tr>
            @empty
            <tr><td colspan="6">
                <div class="text-center py-16 text-gray-300">
                    <p class="text-sm">{{ $search ? 'لا توجد نتائج' : 'لا توجد خدمات في الأرشيف' }}</p>
                </div>
            </td></tr>
            @endforelse
        </tbody>
    </table>

    <x-list-pagination :paginator="$rows" />
</div>

</div>
