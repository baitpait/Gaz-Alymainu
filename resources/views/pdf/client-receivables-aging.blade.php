<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: xbriyaz, sans-serif;
    color: #3D3D3D;
    font-size: 10pt;
    direction: rtl;
}

.header {
    width: 100%;
    margin-bottom: 14pt;
    overflow: hidden;
}
.header-right { float: right; }
.header-title {
    font-size: 20pt;
    font-weight: bold;
    color: #1a1a1a;
}
.header-subtitle {
    font-size: 10pt;
    color: #555;
    margin-top: 4pt;
}
.header-left {
    float: left;
    text-align: left;
    direction: ltr;
}
.header-logo {
    width: 90pt;
    display: block;
    margin-bottom: 4pt;
}
.header-company {
    font-size: 10pt;
    font-weight: bold;
    color: #3D3D3D;
    direction: ltr;
    text-align: center;
}
.header-date {
    font-size: 9pt;
    color: #555;
    direction: ltr;
    text-align: center;
    margin-top: 3pt;
}

.filters {
    margin-bottom: 10pt;
    font-size: 9pt;
    color: #555;
}
.filters span {
    display: inline-block;
    background: #F5F5F5;
    border: 1pt solid #E0E0E0;
    padding: 2pt 6pt;
    margin: 2pt 0 2pt 4pt;
    border-radius: 2pt;
}

.main-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 9pt;
    margin-bottom: 14pt;
}
.main-table th {
    background: #E8E8E8;
    border: 1pt solid #CCCCCC;
    padding: 5pt 7pt;
    font-weight: bold;
    text-align: right;
}
.main-table th.ltr { direction: ltr; text-align: left; }
.main-table td {
    border: 1pt solid #CCCCCC;
    padding: 5pt 7pt;
    text-align: right;
}
.main-table td.ltr { direction: ltr; text-align: left; }
.main-table tr:nth-child(even) td { background: #FAFAFA; }

.summary-grid {
    width: 100%;
    border-collapse: collapse;
    font-size: 9pt;
}
.summary-grid th,
.summary-grid td {
    border: 1pt solid #CCCCCC;
    padding: 5pt 7pt;
}
.summary-grid th {
    background: #E8E8E8;
    font-weight: bold;
    text-align: right;
}
.summary-grid td.ltr { direction: ltr; text-align: left; }
.section-title {
    font-size: 11pt;
    font-weight: bold;
    margin: 10pt 0 6pt;
}
.empty-msg {
    color: #999;
    text-align: center;
    margin: 30pt 0;
}
</style>
</head>
<body>

@php
    $logoPath = public_path('branding/logo.png');
    $logoExists = file_exists($logoPath);
@endphp

<div class="header">
    <div class="header-right">
        <div class="header-title">أعمار ذمم العملاء</div>
        <div class="header-subtitle">رصيد مستحق — أيام من أقدم فاتورة غير مسدّدة (FIFO)</div>
    </div>
    <div class="header-left">
        @if($logoExists)
            <img src="{{ $logoPath }}" class="header-logo" alt="Logo">
        @endif
        <div class="header-company">{{ $companyName }}</div>
        <div class="header-date">Date: {{ $printedAt }}</div>
    </div>
    <div style="clear:both;"></div>
</div>

@if(count($filterLabels) > 0)
<div class="filters">
    <strong>الفلاتر:</strong>
    @foreach($filterLabels as $label)
        <span>{{ $label }}</span>
    @endforeach
</div>
@endif

@if($rows->isEmpty())
    <p class="empty-msg">لا توجد ذمم مستحقة مطابقة للفلتر الحالي.</p>
@else
    <table class="main-table">
        <thead>
            <tr>
                <th>العميل</th>
                <th class="ltr">الهاتف</th>
                <th class="ltr" style="width:42pt;">العملة</th>
                <th class="ltr" style="width:72pt;">المبلغ المستحق</th>
                <th style="width:52pt;">أيام</th>
                <th class="ltr" style="width:62pt;">تاريخ أول ذمة</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $r)
            <tr>
                <td>{{ $r['client_name'] }}</td>
                <td class="ltr">{{ $r['phone'] ?? '—' }}</td>
                <td class="ltr">{{ $r['currency_code'] }}</td>
                <td class="ltr">{{ number_format((float) $r['balance'], 2) }}</td>
                <td style="text-align:center;">{{ $r['days_from_first_unpaid'] }}</td>
                <td class="ltr">{{ $r['first_unpaid_document_date'] ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="section-title">ملخص الذمم (حسب الفلتر)</div>
    <table class="summary-grid" style="width:48%; float:right; margin-left:2%;">
        <tr>
            <th>عدد العملاء</th>
            <td class="ltr">{{ $summary['client_count'] }}</td>
        </tr>
        <tr>
            <th>إجمالي المستحق</th>
            <td class="ltr">{{ number_format((float) $summary['total_balance'], 2) }}</td>
        </tr>
    </table>

    <table class="summary-grid" style="width:48%; float:left;">
        <thead>
            <tr>
                <th>الفئة</th>
                <th class="ltr">المبلغ</th>
                <th class="ltr">تراكمي</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>0–30 يوم</td>
                <td class="ltr">{{ number_format((float) $summary['buckets']['0_30'], 2) }}</td>
                <td class="ltr">{{ number_format((float) $summary['cumulative']['through_30'], 2) }}</td>
            </tr>
            <tr>
                <td>31–60 يوم</td>
                <td class="ltr">{{ number_format((float) $summary['buckets']['31_60'], 2) }}</td>
                <td class="ltr">{{ number_format((float) $summary['cumulative']['through_60'], 2) }}</td>
            </tr>
            <tr>
                <td>61–90 يوم</td>
                <td class="ltr">{{ number_format((float) $summary['buckets']['61_90'], 2) }}</td>
                <td class="ltr">{{ number_format((float) $summary['cumulative']['through_90'], 2) }}</td>
            </tr>
            <tr>
                <td>91+ يوم</td>
                <td class="ltr">{{ number_format((float) $summary['buckets']['91_plus'], 2) }}</td>
                <td class="ltr">{{ number_format((float) $summary['cumulative']['all'], 2) }}</td>
            </tr>
        </tbody>
    </table>
    <div style="clear:both;"></div>
@endif

</body>
</html>
