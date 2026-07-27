<?php

namespace App\Services\Hr;

use App\Models\Product;
use App\Models\SalaryPayment;
use App\Services\Reports\PaymentMethodLabels;
use App\Services\Reports\ReportPeriodFilters;
use Illuminate\Support\Collection;

/**
 * Business Purpose: Payroll rows and totals for a period — independent from expenses.
 */
class SalaryPeriodReportService
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function rows(ReportPeriodFilters $filters, ?string $status = null): Collection
    {
        $query = SalaryPayment::query()
            ->with('employee')
            ->whereNull('deleted_at')
            ->where('period_year', '>=', (int) $filters->resolvedDateFrom()->format('Y'))
            ->where('period_year', '<=', (int) $filters->resolvedDateTo()->format('Y'));

        if ($filters->currency !== null) {
            $query->where('currency_code', $filters->currency);
        }

        if ($status !== null && $status !== '') {
            $query->where('status', $status);
        }

        return $query->orderByDesc('period_year')
            ->orderByDesc('period_month')
            ->orderBy('employee_id')
            ->get()
            ->filter(function (SalaryPayment $pay) use ($filters): bool {
                $periodStart = sprintf('%04d-%02d-01', $pay->period_year, $pay->period_month);

                return $periodStart >= $filters->resolvedDateFrom()->format('Y-m-d')
                    && $periodStart <= $filters->resolvedDateTo()->format('Y-m-d');
            })
            ->map(fn (SalaryPayment $pay) => [
                'id' => $pay->id,
                'employee_id' => $pay->employee_id,
                'employee_name' => $pay->employee?->displayName() ?? '—',
                'department' => $pay->employee?->department,
                'period_year' => $pay->period_year,
                'period_month' => $pay->period_month,
                'period_label' => $pay->periodLabel(),
                'base_amount' => (float) $pay->base_amount,
                'worked_days' => $pay->worked_days,
                'daily_rate' => $pay->daily_rate !== null ? (float) $pay->daily_rate : null,
                'bonus_amount' => (float) $pay->bonus_amount,
                'deduction_amount' => (float) $pay->deduction_amount,
                'net_amount' => (float) $pay->net_amount,
                'currency' => $pay->currency_code,
                'paid_at' => $pay->paid_at,
                'method' => $pay->method,
                'method_label' => PaymentMethodLabels::label($pay->method),
                'status' => $pay->status,
                'status_label' => SalaryPayment::statusLabel($pay->status),
            ])
            ->values();
    }

    /** @return array<string, array{base: float, bonus: float, deduction: float, net: float, count: int}> */
    public function totalsByCurrency(ReportPeriodFilters $filters, ?string $status = SalaryPayment::STATUS_PAID): array
    {
        $totals = [];

        foreach ($this->rows($filters, $status) as $row) {
            $cur = $row['currency'];
            if (! isset($totals[$cur])) {
                $totals[$cur] = ['base' => 0.0, 'bonus' => 0.0, 'deduction' => 0.0, 'net' => 0.0, 'count' => 0];
            }
            $totals[$cur]['base'] += $row['base_amount'];
            $totals[$cur]['bonus'] += $row['bonus_amount'];
            $totals[$cur]['deduction'] += $row['deduction_amount'];
            $totals[$cur]['net'] += $row['net_amount'];
            $totals[$cur]['count']++;
        }

        ksort($totals);

        return $totals;
    }

    /** @return list<string> */
    public function currencyOptions(): array
    {
        return Product::billingCurrencies();
    }
}
