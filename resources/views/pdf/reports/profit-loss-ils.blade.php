<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
@include('pdf.reports.partials.styles')
</head>
<body>

@include('pdf.reports.partials.header')

<div class="summary-box">
    <div class="summary-row"><span>إيرادات/مبيعات</span><span class="positive" dir="ltr">{{ number_format($totals['sales'], 2) }} ILS</span></div>
    <div class="summary-row"><span>تكلفة المباع</span><span class="negative" dir="ltr">{{ number_format($totals['purchases'], 2) }} ILS</span></div>
    <div class="summary-row"><span>مصروفات</span><span class="negative" dir="ltr">{{ number_format($totals['expenses'], 2) }} ILS</span></div>
    <div class="summary-row"><span>رواتب</span><span class="negative" dir="ltr">{{ number_format($totals['salaries'], 2) }} ILS</span></div>
    <div class="summary-row"><span><strong>صافي الربح/الخسارة</strong></span>
        <span class="{{ $totals['net_profit'] >= 0 ? 'positive' : 'negative' }}" dir="ltr"><strong>{{ number_format($totals['net_profit'], 2) }} ILS</strong></span>
    </div>
</div>

@if(!empty($totals['rates']))
<p class="text-sm" style="margin-top:12px;">أسعار BOI بتاريخ {{ $totals['rate_date'] }}:</p>
@foreach($totals['rates'] as $cur => $rate)
<p dir="ltr" style="font-size:11px;">{{ $cur }} = {{ number_format($rate, 4) }}</p>
@endforeach
@endif

</body>
</html>
