<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
@include('pdf.reports.partials.styles')
</head>
<body>

@include('pdf.reports.partials.header')

@if(!empty($totals))
<div class="summary-box">
    @foreach($totals as $cur => $total)
    <div class="summary-row">
        <span>صافي {{ $cur }} ({{ $total['count'] }})</span>
        <span class="positive">{{ number_format($total['net'], 2) }}</span>
    </div>
    @endforeach
</div>
@endif

@if($rows->isEmpty())
<p class="empty">لا توجد رواتب في هذه الفترة.</p>
@else
<table class="main-table">
    <thead>
        <tr>
            <th>الموظف</th>
            <th>القسم</th>
            <th class="ltr">الشهر</th>
            <th>أيام العمل</th>
            <th class="ltr">أساسي</th>
            <th class="ltr">صافي</th>
            <th class="ltr">عملة</th>
            <th>الحالة</th>
        </tr>
    </thead>
    <tbody>
        @foreach($rows as $row)
        <tr>
            <td>{{ $row['employee_name'] }}</td>
            <td>{{ $row['department'] ?? '—' }}</td>
            <td class="ltr">{{ $row['period_label'] }}</td>
            <td class="ltr">{{ $row['worked_days'] !== null ? $row['worked_days'].' × '.number_format($row['daily_rate'] ?? 0, 2) : '—' }}</td>
            <td class="ltr">{{ number_format($row['base_amount'], 2) }}</td>
            <td class="ltr positive">{{ number_format($row['net_amount'], 2) }}</td>
            <td class="ltr">{{ $row['currency'] }}</td>
            <td>{{ $row['status_label'] }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

</body>
</html>
