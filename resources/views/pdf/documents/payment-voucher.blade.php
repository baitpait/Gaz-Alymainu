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
            <div class="doc-title" style="font-size:24pt;">{{ $voucherTitle }}</div>
            <div class="doc-sub">{{ $voucherSubtitle }}</div>
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
            <div class="info-lbl">رقم السند</div>
            <div class="info-val">#{{ $payment->id }}</div>
        </td>
        <td class="info-box" width="33%">
            <div class="info-lbl">تاريخ الدفع</div>
            <div class="info-val">{{ $payment->paid_at?->format('Y-m-d') ?? '—' }}</div>
        </td>
        <td class="info-box" width="33%">
            <div class="info-lbl">تاريخ الطباعة</div>
            <div class="info-val">{{ now()->format('Y-m-d') }}</div>
        </td>
    </tr>
</table>

<div class="party-box">
    <div class="section-lbl">{{ $partyLabel }}</div>
    <div class="party-name">{{ $partyName }}</div>
    @if($party?->phone_primary)
    <div class="party-sub ltr">{{ $party->phone_primary }}</div>
    @endif
</div>

<div class="amount-box">
    <div class="amount-head">المبلغ</div>
    <div class="amount-value">{{ number_format((float) $payment->amount, 2) }}</div>
    <div class="amount-currency">{{ $payment->currency_code }}</div>
</div>

<div class="amount-words">{{ $amountInWords }}</div>

<table class="details-table">
    <tr>
        <td class="lbl">طريقة الدفع</td>
        <td>{{ $methodLabel }}</td>
    </tr>
    @if($payment->bank_reference)
    <tr>
        <td class="lbl">رقم المرجع / الشيك</td>
        <td class="ltr">{{ $payment->bank_reference }}</td>
    </tr>
    @endif
    @if($payment->notes)
    <tr>
        <td class="lbl">ملاحظات</td>
        <td>{{ $payment->notes }}</td>
    </tr>
    @endif
    @if($payment->recordedBy)
    <tr>
        <td class="lbl">سجّل بواسطة</td>
        <td>{{ $payment->recordedBy->full_name ?? $payment->recordedBy->email }}</td>
    </tr>
    @endif
</table>

<table class="sig-table">
    <tr>
        <td>توقيع المستلم</td>
        <td>توقيع الطرف</td>
        <td>الختم</td>
    </tr>
</table>

<div class="footer">{{ $companyName }} — {{ $voucherTitle }} رسمي</div>

</body>
</html>
