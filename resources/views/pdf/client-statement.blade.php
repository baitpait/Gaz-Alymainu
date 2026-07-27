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

/* ===== HEADER ===== */
.header {
    width: 100%;
    margin-bottom: 14pt;
    overflow: hidden;
}
.header-right {
    float: right;
}
.header-title {
    font-size: 22pt;
    font-weight: bold;
    color: #1a1a1a;
}
.header-title span {
    font-size: 14pt;
    font-weight: normal;
    color: #3D3D3D;
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

/* ===== SECTION TITLE ===== */
.section-title {
    font-size: 12pt;
    font-weight: bold;
    text-align: right;
    margin-bottom: 6pt;
    margin-top: 4pt;
}

/* ===== MAIN TABLE ===== */
.main-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 9pt;
}
.main-table th {
    background: #E8E8E8;
    border: 1pt solid #CCCCCC;
    padding: 5pt 7pt;
    font-weight: bold;
    text-align: right;
}
.main-table th.ltr { direction: ltr; text-align: left; }

/* صف الفاتورة - رأس */
.row-invoice-header td {
    border: 1pt solid #CCCCCC;
    padding: 5pt 7pt;
    font-weight: bold;
    background: #FAFAFA;
}
.row-invoice-header .inv-number {
    font-weight: bold;
    font-size: 10pt;
}

/* صف تفاصيل الفاتورة (جدول داخلي) */
.row-invoice-lines td {
    border-right: 1pt solid #CCCCCC;
    border-left: 1pt solid #CCCCCC;
    padding: 0;
}
.lines-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 8.5pt;
}
.lines-table th {
    background: #C9A227;
    color: #fff;
    padding: 4pt 6pt;
    text-align: right;
    font-weight: bold;
    border-right: 1pt solid #b8911e;
}
.lines-table th:first-child { border-right: none; }
.lines-table td {
    padding: 4pt 6pt;
    border-top: 1pt solid #E8E8E8;
    border-right: 1pt solid #E8E8E8;
    text-align: right;
    background: #fff;
}
.lines-table td.ltr { direction: ltr; text-align: left; }

/* صف إجمالي الفاتورة */
.row-invoice-total td {
    border: 1pt solid #CCCCCC;
    padding: 4pt 7pt;
    background: #F5F5F5;
    font-weight: bold;
    direction: ltr;
    text-align: left;
}
.row-invoice-total .label-cell {
    direction: rtl;
    text-align: right;
    font-size: 8.5pt;
    color: #555;
}

/* صف الدفعة */
.row-payment td {
    border: 1pt solid #CCCCCC;
    padding: 5pt 7pt;
    background: #FFFDF5;
}
.row-payment .pay-desc {
    font-weight: bold;
    color: #1a1a1a;
}
.row-payment .amount-col {
    direction: ltr;
    text-align: left;
    color: #DC2626;
    font-weight: bold;
}
.row-payment .balance-col {
    direction: ltr;
    text-align: left;
    font-weight: bold;
}

/* صف رصيد نهاية المدة */
.row-final td {
    border: 1pt solid #CCCCCC;
    padding: 6pt 7pt;
    background: #3D3D3D;
    color: #fff;
    font-weight: bold;
    font-size: 10pt;
}
.row-final .balance-col {
    direction: ltr;
    text-align: left;
    font-size: 11pt;
}

