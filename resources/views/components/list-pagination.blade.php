@props(['paginator'])

<footer {{ $attributes->merge([
    'class' => 'border-t border-[#E2E8F0] bg-[#F7F8FA]',
]) }} dir="rtl" aria-label="تصفح القائمة وعدد السجلات">
    {{-- صف واحد من كل العروض: القائمة يمين، التصفح يسار (RTL) — تمرير أفقي عند الضيق --}}
    <div class="flex flex-row flex-nowrap items-center justify-between gap-4 px-4 py-3.5 sm:gap-8 md:gap-10">
        <div class="flex shrink-0 flex-nowrap items-center justify-start gap-2">
            <span class="shrink-0 text-xs font-semibold text-[#9CA3AF] hidden sm:inline">عدد السجلات</span>
            <div class="inline-flex shrink-0 flex-nowrap items-center whitespace-nowrap rounded-lg border border-[#E2E8F0] bg-white px-2.5 py-1 shadow-[0_1px_2px_rgba(0,0,0,0.03)]">
                <select id="list-per-page-{{ $paginator->getPageName() }}"
                        wire:model.live="perPage"
                        class="input !w-auto min-w-[5.25rem] max-w-[7rem] shrink-0 grow-0 basis-auto min-h-0 py-1.5 ps-2 pe-7 text-sm font-medium text-[#1E293B] border-0 bg-transparent shadow-none focus:ring-0 focus:ring-offset-0"
                        aria-label="عدد السجلات في الصفحة">
                    <option value="50">٥٠</option>
                    <option value="100">١٠٠</option>
                    <option value="200">٢٠٠</option>
                    <option value="0">الكل</option>
                </select>
            </div>
        </div>

        <div class="min-w-0 flex flex-1 flex-nowrap items-center justify-end">
            @if($paginator->total() > 0)
                @if($paginator->hasPages())
                    <div class="w-full min-w-0 max-w-full overflow-x-auto pb-0.5 [-webkit-overflow-scrolling:touch] sm:pb-0">
                        {{ $paginator->links() }}
                    </div>
                @else
                    <p class="text-sm text-[#6B7280] tabular-nums whitespace-nowrap self-center">
                        <span class="text-[#1E293B] font-semibold" dir="ltr">{{ $paginator->total() }}</span>
                        سجلًا في الصفحة الحالية
                    </p>
                @endif
            @endif
        </div>
    </div>
</footer>
