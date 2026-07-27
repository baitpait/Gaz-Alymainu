@props([
    'printUrl' => null,
    'pdfUrl' => null,
])

@if($printUrl)
<a href="{{ $printUrl }}" target="_blank" rel="noopener"
   class="btn btn-ghost py-1 px-2 text-xs text-[#1B6CA8] hover:bg-amber-50"
   style="text-decoration:none;">طباعة</a>
@endif
@if($pdfUrl)
<a href="{{ $pdfUrl }}" target="_blank" rel="noopener"
   class="btn btn-ghost py-1 px-2 text-xs text-[#1E293B] hover:bg-gray-100"
   style="text-decoration:none;">PDF</a>
@endif
