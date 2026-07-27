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
        <td width="28%" style="vertical-align:middle;">
            <div class="doc-title" style="font-size:22pt;">فاتورة مشتريات</div>
        </td>
        <td width="44%" align="center" style="vertical-align:middle;">
            @if($logoExists)
            <img src="{{ $logoPath }}" class="logo" alt="Logo">
            @endif
        </td>
        <td width="28%" align="right" style="vertical-align:middle;">
            <div class="brand-name">{{ $companyName }}</div>
            <div class="brand-sub">شركة توزيع غاز</div>
        </td>
    </tr>
</table>

<table class="info-table" width="100%">
    <tr>
        <td class="info-box" width="33%">
            <div class="info-lbl">رقم المستند</div>
            <div class="info-val">{{ $purchaseOrder->legacy_po_no ?? '#'.$purchaseOrder->id }}</div>
        </td>
        <td class="info-box" width="33%">
            <div class="info-lbl">تاريخ المستند</div>
            <div class="info-val">{{ $purchaseOrder->document_date?->format('Y-m-d') ?? '—' }}</div>
        </td>
        @if($purchaseOrder->due_date)
        <td class="info-box" width="33%">
            <div class="info-lbl">تاريخ الاستحقاق</div>
            <div class="info-val">{{ $purchaseOrder->due_date->format('Y-m-d') }}</div>
        </td>
        @endif
    </tr>
</table>

<table width="100%" cellpadding="0" cellspacing="8">
    <tr>
        <td width="65%" valign="top">
            <div class="party-box">
                <div class="section-lbl">بيانات المورد</div>
                <div class="party-name">{{ $supplier?->displayName() ?? '—' }}</div>
                @if($supplier?->phone_primary)
                <div class="party-sub">{{ $supplier->phone_primary }}</div>
                @endif
            </div>
        </td>
        <td width="35%" valign="top">
            <div class="amount-box">
                <div class="amount-head">مجموع المستند</div>
                <div class="amount-value">{{ number_format((float) $purchaseOrder->total_amount, 2) }}</div>
                <div class="amount-currency">{{ $purchaseOrder->currency_code }}</div>
            </div>
        </td>
    </tr>
</table>

@if($purchaseOrder->lines->isNotEmpty())
<table class="lines-table">
    <thead>
        <tr>
            <th width="5%">#</th>
            <th>البند</th>
            <th width="14%" class="ltr">سعر الوحدة</th>
            <th width="10%" class="center">الكمية</th>
            <th width="14%" class="ltr">المجموع</th>
        </tr>
    </thead>
    <tbody>
        @foreach($purchaseOrder->lines as $line)
        <tr>
            <td class="center">{{ $loop->iteration }}</td>
            <td><strong>{{ $line->title }}</strong></td>
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
        <td>مجموع المستند</td>
        <td class="ltr">{{ number_format((float) $purchaseOrder->total_amount, 2) }} {{ $purchaseOrder->currency_code }}</td>
    </tr>
    @if($supplierBalanceDue !== null)
    <tr class="due">
        <td>المبلغ المستحق للمورد</td>
        <td class="ltr">{{ number_format((float) $supplierBalanceDue, 2) }} {{ $purchaseOrder->currency_code }}</td>
    </tr>
    @endif
</table>

<div class="amount-words">{{ $amountInWords }}</div>

@if($purchaseOrder->notes)
<div class="notes-box"><strong style="color:#C9A227;">ملاحظات:</strong> {{ $purchaseOrder->notes }}</div>
@endif

<div class="footer">{{ $companyName }} — مستند مشتريات رسمي</div>
<div class="print-date">تاريخ الطباعة: {{ now()->format('Y-m-d') }}</div>

</body>
</html>
