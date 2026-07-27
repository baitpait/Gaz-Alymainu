@props(['variant' => 'float'])

@php
    $phone = preg_replace('/\D+/', '', (string) config('app.support_whatsapp', '970599814758'));
    $message = rawurlencode('مرحباً، أحتاج دعماً فنياً لنظام '.config('app.name', 'غاز اليمني'));
    $href = 'https://wa.me/'.$phone.'?text='.$message;
@endphp

@if($variant === 'sidebar')
<a href="{{ $href }}"
   target="_blank"
   rel="noopener noreferrer"
   title="الدعم الفني — واتساب {{ $phone }}"
   class="flex justify-center px-2 py-2 rounded-xl hover:bg-[#F0FDF4] transition group"
   style="text-decoration:none;"
   aria-label="الدعم الفني واتساب">
    <img src="{{ asset('branding/support.png') }}"
         alt="الدعم الفني"
         class="w-28 h-28 object-contain drop-shadow-sm group-hover:scale-105 transition-transform cursor-pointer"
         onerror="this.style.display='none'">
</a>
@elseif($variant === 'nav')
<a href="{{ $href }}"
   target="_blank"
   rel="noopener noreferrer"
   class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-sm font-semibold transition-all
          bg-[#25D366] text-white shadow-sm hover:bg-[#1EBE57] hover:shadow-md border border-[#1EBE57]"
   style="text-decoration:none;">
    <span class="inline-flex h-6 w-6 items-center justify-center rounded-md bg-white/20 text-white shrink-0" aria-hidden="true">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
        </svg>
    </span>
    <span class="flex-1 min-w-0">
        <span class="block">الدعم الفني</span>
        <span class="block text-[10px] font-normal opacity-90" dir="ltr">WhatsApp · {{ $phone }}</span>
    </span>
</a>
@elseif($variant === 'header')
<a href="{{ $href }}"
   target="_blank"
   rel="noopener noreferrer"
   title="الدعم الفني — واتساب {{ $phone }}"
   class="inline-flex items-center gap-2 px-2.5 sm:px-3 py-1.5 rounded-lg text-xs sm:text-sm font-semibold shadow-sm transition shrink-0
          bg-[#25D366] text-white border border-[#1EBE57] hover:bg-[#1EBE57] hover:shadow-md"
   style="text-decoration:none;">
    <span class="inline-flex h-5 w-5 sm:h-[22px] sm:w-[22px] items-center justify-center rounded-md bg-white/20 text-white shrink-0" aria-hidden="true">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 sm:h-4 sm:w-4" viewBox="0 0 24 24" fill="currentColor">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
        </svg>
    </span>
    <span class="whitespace-nowrap">الدعم الفني</span>
</a>
@endif
