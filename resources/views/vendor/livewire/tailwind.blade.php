@php
if (! isset($scrollTo)) {
    $scrollTo = 'body';
}

$scrollIntoViewJsSnippet = ($scrollTo !== false)
    ? <<<JS
       (\$el.closest('{$scrollTo}') || document.querySelector('{$scrollTo}')).scrollIntoView()
    JS
    : '';
@endphp

@if ($paginator->hasPages())
    <nav role="navigation" aria-label="تصفح الصفحات" dir="rtl" class="inline-flex w-full max-w-full flex-row flex-nowrap items-center justify-end gap-0 sm:w-auto">
        <div class="flex w-full shrink-0 justify-between gap-2 sm:hidden">
            @if ($paginator->onFirstPage())
                <span class="inline-flex flex-1 items-center justify-center rounded-lg border border-[#E2E8F0] bg-white px-3 py-2 text-sm font-medium text-[#9CA3AF]">
                    السابق
                </span>
            @else
                <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled"
                        dusk="previousPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.before"
                        class="inline-flex flex-1 items-center justify-center rounded-lg border border-[#E2E8F0] bg-white px-3 py-2 text-sm font-medium text-[#1E293B] shadow-[0_1px_2px_rgba(0,0,0,0.04)] transition hover:bg-[#FAFBFC] focus:outline-none focus:ring-2 focus:ring-[#1B6CA8]/25">
                    السابق
                </button>
            @endif

            @if ($paginator->hasMorePages())
                <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled"
                        dusk="nextPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.before"
                        class="inline-flex flex-1 items-center justify-center rounded-lg border border-[#E2E8F0] bg-white px-3 py-2 text-sm font-medium text-[#1E293B] shadow-[0_1px_2px_rgba(0,0,0,0.04)] transition hover:bg-[#FAFBFC] focus:outline-none focus:ring-2 focus:ring-[#1B6CA8]/25">
                    التالي
                </button>
            @else
                <span class="inline-flex flex-1 items-center justify-center rounded-lg border border-[#E2E8F0] bg-white px-3 py-2 text-sm font-medium text-[#9CA3AF]">
                    التالي
                </span>
            @endif
        </div>

        <div class="hidden min-w-0 flex-1 sm:block">
            <span class="inline-flex max-w-full flex-nowrap rtl:flex-row-reverse rounded-lg shadow-[0_1px_2px_rgba(0,0,0,0.04)]">
                @if ($paginator->onFirstPage())
                    <span aria-disabled="true" aria-label="الصفحة السابقة">
                        <span class="inline-flex items-center rounded-s-lg border border-[#E2E8F0] bg-[#F3F4F6] px-2 py-2 text-[#C0C4CC]" aria-hidden="true">
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        </span>
                    </span>
                @else
                    <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}"
                            dusk="previousPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.after"
                            class="inline-flex items-center rounded-s-lg border border-[#E2E8F0] bg-white px-2 py-2 text-[#6B7280] transition hover:bg-[#FAFBFC] hover:text-[#1E293B] focus:z-10 focus:outline-none focus:ring-2 focus:ring-[#1B6CA8]/25"
                            aria-label="الصفحة السابقة">
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                    </button>
                @endif

                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span aria-disabled="true">
                            <span class="-ms-px inline-flex items-center border border-[#E2E8F0] bg-white px-3 py-2 text-sm font-medium text-[#9CA3AF]">{{ $element }}</span>
                        </span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            <span wire:key="paginator-{{ $paginator->getPageName() }}-page{{ $page }}">
                                @if ($page == $paginator->currentPage())
                                    <span aria-current="page">
                                        <span class="-ms-px inline-flex min-w-[2.5rem] items-center justify-center border border-[#1B6CA8] bg-[#1B6CA8]/12 px-3 py-2 text-sm font-bold tabular-nums text-[#1E293B]">{{ $page }}</span>
                                    </span>
                                @else
                                    <button type="button" wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}"
                                            class="-ms-px inline-flex min-w-[2.5rem] items-center justify-center border border-[#E2E8F0] bg-white px-3 py-2 text-sm font-medium tabular-nums text-[#1E293B] transition hover:bg-[#FAFBFC] focus:z-10 focus:outline-none focus:ring-2 focus:ring-[#1B6CA8]/20"
                                            aria-label="الانتقال إلى الصفحة {{ $page }}">
                                        {{ $page }}
                                    </button>
                                @endif
                            </span>
                        @endforeach
                    @endif
                @endforeach

                @if ($paginator->hasMorePages())
                    <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}"
                            dusk="nextPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.after"
                            class="-ms-px inline-flex items-center rounded-e-lg border border-[#E2E8F0] bg-white px-2 py-2 text-[#6B7280] transition hover:bg-[#FAFBFC] hover:text-[#1E293B] focus:z-10 focus:outline-none focus:ring-2 focus:ring-[#1B6CA8]/25"
                            aria-label="الصفحة التالية">
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                    </button>
                @else
                    <span aria-disabled="true" aria-label="الصفحة التالية">
                        <span class="-ms-px inline-flex items-center rounded-e-lg border border-[#E2E8F0] bg-[#F3F4F6] px-2 py-2 text-[#C0C4CC]" aria-hidden="true">
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                        </span>
                    </span>
                @endif
            </span>
        </div>
    </nav>
@endif
