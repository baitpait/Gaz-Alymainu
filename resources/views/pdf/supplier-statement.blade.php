<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: xbriyaz, sans-serif;
    color: #1E293B;
    font-size: 11pt;
    direction: rtl;
    unicode-bidi: bidi-override;
}

.header {
    width: 100%;
    border-bottom: 2px solid #1B6CA8;
    padding-bottom: 10pt;
    margin-bottom: 16pt;
    overflow: hidden;
}
.header-brand {
    font-size: 18pt;
    font-weight: bold;
    color: #1E293B;
    float: right;
}
.header-brand small {
    font-size: 9pt;
    color: #1B6CA8;
    display: block;
    margin-top: 2pt;
}
.header-meta {
    float: left;
    text-align: left;
    font-size: 9pt;
    color: #666;
    direction: ltr;
}

.statement-title { font-size: 16pt; font-weight: bold; margin-bottom: 4pt; }
.supplier-name   { font-size: 13pt; color: #1B6CA8; margin-bottom: 14pt; }
.date-range      { font-size: 9pt; color: #666; margin-bottom: 14pt; }

.currency-section  { margin-bottom: 18pt; }
.currency-header   {
    background: #F5F5F5;
    border: 1px solid #E0E0E0;
    padding: 6pt 10pt;
    font-size: 12pt;
    font-weight: bold;
}
.currency-code { color: #1B6CA8; direction: ltr; }

table { width: 100%; border-collapse: collapse; font-size: 9.5pt; margin-top: 6pt; }
th {
    background: #F5F5F5;
    text-align: right;
    padding: 5pt 8pt;
    border: 1px solid #E0E0E0;
    font-weight: bold;
}
td { padding: 5pt 8pt; border: 1px solid #E0E0E0; }
.ltr { direction: ltr; text-align: left; }
tfoot td { background: #F5F5F5; font-weight: bold; }

.balance-box {
    margin-top: 8pt;
    border: 1px solid #E0E0E0;
    padding: 8pt 12pt;
    width: 260pt;
    float: left;
}
.balance-row {
    overflow: hidden;
    font-size: 9.5pt;
    margin-bottom: 3pt;
}
.balance-label  { float: right; }
.balance-amount { float: left; direction: ltr; }
.balance-total  {
    border-top: 1px solid #E0E0E0;
    padding-top: 5pt;
    margin-top: 4pt;
    font-weight: bold;
    font-size: 11pt;
}
.balance-owed  { color: #DC2626; }
.balance-clear { color: #16A34A; }

.footer {
    border-top: 1px solid #E0E0E0;
    padding-top: 8pt;
    margin-top: 20pt;
    font-size: 8pt;
    color: #999;
}
</style>
</head>
<body>

<div class="header">
    <div class="header-brand">
        {{ config('app.name', 'غاز اليمني') }}
        <small>توزيع الغاز</small>
    </div>
    <div class="header-meta">
        تاريخ الإصدار: {{ now()->format('Y-m-d') }}<br>
        الوقت (UTC): {{ now()->utc()->format('H:i') }}
    </div>
    <div style="clear:both;"></div>
</div>

<div class="statement-title">كشف حساب مورد</div>
<div class="supplier-name">{{ $supplier->displayName() }}</div>

@if($dateFrom || $dateTo)
<div class="date-range">
    الفترة:
    @if($dateFrom) من {{ $dateFrom }} @endif
    @if($dateTo) إلى {{ $dateTo }} @endif
</div>
@endif

@if(empty($statement))
    <p style="color:#999; text-align:center; margin-top:40pt;">لا توجد حركات في هذه الفترة.</p>
@endif

@foreach($statement as $currency => $section)
<div class="currency-section">
    <div class="currency-header">
        عملة: <span class="currency-code">{{ $currency }}</span>
    </div>

    @if($section['purchase_orders']->count() > 0)
    <table>
        <thead>
            <tr>
                <th>التاريخ</th>
                <th>رقم أمر الشراء</th>
                <th>الحالة</th>
                <th class="ltr">المبلغ ({{ $currency }})</th>
            </tr>
        </thead>
        <tbody>
            @foreach($section['purchase_orders'] as $po)
            <tr>
                <td class="ltr">{{ $po->document_date->format('Y-m-d') }}</td>
                <td>{{ $po->legacy_po_no ?? '#'.$po->id }}</td>
                <td>
                    @if($po->status === 'issued') صادر
                    @elseif($po->status === 'draft') مسودة
                    @else ملغى
                    @endif
                </td>
                <td class="ltr">{{ number_format((float)$po->total_amount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3">مجموع أوامر الشراء</td>
                <td class="ltr">{{ number_format($section['total_ordered'], 2) }}</td>
            </tr>
        </tfoot>
    </table>
    @endif

    @if($section['payments']->count() > 0)
    <table style="margin-top:6pt;">
        <thead>
            <tr>
                <th>التاريخ</th>
                <th>طريقة الدفع</th>
                <th>المرجع</th>
                <th class="ltr">المبلغ ({{ $currency }})</th>
            </tr>
        </thead>
        <tbody>
            @foreach($section['payments'] as $pay)
            <tr>
                <td class="ltr">{{ $pay->paid_at->format('Y-m-d') }}</td>
                <td>{{ $pay->method ?? '—' }}</td>
                <td>{{ $pay->bank_reference ?? '—' }}</td>
                <td class="ltr">{{ number_format((float)$pay->amount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3">مجموع الدفعات</td>
                <td class="ltr">{{ number_format($section['total_paid'], 2) }}</td>
            </tr>
        </tfoot>
    </table>
    @endif

    <div class="balance-box">
        <div class="balance-row">
            <span class="balance-label">إجمالي أوامر الشراء</span>
            <span class="balance-amount">{{ number_format($section['total_ordered'], 2) }} {{ $currency }}</span>
        </div>
        <div class="balance-row">
            <span class="balance-label">إجمالي الدفعات</span>
            <span class="balance-amount" style="color:#16A34A;">{{ number_format($section['total_paid'], 2) }} {{ $currency }}</span>
        </div>
        <div class="balance-row">
            <span class="balance-label">إجمالي التسويات</span>
            <span class="balance-amount" style="color:#7C3AED;">{{ number_format($section['total_adjusted'], 2) }} {{ $currency }}</span>
        </div>
        <div class="balance-row balance-total">
            <span class="balance-label">المتبقي للمورد</span>
            <span class="balance-amount {{ $section['balance'] > 0 ? 'balance-owed' : 'balance-clear' }}">
                {{ number_format($section['balance'], 2) }} {{ $currency }}
            </span>
        </div>
    </div>
    <div style="clear:both;"></div>

</div>
@endforeach

<div class="footer">
    <p>وثيقة داخلية — {{ config('app.name', 'غاز اليمني') }} · توزيع الغاز &bull; صدر بتاريخ {{ \App\Support\AppDateTime::format(now()) }}</p>
</div>

</body>
</html>
