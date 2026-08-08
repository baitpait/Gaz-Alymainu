<?php

namespace App\Services\Reports;

use App\Enums\SalePaymentType;
use App\Models\ClientPayment;
use App\Models\Collection;
use App\Models\DriverExpense;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\SalaryPayment;
use App\Models\Sale;
use App\Models\SupplierPayment;
use App\Services\Finance\BoiExchangeRateService;

/**
 * Business Purpose: Profit & loss per currency — accrual (invoices + driver sales) or cash (payments + cash sales + collections), no COGS.
 */
class ProfitLossReportService
{
    public const MODE_ACCRUAL = 'accrual';

    public const MODE_CASH = 'cash';

    public const DEFAULT_CURRENCY = 'ILS';

    /**
     * @return array<string, array{
     *   sales: float,
     *   purchases: float,
     *   expenses: float,
     *   salaries: float,
     *   net_profit: float,
     *   invoice_count: int,
     *   driver_sale_count: int,
     *   po_count: int,
     *   client_payment_count: int,
     *   collection_count: int,
     *   supplier_payment_count: int,
     *   salary_count: int
     * }>
     */
    public function byCurrency(ReportPeriodFilters $filters, string $mode): array
    {
        $from = $filters->resolvedDateFrom();
        $to = $filters->resolvedDateTo();

        $invoices = Invoice::query()
            ->where('status', 'issued')
            ->whereNull('deleted_at')
            ->whereDate('document_date', '>=', $from)
            ->whereDate('document_date', '<=', $to)
            ->when($filters->currency, fn ($q) => $q->where('currency_code', $filters->currency))
            ->get(['currency_code', 'total_amount']);

        $driverSales = Sale::query()
            ->whereNull('deleted_at')
            ->whereDate('sale_date', '>=', $from)
            ->whereDate('sale_date', '<=', $to)
            ->when($filters->currency, fn ($q) => $q->where('currency_code', $filters->currency))
            ->get(['currency_code', 'total_amount', 'payment_type']);

        $purchaseOrders = PurchaseOrder::query()
            ->where('status', 'issued')
            ->whereNull('deleted_at')
            ->whereDate('document_date', '>=', $from)
            ->whereDate('document_date', '<=', $to)
            ->when($filters->currency, fn ($q) => $q->where('currency_code', $filters->currency))
            ->get(['currency_code', 'total_amount']);

        $clientPayments = ClientPayment::query()
            ->whereNull('deleted_at')
            ->whereDate('paid_at', '>=', $from)
            ->whereDate('paid_at', '<=', $to)
            ->when($filters->currency, fn ($q) => $q->where('currency_code', $filters->currency))
            ->get(['currency_code', 'amount']);

        $collections = Collection::query()
            ->whereNull('deleted_at')
            ->whereDate('collection_date', '>=', $from)
            ->whereDate('collection_date', '<=', $to)
            ->when($filters->currency, fn ($q) => $q->where('currency_code', $filters->currency))
            ->get(['currency_code', 'amount']);

        $supplierPayments = SupplierPayment::query()
            ->whereNull('deleted_at')
            ->whereDate('paid_at', '>=', $from)
            ->whereDate('paid_at', '<=', $to)
            ->when($filters->currency, fn ($q) => $q->where('currency_code', $filters->currency))
            ->get(['currency_code', 'amount']);

        $expenses = Expense::query()
            ->whereNull('deleted_at')
            ->whereDate('expense_date', '>=', $from)
            ->whereDate('expense_date', '<=', $to)
            ->when($filters->currency, fn ($q) => $q->where('currency_code', $filters->currency))
            ->get(['currency_code', 'amount']);

        $driverExpenses = DriverExpense::query()
            ->whereNull('deleted_at')
            ->whereDate('expense_date', '>=', $from)
            ->whereDate('expense_date', '<=', $to)
            ->when($filters->currency, fn ($q) => $q->where('currency_code', $filters->currency))
            ->get(['currency_code', 'amount']);

        $salaries = SalaryPayment::query()
            ->where('status', SalaryPayment::STATUS_PAID)
            ->whereNull('deleted_at')
            ->whereDate('paid_at', '>=', $from)
            ->whereDate('paid_at', '<=', $to)
            ->when($filters->currency, fn ($q) => $q->where('currency_code', $filters->currency))
            ->get(['currency_code', 'net_amount']);

        $currencies = collect()
            ->merge($invoices->pluck('currency_code'))
            ->merge($driverSales->pluck('currency_code'))
            ->merge($purchaseOrders->pluck('currency_code'))
            ->merge($clientPayments->pluck('currency_code'))
            ->merge($collections->pluck('currency_code'))
            ->merge($supplierPayments->pluck('currency_code'))
            ->merge($expenses->pluck('currency_code'))
            ->merge($driverExpenses->pluck('currency_code'))
            ->merge($salaries->pluck('currency_code'))
            ->unique()
            ->sort()
            ->values();

        if ($filters->currency !== null) {
            $currencies = collect([$filters->currency]);
        }

        $result = [];

        foreach ($currencies as $cur) {
            $invoiceSales = (float) $invoices->where('currency_code', $cur)->sum('total_amount');
            $gasSalesAll = (float) $driverSales->where('currency_code', $cur)->sum('total_amount');
            $gasSalesCash = (float) $driverSales
                ->where('currency_code', $cur)
                ->filter(fn (Sale $sale) => $sale->payment_type === SalePaymentType::Cash)
                ->sum('total_amount');
            $purchases = (float) $purchaseOrders->where('currency_code', $cur)->sum('total_amount');
            $clientPaid = (float) $clientPayments->where('currency_code', $cur)->sum('amount');
            $collectionsPaid = (float) $collections->where('currency_code', $cur)->sum('amount');
            $supplierPaid = (float) $supplierPayments->where('currency_code', $cur)->sum('amount');
            $expenseTotal = (float) $expenses->where('currency_code', $cur)->sum('amount')
                + (float) $driverExpenses->where('currency_code', $cur)->sum('amount');
            $salaryTotal = (float) $salaries->where('currency_code', $cur)->sum('net_amount');

            if ($mode === self::MODE_CASH) {
                $revenue = $clientPaid + $collectionsPaid + $gasSalesCash;
                $costPurchases = $supplierPaid;
            } else {
                $revenue = $invoiceSales + $gasSalesAll;
                $costPurchases = $purchases;
            }

            $net = round($revenue - $costPurchases - $expenseTotal - $salaryTotal, 2);

            $result[$cur] = [
                'sales' => round($revenue, 2),
                'purchases' => round($costPurchases, 2),
                'expenses' => round($expenseTotal, 2),
                'salaries' => round($salaryTotal, 2),
                'net_profit' => $net,
                'invoice_count' => $invoices->where('currency_code', $cur)->count(),
                'driver_sale_count' => $driverSales->where('currency_code', $cur)->count(),
                'po_count' => $purchaseOrders->where('currency_code', $cur)->count(),
                'client_payment_count' => $clientPayments->where('currency_code', $cur)->count(),
                'collection_count' => $collections->where('currency_code', $cur)->count(),
                'supplier_payment_count' => $supplierPayments->where('currency_code', $cur)->count(),
                'salary_count' => $salaries->where('currency_code', $cur)->count(),
            ];
        }

        return $result;
    }

