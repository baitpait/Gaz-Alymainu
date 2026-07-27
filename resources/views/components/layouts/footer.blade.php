@php
    $developerUrl = config('app.developer_url', 'https://baitpait.com');
    $developerCredit = config('app.developer_credit', 'تطوير وبرمجة بيت البرمجيات وتكنولوجيا المعلومات');
    $dark = $dark ?? false;
@endphp

<footer class="shrink-0 border-t px-5 py-3 text-center text-xs
    {{ $dark ? 'border-white/10 bg-transparent text-white/40' : 'border-[#E2E8F0] bg-[#FAFAFA] text-gray-500' }}">
    <a href="{{ $developerUrl }}"
       target="_blank"
       rel="noopener noreferrer"
       class="font-medium transition {{ $dark ? 'text-[#1B6CA8] hover:text-[#E85D04]' : 'text-[#1B6CA8] hover:text-[#145088]' }}"
       style="text-decoration:none;">
        {{ $developerCredit }}
    </a>
</footer>
