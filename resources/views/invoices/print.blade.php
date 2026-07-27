<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<title>فاتورة {{ $invoice->legacy_invoice_no ?? '#'.$invoice->id }}</title>
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
    min-height: 1123px;
    margin: 20px auto;
    padding: 40px 45px;
    box-shadow: 0 2px 20px rgba(0,0,0,.12);
    position: relative;
    display: flex;
    flex-direction: column;
  }

  .page-print-date {
    margin-top: auto;
    padding-top: 16px;
    text-align: center;
    font-size: 10px;
    color: #999;
    direction: ltr;
  }

  /* ── Header: فاتورة يسار | شعار وسط | بيانات الشركة يمين ── */
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

  .header-side-invoice {
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

  .invoice-title {
    font-size: 42px;
    font-weight: 900;
    color: #C9A227;
    letter-spacing: -1px;
    line-height: 1;
    text-align: left;
  }

  /* ── Info row ── */
  .info-row {
    display: flex;
    gap: 12px;
    margin-bottom: 20px;
  }

  .info-box {
    flex: 1;
    border: 1px solid #E2E4E9;
    border-radius: 8px;
    padding: 10px 16px;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .info-box .lbl { font-size: 11px; color: #888; }
  .info-box .val { font-size: 13px; font-weight: 700; color: #3D3D3D; direction: ltr; }

  /* ── Client + Summary row ── */
  .client-row {
    display: flex;
    gap: 12px;
    margin-bottom: 24px;
  }

  .client-card {
    flex: 1;
    background: #FAFAFA;
    border: 1px solid #E2E4E9;
    border-radius: 8px;
    padding: 14px 16px;
  }

  .client-card .section-lbl { font-size: 11px; color: #C9A227; font-weight: 700; margin-bottom: 6px; text-transform: uppercase; letter-spacing: .5px; }
  .client-card .client-name { font-size: 16px; font-weight: 800; color: #3D3D3D; margin-bottom: 4px; }
  .client-card .client-sub  { font-size: 12px; color: #666; line-height: 1.6; }

  .summary-card {
    width: 220px;
    border: 2px solid #C9A227;
    border-radius: 8px;
    overflow: hidden;
  }

  .summary-card .sum-header {
    background: #C9A227;
    color: #fff;
    font-size: 11px;
    font-weight: 700;
    padding: 6px 14px;
    text-align: center;
  }

  .summary-card .sum-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 14px;
    border-bottom: 1px solid #F0EDE3;
  }

  .summary-card .sum-row:last-child { border-bottom: none; }
  .summary-card .sum-lbl { font-size: 11px; color: #666; }
  .summary-card .sum-val { font-size: 13px; font-weight: 700; direction: ltr; }
  .summary-card .sum-val.credit { color: #16a34a; }
  .summary-card .sum-val.debit  { color: #dc2626; }

  /* ── Lines table ── */
  table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
    font-size: 12.5px;
  }

  thead tr {
    background: #3D3D3D;
    color: #fff;
  }

  thead th {
    padding: 10px 12px;
    text-align: right;
    font-weight: 600;
    font-size: 12px;
  }

  thead th:last-child { text-align: left; }

  tbody tr:nth-child(even) { background: #FAFAFA; }
  tbody tr:hover { background: #FFF9EC; }

  tbody td {
    padding: 9px 12px;
    border-bottom: 1px solid #E2E4E9;
    vertical-align: top;
  }

  tbody td.num { text-align: left; direction: ltr; font-weight: 600; }
  tbody td.center { text-align: center; }

  .line-title { font-weight: 600; color: #3D3D3D; }
  .line-desc  { font-size: 11px; color: #666; }

  /* ── Totals ── */
  .totals-section {
    display: flex;
    justify-content: flex-start;
    margin-bottom: 24px;
  }

  .totals-table {
    width: 280px;
    margin-right: auto;
  }

  .totals-table td {
    padding: 7px 12px;
    font-size: 13px;
    border-bottom: 1px solid #F0F0F0;
  }

  .totals-table td:last-child { text-align: left; direction: ltr; font-weight: 600; }
  .totals-table .grand td { font-weight: 800; font-size: 15px; color: #C9A227; border-top: 2px solid #C9A227; border-bottom: 2px solid #C9A227; }
  .totals-table .balance-due-row td { color: #dc2626; font-weight: 800; font-size: 14px; }

  /* ── Amount words ── */
  .amount-words {
    background: #FFFBF0;
    border: 1px solid #F0E6B8;
    border-radius: 6px;
    padding: 10px 16px;
    font-size: 13px;
    font-weight: 600;
    color: #7A6200;
    margin-bottom: 24px;
    text-align: center;
  }

  /* ── Footer ── */
  .footer {
    border-top: 1px solid #E2E4E9;
    padding-top: 14px;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    font-size: 11px;
    color: #aaa;
  }

  .footer-main {
    direction: rtl;
    text-align: right;
    line-height: 1.65;
  }

  .footer-thanks {
    color: #888;
    font-size: 11px;
  }

  .footer-contact-block {
    direction: rtl;
    text-align: left;
    line-height: 1.65;
  }

  .footer-contact {
    color: #666;
    font-size: 11px;
  }

  .footer-phone {
    margin-top: 4px;
    color: #C9A227;
    font-weight: 700;
    font-size: 13px;
    direction: ltr;
    text-align: left;
  }

  /* ── Print media ── */
  @media print {
    body { background: #fff; }
    .page { margin: 0; padding: 28px 32px; box-shadow: none; width: 100%; }
    .print-actions { display: none; }
    @page { margin: 0; size: A4; }
  }
</style>
</head>
<body>

<div class="page">

  {{-- ── Header ── --}}
  <div class="header">
    <div class="header-row">
      <div class="header-side-invoice">
        <div class="invoice-title">فاتورة</div>
      </div>
      <div class="header-logo">
        <img src="{{ asset('branding/logo.png') }}" alt="Logo">
      </div>
      <div class="header-side-brand">
        <div class="brand-text">
          <div class="brand-name">Gaz Alymainu</div>
          <div class="brand-sub">شركة توزيع غاز</div>
        </div>
      </div>
    </div>
  </div>

  {{-- ── Info boxes ── --}}
  <div class="info-row">
    <div class="info-box">
      <span class="lbl">رقم الفاتورة</span>
      <span class="val">{{ $invoice->legacy_invoice_no ?? '#'.$invoice->id }}</span>
    </div>
    <div class="info-box">
      <span class="lbl">تاريخ الفاتورة</span>
      <span class="val">{{ $invoice->document_date?->format('Y-m-d') ?? '—' }}</span>
    </div>
    @if($invoice->due_date)
    <div class="info-box">
      <span class="lbl">تاريخ الاستحقاق</span>
      <span class="val">{{ $invoice->due_date->format('Y-m-d') }}</span>
    </div>
    @endif
  </div>

  {{-- ── Client + Summary ── --}}
  <div class="client-row">
    <div class="client-card">
      <div class="section-lbl">بيانات العميل</div>
      <div class="client-name">{{ $client?->displayName() ?? '—' }}</div>
      @if($client?->phone_primary)
      <div class="client-sub">📞 {{ $client->phone_primary }}</div>
      @endif
      @if($client?->email)
      <div class="client-sub" dir="ltr">{{ $client->email }}</div>
      @endif
      @if($client?->city)
      <div class="client-sub">{{ $client->city }}{{ $client->country_code ? ' — '.$client->country_code : '' }}</div>
      @endif
    </div>

    <div class="summary-card">
      <div class="sum-header">مجموع الفاتورة</div>
      <div class="sum-row">
        <span class="sum-lbl" style="font-weight:700;color:#3D3D3D">الإجمالي</span>
        <span class="sum-val">{{ number_format((float)$invoice->total_amount, 2) }} {{ $invoice->currency_code }}</span>
      </div>
    </div>
  </div>

  {{-- ── Line items ── --}}
  @if($invoice->lines->isNotEmpty())
  <table>
    <thead>
      <tr>
        <th style="width:36px">#</th>
        <th>البند</th>
        <th>الوصف</th>
        <th style="width:100px">سعر الوحدة</th>
        <th style="width:70px; text-align:center">الكمية</th>
        <th style="width:110px">المجموع</th>
      </tr>
    </thead>
    <tbody>
      @foreach($invoice->lines as $line)
      <tr>
        <td class="center" style="color:#aaa">{{ $loop->iteration }}</td>
        <td class="line-title">{{ $line->title }}</td>
        <td class="line-desc">{{ $line->description ?: '—' }}</td>
        <td class="num">{{ number_format((float)$line->unit_price, 2) }}</td>
        <td class="center">{{ rtrim(rtrim(number_format((float)$line->quantity, 2), '0'), '.') }}</td>
        <td class="num">{{ number_format((float)$line->line_total, 2) }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>
  @endif

  {{-- ── Totals ── --}}
  <div class="totals-section">
    <table class="totals-table">
      <tr class="grand">
        <td>مجموع الفاتورة</td>
        <td>{{ number_format((float) $invoice->total_amount, 2) }} {{ $invoice->currency_code }}</td>
      </tr>
      @if($clientBalanceDue !== null)
      <tr class="balance-due-row">
        <td>المبلغ المستحق</td>
        <td>{{ number_format((float) $clientBalanceDue, 2) }} {{ $invoice->currency_code }}</td>
      </tr>
      @endif
    </table>
  </div>

  {{-- ── Amount in Arabic words ── --}}
  <div class="amount-words">{{ $amountInWords }}</div>

  @if($invoice->notes)
  <div style="margin-bottom:20px; padding:12px 16px; background:#FAFAFA; border:1px solid #E2E4E9; border-radius:6px; font-size:12px; color:#555;">
    <span style="font-weight:700; color:#C9A227;">ملاحظات: </span>{{ $invoice->notes }}
  </div>
  @endif

  {{-- ── Footer ── --}}
  <div class="footer">
    <div class="footer-main">
      <div class="footer-thanks">Gaz Alymainu — شكراً لثقتكم</div>
    </div>
    <div class="footer-contact-block">
      <div class="footer-contact">لاستفساراتكم تواصل معنا على الرقم</div>
      <div class="footer-phone">0569224006</div>
    </div>
  </div>

  <div class="page-print-date">تاريخ الطباعة: {{ now()->format('Y-m-d') }}</div>

</div>

<x-print-page-actions :pdf-url="$pdfUrl" />

<script>
  window.addEventListener('load', function () {
    // small delay so the logo can load before print
    setTimeout(function () {
      // only auto-print if ?auto=1 is in URL
      if (new URLSearchParams(location.search).get('auto') === '1') {
        window.print();
      }
    }, 400);
  });
</script>

</body>
</html>
