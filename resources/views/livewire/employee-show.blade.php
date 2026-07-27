<div>
<div class="flex flex-wrap items-start justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-[#3D3D3D]">{{ $employee->displayName() }}</h1>
        <p class="text-sm text-gray-500 mt-1">{{ $employee->job_title ?? '—' }} · {{ $employee->department ?? '—' }}</p>
    </div>
    <div class="flex gap-2">
        @can('update', $employee)
        <a href="{{ route('employees.edit', $employee) }}" wire:navigate class="btn btn-secondary" style="text-decoration:none;">تعديل</a>
        @endcan
        @can('create', App\Models\SalaryPayment::class)
        <a href="{{ route('salary-payments.create', ['employee_id' => $employee->id]) }}" wire:navigate class="btn btn-primary" style="text-decoration:none;">تسجيل راتب</a>
        @endcan
    </div>
</div>

<div class="grid lg:grid-cols-4 gap-4 mb-6">
    <div class="card p-4">
        <p class="text-xs text-gray-400 mb-1">الراتب الأساسي</p>
        <p class="text-lg font-bold font-mono" dir="ltr">{{ number_format((float)$employee->base_salary_amount, 2) }} {{ $employee->base_salary_currency }}</p>
    </div>
    <div class="card p-4">
        <p class="text-xs text-gray-400 mb-1">نوع التوظيف</p>
        <p class="font-semibold text-[#3D3D3D]">{{ App\Models\Employee::employmentTypeLabel($employee->pay_frequency) }}</p>
    </div>
    <div class="card p-4">
        <p class="text-xs text-gray-400 mb-1">الحالة</p>
        <p class="font-semibold {{ $employee->is_active ? 'text-green-600' : 'text-gray-400' }}">{{ $employee->is_active ? 'نشط' : 'غير نشط' }}</p>
    </div>
    <div class="card p-4">
        <p class="text-xs text-gray-400 mb-1">التواصل</p>
        <p class="text-sm" dir="ltr">{{ $employee->phone_primary ?? '—' }}</p>
        @if($employee->email)<p class="text-xs text-gray-500" dir="ltr">{{ $employee->email }}</p>@endif
    </div>
</div>

<div class="card overflow-hidden">
    <div class="px-4 py-3 border-b border-[#E2E4E9] font-bold text-sm">سجل الرواتب</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>الشهر</th>
                <th>أيام العمل</th>
                <th>أساسي</th>
                <th>صافي</th>
                <th>الحالة</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($employee->salaryPayments as $pay)
            <tr>
                <td dir="ltr">{{ $pay->periodLabel() }}</td>
                <td class="text-sm text-center">
                    @if($pay->worked_days !== null)
                        {{ $pay->worked_days }} <span class="text-gray-400 text-xs" dir="ltr">× {{ number_format((float) $pay->daily_rate, 2) }}</span>
                    @else
                        <span class="text-gray-300">—</span>
                    @endif
                </td>
                <td class="font-mono text-sm" dir="ltr">{{ number_format((float)$pay->base_amount, 2) }} {{ $pay->currency_code }}</td>
                <td class="font-mono font-semibold text-sm text-[#C9A227]" dir="ltr">{{ number_format((float)$pay->net_amount, 2) }}</td>
                <td>{{ App\Models\SalaryPayment::statusLabel($pay->status) }}</td>
                <td>
                    @can('update', $pay)
                    <a href="{{ route('salary-payments.edit', $pay) }}" wire:navigate class="text-xs text-blue-600">تعديل</a>
                    @endcan
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center py-8 text-gray-400">لا توجد رواتب مسجّلة</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
</div>
