@props([
    'title',
    'name',
    'active' => false,
])

{{-- مجموعة قائمة جانبية قابلة للطي/الفتح مع تذكّر الحالة --}}
<div
    class="pt-2"
    x-data="{
        storageKey: 'gaz-nav-{{ $name }}',
        routeActive: {{ $active ? 'true' : 'false' }},
        open: {{ $active ? 'true' : 'false' }},
        init() {
            if (this.routeActive) {
                this.open = true;
                return;
            }
            const saved = localStorage.getItem(this.storageKey);
            if (saved !== null) {
                this.open = saved === '1';
            }
        },
        toggle() {
            this.open = ! this.open;
            localStorage.setItem(this.storageKey, this.open ? '1' : '0');
        },
    }"
>
    <button type="button"
            @click="toggle()"
            class="w-full flex items-center justify-between gap-2 px-2 py-2.5 rounded-lg
                   text-sm font-bold text-gray-500 tracking-wide
                   hover:bg-[#F7F8FA] hover:text-[#1B6CA8] transition select-none">
        <span>{{ $title }}</span>
        <svg xmlns="http://www.w3.org/2000/svg"
             class="w-4 h-4 shrink-0 transition-transform duration-200"
             :class="open ? 'rotate-180' : ''"
             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    <div x-show="open"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 -translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="space-y-0.5 pb-1"
         @if(! $active) style="display: none;" @endif>
        {{ $slot }}
    </div>
</div>