    /**
     * @return array{
     *   sales: float,
     *   purchases: float,
     *   expenses: float,
     *   salaries: float,
     *   net_profit: float,
     *   rates: array<string, float>,
     *   rate_date: string
     * }
     */
    public function consolidatedIls(ReportPeriodFilters $filters, string $mode, ?BoiExchangeRateService $fx = null): array
    {
        $fx ??= new BoiExchangeRateService;
        $rows = $this->byCurrency($filters, $mode);
        $rateDate = $filters->resolvedDateTo();

        $totals = [
            'sales' => 0.0,
            'purchases' => 0.0,
            'expenses' => 0.0,
            'salaries' => 0.0,
            'net_profit' => 0.0,
            'rates' => [],
            'rate_date' => $rateDate->format('Y-m-d'),
        ];

        foreach ($rows as $cur => $row) {
            $rate = $fx->getRateToIls($cur, $rateDate);
            $totals['rates'][$cur] = $rate;

            $totals['sales'] += $row['sales'] * $rate;
            $totals['purchases'] += $row['purchases'] * $rate;
            $totals['expenses'] += $row['expenses'] * $rate;
            $totals['salaries'] += $row['salaries'] * $rate;
        }

        $totals['sales'] = round($totals['sales'], 2);
        $totals['purchases'] = round($totals['purchases'], 2);
        $totals['expenses'] = round($totals['expenses'], 2);
        $totals['salaries'] = round($totals['salaries'], 2);
        $totals['net_profit'] = round($totals['sales'] - $totals['purchases'] - $totals['expenses'] - $totals['salaries'], 2);

        return $totals;
    }

    public static function modeLabel(string $mode): string
    {
        return match ($mode) {
            self::MODE_CASH => 'بدون دين (نقدي)',
            default => 'كامل (فواتير)',
        };
    }

    /** @return list<string> */
    public function currencyOptions(): array
    {
        return Product::billingCurrencies();
    }
}
