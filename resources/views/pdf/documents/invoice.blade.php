<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
@include('pdf.documents._styles')
</head>
<body>

@php
    $logoExists = !empty($logoPath) && file_exists($logoPath);
@endphp

<table width="100%" class="gold-bar" cellpadding="0" cellspacing="0">
    <tr>
        <td width="25%" style="vertical-align:middle;">
            <div class="doc-title">فاتورة</div>
        </td>
        <td width="50%" align="center" style="vertical-align:middle;">
            @if($logoExists)
            <img src="{{ $logoPath }}" class="logo" alt="Logo">
            @endif
        </td>
        <td width="25%" align="right" style="vertical-align:middle;">
            <div class="brand-name">{{ $companyName }}</div>
            <div class="brand-sub">شركة توزيع غاز</div>
        </td>
    </tr>
</table>

<table class="info-table" width="100%">
    <tr>
        <td class="info-box" width="33%">
            <div class="info-lbl">رقم الفاتورة</div>
            <div class="info-val">{{ $invoice->legacy_invoice_no ?? '#'.$invoice->id }}</div>
        </td>
        <td class="info-box" width="33%">
            <div class="info-lbl">تاريخ الفاتورة</div>
            <div class="info-val">{{ $invoice->document_date?->format('Y-m-d') ?? '—' }}</div>
        </td>
        @if($invoice->due_date)
        <td class="info-box" width="33%">
            <div class="info-lbl">تاريخ الاستحقاق</div>
            <div class="info-val">{{ $invoice->due_date->format('Y-m-d') }}</div>
        </td>
        @endif
    </tr>
</table>

<table width="100%" cellpadding="0" cellspacing="8">
    <tr>
        <td width="65%" valign="top">
            <div class="party-box">
                <div class="section-lbl">بيانات العميل</div>
                <div class="party-name">{{ $client?->displayName() ?? '—' }}</div>
                @if($client?->phone_primary)
                <div class="party-sub">{{ $client->phone_primary }}</div>
                @endif
                @if($client?->email)
                <div class="party-sub ltr">{{ $client->email }}</div>
                @endif
            </div>
        </td>
        <td width="35%" valign="top">
            <div class="amount-box">
                <div class="amount-head">مجموع الفاتورة</div>
                <div class="amount-value">{{ number_format((float) $invoice->total_amount, 2) }}</div>
                <div class="amount-currency">{{ $invoice->currency_code }}</div>
            </div>
        </td>
    </tr>
</table>

@if($invoice->lines->isNotEmpty())
<table class="lines-table">
    <thead>
        <tr>
            <th width="5%">#</th>
            <th>البند</th>
            <th>الوصف</th>
            <th width="14%" class="ltr">سعر الوحدة</th>
            <th width="10%" class="center">الكمية</th>
            <th width="14%" class="ltr">المجموع</th>
        </tr>
    </thead>
    <tbody>
        @foreach($invoice->lines as $line)
        <tr>
            <td class="center">{{ $loop->iteration }}</td>
            <td><strong>{{ $line->title }}</strong></td>
            <td>{{ $line->description ?: '—' }}</td>
            <td class="ltr">{{ number_format((float) $line->unit_price, 2) }}</td>
            <td class="center">{{ rtrim(rtrim(number_format((float) $line->quantity, 2), '0'), '.') }}</td>
            <td class="ltr">{{ number_format((float) $line->line_total, 2) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

<table class="totals-table" align="left">
    <tr class="grand">
        <td>مجموع الفاتورة</td>
        <td class="ltr">{{ number_format((float) $invoice->total_amount, 2) }} {{ $invoice->currency_code }}</td>
    </tr>
    @if($clientBalanceDue !== null)
    <tr class="due">
        <td>المبلغ المستحق</td>
        <td class="ltr">{{ number_format((float) $clientBalanceDue, 2) }} {{ $invoice->currency_code }}</td>
    </tr>
    @endif
</table>

<div class="amount-words">{{ $amountInWords }}</div>

@if($invoice->notes)
<div class="notes-box"><strong style="color:#1B6CA8;">ملاحظات:</strong> {{ $invoice->notes }}</div>
@endif

<div class="footer">
    {{ $companyName }} — شكراً لثقتكم · لاستفساراتكم: 0569224006
</div>
<div class="print-date">تاريخ الطباعة: {{ now()->format('Y-m-d') }}</div>

</body>
</html>
