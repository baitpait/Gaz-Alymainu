<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<title>{{ $voucherTitle }} #{{ $payment->id }}</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: 'Cairo', 'Segoe UI', Tahoma, Arial, sans-serif;
    direction: rtl;
    background: #f5f5f5;
    color: #222;
    font-size: 13px;
  }

  .page {
    background: #fff;
    width: 794px;
    min-height: 560px;
    margin: 20px auto;
    padding: 40px 45px;
    box-shadow: 0 2px 20px rgba(0,0,0,.12);
  }

  .header {
    margin-bottom: 28px;
    padding-bottom: 0;
    border-bottom: 3px solid #C9A227;
  }

  .header-row {
    display: flex;
    flex-direction: row;
    direction: ltr;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    width: 100%;
  }

  .header-side-doc {
    flex: 0 0 auto;
    min-width: 120px;
    text-align: left;
  }

  .header-logo {
    flex: 0 0 auto;
    text-align: center;
  }

  .header-logo img {
    width: 180px;
    height: 180px;
    object-fit: contain;
    display: block;
    margin: 0 auto;
  }

  .header-side-brand {
    flex: 0 0 auto;
    min-width: 200px;
    max-width: 260px;
    direction: rtl;
    text-align: right;
  }

  .brand-text { line-height: 1.5; }
  .brand-name { font-size: 16px; font-weight: 700; color: #3D3D3D; }
  .brand-sub  { font-size: 12px; color: #888; margin-top: 4px; }

  .doc-title {
    font-size: 36px;
    font-weight: 900;
    color: #C9A227;
    line-height: 1.1;
    text-align: left;
  }
  .doc-subtitle {
    font-size: 12px;
    color: #666;
    margin-top: 4px;
    text-align: left;
  }

  .info-row {
    display: flex;
    gap: 12px;
    margin-bottom: 20px;
    flex-wrap: wrap;
  }

  .info-box {
    flex: 1;
    min-width: 140px;
    border: 1px solid #E2E4E9;
    border-radius: 8px;
    padding: 10px 16px;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .info-box .lbl { font-size: 11px; color: #888; }
  .info-box .val { font-size: 13px; font-weight: 700; color: #3D3D3D; direction: ltr; }

  .client-card {
    background: #FAFAFA;
    border: 1px solid #E2E4E9;
    border-radius: 8px;
    padding: 14px 16px;
    margin-bottom: 20px;
  }

  .client-card .section-lbl { font-size: 11px; color: #C9A227; font-weight: 700; margin-bottom: 6px; }
  .client-card .client-name { font-size: 18px; font-weight: 800; color: #3D3D3D; margin-bottom: 4px; }
  .client-card .client-sub  { font-size: 12px; color: #666; line-height: 1.6; }

  .amount-box {
    border: 2px solid #C9A227;
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 16px;
  }

  .amount-box .head {
    background: #C9A227;
    color: #fff;
    text-align: center;
    font-weight: 700;
    padding: 8px 14px;
    font-size: 12px;
  }

  .amount-box .value {
    text-align: center;
    font-size: 32px;
    font-weight: 900;
    color: #3D3D3D;
    padding: 18px 14px 8px;
    direction: ltr;
  }

  .amount-box .currency {
    text-align: center;
    font-size: 14px;
    color: #666;
    padding-bottom: 14px;
    direction: ltr;
  }

  .amount-words {
    background: #FFFBF0;
    border: 1px solid #F0E6B8;
    border-radius: 6px;
    padding: 12px 16px;
    font-size: 14px;
    font-weight: 600;
    color: #7A6200;
    margin-bottom: 24px;
    text-align: center;
    line-height: 1.7;
  }

  .details-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 28px;
    font-size: 13px;
  }

  .details-table td {
    padding: 10px 12px;
    border: 1px solid #E2E4E9;
  }

  .details-table td.lbl {
    width: 35%;
    background: #FAFAFA;
    color: #666;
    font-weight: 600;
  }

  .details-table td.val {
    font-weight: 700;
    color: #3D3D3D;
  }

  .details-table td.val.ltr { direction: ltr; text-align: left; }

  .signatures {
    display: flex;
    justify-content: space-between;
    gap: 24px;
    margin-top: 36px;
    padding-top: 20px;
    border-top: 1px dashed #E2E4E9;
  }

  .sig-box {
    flex: 1;
    text-align: center;
  }

  .sig-line {
    border-top: 1px solid #3D3D3D;
    margin-top: 48px;
    padding-top: 8px;
    font-size: 12px;
    color: #666;
    font-weight: 600;
  }

  .footer {
    margin-top: 24px;
    font-size: 11px;
    color: #aaa;
    text-align: center;
  }

  @media print {
    body { background: #fff; }
    .page { margin: 0; padding: 28px 32px; box-shadow: none; width: 100%; }
    .print-actions { display: none; }
    @page { margin: 12mm; size: A4; }
  }
</style>
</head>
<body>

@php
    $party = $party ?? null;
@endphp

<div class="page">

  <div class="header">
    <div class="header-row">
      <div class="header-side-doc">
        <div class="doc-title">{{ $voucherTitle }}</div>
        <div class="doc-subtitle">{{ $voucherSubtitle }}</div>
      </div>
      <div class="header-logo">
        <img src="{{ asset('branding/logo-print.png') }}" alt="Logo" onerror="this.style.display='none'">
      </div>
      <div class="header-side-brand">
        <div class="brand-text">
          <div class="brand-name">{{ $companyName }}</div>
          <div class="brand-sub">شركة توزيع غاز</div>
        </div>
      </div>
    </div>
  </div>

  <div class="info-row">
    <div class="info-box">
      <span class="lbl">رقم السند</span>
      <span class="val">#{{ $payment->id }}</span>
    </div>
    <div class="info-box">
      <span class="lbl">تاريخ الدفع</span>
      <span class="val">{{ $payment->paid_at?->format('Y-m-d') ?? '—' }}</span>
    </div>
    <div class="info-box">
      <span class="lbl">تاريخ الطباعة</span>
      <span class="val">{{ now()->format('Y-m-d') }}</span>
    </div>
  </div>

  <div class="client-card">
    <div class="section-lbl">{{ $partyLabel }}</div>
    <div class="client-name">{{ $partyName }}</div>
    @if($party?->phone_primary)
    <div class="client-sub" dir="ltr">{{ $party->phone_primary }}</div>
    @endif
    @if($party?->email)
    <div class="client-sub" dir="ltr">{{ $party->email }}</div>
    @endif
  </div>

  <div class="amount-box">
    <div class="head">المبلغ</div>
    <div class="value">{{ number_format((float) $payment->amount, 2) }}</div>
    <div class="currency">{{ $payment->currency_code }}</div>
  </div>

  <div class="amount-words">{{ $amountInWords }}</div>

  <table class="details-table">
    <tr>
      <td class="lbl">طريقة الدفع</td>
      <td class="val">{{ $methodLabel }}</td>
    </tr>
    @if($payment->bank_reference)
    <tr>
      <td class="lbl">رقم المرجع / الشيك</td>
      <td class="val ltr">{{ $payment->bank_reference }}</td>
    </tr>
    @endif
    @if($payment->notes)
    <tr>
      <td class="lbl">ملاحظات</td>
      <td class="val">{{ $payment->notes }}</td>
    </tr>
    @endif
    @if($payment->recordedBy)
    <tr>
      <td class="lbl">سجّل بواسطة</td>
      <td class="val">{{ $payment->recordedBy->full_name ?? $payment->recordedBy->email }}</td>
    </tr>
    @endif
  </table>

  <div class="signatures">
    <div class="sig-box">
      <div class="sig-line">توقيع المستلم</div>
    </div>
    <div class="sig-box">
      <div class="sig-line">توقيع العميل</div>
    </div>
    <div class="sig-box">
      <div class="sig-line">الختم</div>
    </div>
  </div>

  <div class="footer">
    {{ $companyName }} — {{ $voucherTitle }} رسمي
  </div>

</div>

<x-print-page-actions :pdf-url="$pdfUrl" />

</body>
</html>