.ltr  { direction: ltr; text-align: left; }
.rtl  { direction: rtl; text-align: right; }
.amt  { direction: ltr; text-align: left; font-weight: bold; }
.amt-negative { color: #DC2626; }
.amt-positive { color: #1a1a1a; }

/* ===== FOOTER ===== */
.footer {
    margin-top: 18pt;
    text-align: center;
}
.footer img {
    width: 70pt;
}

/* currency section spacing */
.currency-block { margin-bottom: 24pt; }

.summary-box {
    border: 1pt solid #CCCCCC;
    background: #FAFAFA;
    padding: 8pt 10pt;
    margin-bottom: 10pt;
    width: 58%;
}
.summary-row {
    width: 100%;
    font-size: 9.5pt;
    margin-bottom: 4pt;
    overflow: hidden;
}
.summary-row span:first-child { float: right; color: #555; }
.summary-row span:last-child {
    float: left;
    direction: ltr;
    font-weight: bold;
}
.summary-total {
    border-top: 1pt solid #CCCCCC;
    padding-top: 5pt;
    margin-top: 5pt;
    font-size: 11pt;
    font-weight: bold;
}
</style>
</head>
<body>

@php
    $logoPath = public_path('branding/logo-print.png');
    $logoExists = file_exists($logoPath);

    $printDate = $dateTo
        ? \Carbon\Carbon::parse($dateTo)->format('d/m/Y')
        : now()->format('d/m/Y');

    $methods = ['cash' => 'Cash', 'bank' => 'Bank', 'check' => 'Check', 'transfer' => 'Transfer'];
@endphp

{{-- ===== HEADER ===== --}}
<div class="header">
    <div class="header-right">
        <div class="header-title">
            كشف حساب&nbsp; <span>{{ $client->displayName() }}</span>
        </div>
    </div>
    <div class="header-left">
        @if($logoExists)
            <img src="{{ $logoPath }}" class="header-logo" alt="Logo">
        @endif
        <div class="header-company">Gaz Alymainu</div>
        <div class="header-date">Date: {{ $printDate }}</div>
    </div>
    <div style="clear:both;"></div>
</div>

@if(empty($statement))
    <p style="color:#999; text-align:center; margin-top:40pt;">لا توجد حركات مالية في هذه الفترة.</p>
@endif

{{-- ===== قسم لكل عملة ===== --}}
@foreach($statement as $currency => $section)
<div class="currency-block">

    <div class="summary-box">
        <div class="summary-row">
            <span>إجمالي الفواتير</span>
            <span>{{ number_format($section['total_invoiced'], 2) }} {{ $currency }}</span>
        </div>
        <div class="summary-row">
            <span>إجمالي الدفعات</span>
            <span>{{ number_format($section['total_paid'], 2) }} {{ $currency }}</span>
        </div>
        <div class="summary-row">
            <span>إجمالي التسويات</span>
            <span>{{ number_format($section['total_adjusted'], 2) }} {{ $currency }}</span>
        </div>
        <div class="summary-row summary-total">
            <span>الرصيد المستحق</span>
            <span>{{ number_format($section['balance'], 2) }} {{ $currency }}</span>
        </div>
    </div>

    <div class="section-title">حركة الحساب حتى {{ $printDate }}</div>

    <table class="main-table">
        <thead>
            <tr>
                <th style="width:70pt;">التاريخ</th>
                <th>العملية</th>
                <th style="width:90pt;" class="ltr">المبلغ ({{ $currency }})</th>
            </tr>
        </thead>
        <tbody>

        @foreach($section['timeline'] as $event)

            @if($event['type'] === 'invoice')
            @php $inv = $event['model']; $invNo = $inv->legacy_invoice_no ?? ('#' . $inv->id); @endphp

            {{-- رأس الفاتورة --}}
            <tr class="row-invoice-header">
                <td class="ltr" style="font-size:8.5pt;">{{ $event['date']->format('d/m/Y') }}</td>
                <td>
                    <span class="inv-number">فاتورة {{ $invNo }}</span>
                </td>
                <td class="ltr" style="font-weight:bold;">
                    +{{ number_format($event['amount'], 2) }}
                </td>
            </tr>

            {{-- بنود الفاتورة --}}
            @if($inv->lines->count() > 0)
            <tr class="row-invoice-lines">
                <td colspan="3">
                    <table class="lines-table">
                        <thead>
                            <tr>
                                <th style="width:80pt;">السعر X الكمية</th>
                                <th>البند</th>
                                <th>التفاصيل</th>
                                <th style="width:70pt;">الإجمالي</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($inv->lines as $line)
                            <tr>
                                <td class="ltr" style="white-space:nowrap;">
                                    {{ number_format((float)$line->unit_price, 2) }}
                                    x {{ number_format((float)$line->quantity, 0) }}
                                </td>
                                <td>{{ $line->title }}</td>
                                <td>{{ $line->displayDetails() ?? '—' }}</td>
                                <td class="ltr">{{ number_format((float)$line->line_total, 2) }} {{ $currency }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </td>
            </tr>
            @endif

            @elseif($event['type'] === 'payment')
            @php
                $pay = $event['model'];
                $payNo = $pay->bank_reference ?? ('#'.$pay->id);
                $methodLabel = $methods[$pay->method] ?? $pay->method ?? '';
            @endphp
            <tr class="row-payment">
                <td class="ltr" style="font-size:8.5pt;">{{ $event['date']->format('d/m/Y') }}</td>
                <td>
                    <span class="pay-desc">دفعة {{ $payNo }}@if($methodLabel) ({{ $methodLabel }})@endif</span>
                </td>
                <td class="ltr amt-negative">−{{ number_format($event['amount'], 2) }}</td>
            </tr>
            @else
            @php $adj = $event['model']; @endphp
            <tr class="row-payment" style="background:#F5F3FF;">
                <td class="ltr" style="font-size:8.5pt;">{{ $event['date']->format('d/m/Y') }}</td>
                <td>تسوية #{{ $adj->id }} ({{ $adj->typeLabel() }})@if($adj->reason) — {{ $adj->reason }}@endif</td>
                <td class="ltr" style="color:#7C3AED;font-weight:bold;">−{{ number_format($event['amount'], 2) }}</td>
            </tr>
            @endif

        @endforeach

        </tbody>
    </table>

</div>
@endforeach

{{-- ===== FOOTER ===== --}}
<div class="footer">
    @if($logoExists)
        <img src="{{ $logoPath }}" alt="Gaz Alymainu">
    @endif
</div>

</body>
</html>
