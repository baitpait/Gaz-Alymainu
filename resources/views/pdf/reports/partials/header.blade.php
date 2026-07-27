@php
    $logoPath = public_path('branding/logo.png');
    $logoExists = file_exists($logoPath);
@endphp
<div class="header">
    <div class="header-right">
        <div class="header-title">{{ $reportTitle }}</div>
        @if(!empty($filterLabels))
        <div class="header-subtitle">{{ implode(' · ', $filterLabels) }}</div>
        @endif
    </div>
    <div class="header-left">
        @if($logoExists)
            <img src="{{ $logoPath }}" class="header-logo" alt="Logo">
        @endif
        <div class="header-company">{{ $companyName }}</div>
        <div class="header-date">Printed: {{ $printedAt }}</div>
    </div>
    <div style="clear:both;"></div>
</div>

@if(!empty($filterLabels))
<div class="filters">
    @foreach($filterLabels as $label)
        <span>{{ $label }}</span>
    @endforeach
</div>
@endif
