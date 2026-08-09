<?php

namespace App\Services\Reports;

use App\Enums\SalePaymentType;
use App\Models\ClientPayment;
use App\Models\Collection;
use App\Models\DriverExpense;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\ProductCurrencyPrice;
use App\Models\PurchaseOrder;
use App\Models\SalaryPayment;
use App\Models\Sale;
use App\Models\SupplierPayment;
use App\Services\Finance\BoiExchangeRateService;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;

/**
 * Business Purpose: P&L by currency — revenue minus COGS (sold qty × product cost), expenses, salaries.
 * Remaining inventory is an asset outside the P&L; valuation purchase orders are excluded from profit.
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
     *   cogs: float,
     *   operational_purchases: float,
     *   expenses: float,
     *   salaries: float,
     *   net_profit: float,
     *   inventory_asset: float,
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
            ->get(['id', 'product_id', 'quantity', 'currency_code', 'total_amount', 'payment_type']);

        $operationalPurchaseOrders = PurchaseOrder::query()
            ->where('status', 'issued')
            ->whereNull('deleted_at')
            ->where('is_inventory_valuation', false)
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

        $costMap = $this->costMapForSales($driverSales);
        $inventoryByCurrency = $this->inventoryAssetByCurrency($filters->currency);

        $currencies = collect()
            ->merge($invoices->pluck('currency_code'))
            ->merge($driverSales->pluck('currency_code'))
            ->merge($operationalPurchaseOrders->pluck('currency_code'))
            ->merge($clientPayments->pluck('currency_code'))
            ->merge($collections->pluck('currency_code'))
            ->merge($supplierPayments->pluck('currency_code'))
            ->merge($expenses->pluck('currency_code'))
            ->merge($driverExpenses->pluck('currency_code'))
            ->merge($salaries->pluck('currency_code'))
            ->merge(array_keys($inventoryByCurrency))
            ->unique()
            ->sort()
            ->values();

        if ($filters->currency !== null) {
            $currencies = collect([$filters->currency]);
        }

        $result = [];

        foreach ($currencies as $cur) {
            $invoiceSales = (float) $invoices->where('currency_code', $cur)->sum('total_amount');
            $salesInCurrency = $driverSales->where('currency_code', $cur);
            $gasSalesAll = (float) $salesInCurrency->sum('total_amount');
            $gasSalesCash = (float) $salesInCurrency
                ->filter(fn (Sale $sale) => $sale->payment_type === SalePaymentType::Cash)
                ->sum('total_amount');

            $cogsAll = $this->cogsForSales($salesInCurrency, $costMap);
            $cogsCash = $this->cogsForSales(
                $salesInCurrency->filter(fn (Sale $sale) => $sale->payment_type === SalePaymentType::Cash),
                $costMap,
            );

            $operationalPurchases = (float) $operationalPurchaseOrders->where('currency_code', $cur)->sum('total_amount');
            $clientPaid = (float) $clientPayments->where('currency_code', $cur)->sum('amount');
            $collectionsPaid = (float) $collections->where('currency_code', $cur)->sum('amount');
            $expenseTotal = (float) $expenses->where('currency_code', $cur)->sum('amount')
                + (float) $driverExpenses->where('currency_code', $cur)->sum('amount');
            $salaryTotal = (float) $salaries->where('currency_code', $cur)->sum('net_amount');

            if ($mode === self::MODE_CASH) {
                $revenue = $clientPaid + $collectionsPaid + $gasSalesCash;
                $cogs = $cogsCash;
            } else {
                $revenue = $invoiceSales + $gasSalesAll;
                $cogs = $cogsAll;
            }

            $net = round($revenue - $cogs - $expenseTotal - $salaryTotal, 2);
            $inventoryAsset = round((float) ($inventoryByCurrency[$cur] ?? 0), 2);

            $result[$cur] = [
                'sales' => round($revenue, 2),
                // purchases key kept for PDF/CSV compatibility = COGS in the net equation
                'purchases' => round($cogs, 2),
                'cogs' => round($cogs, 2),
                'operational_purchases' => round($operationalPurchases, 2),
                'expenses' => round($expenseTotal, 2),
                'salaries' => round($salaryTotal, 2),
                'net_profit' => $net,
                'inventory_asset' => $inventoryAsset,
                'invoice_count' => $invoices->where('currency_code', $cur)->count(),
                'driver_sale_count' => $salesInCurrency->count(),
                'po_count' => $operationalPurchaseOrders->where('currency_code', $cur)->count(),
                'client_payment_count' => $clientPayments->where('currency_code', $cur)->count(),
                'collection_count' => $collections->where('currency_code', $cur)->count(),
                'supplier_payment_count' => $supplierPayments->where('currency_code', $cur)->count(),
                'salary_count' => $salaries->where('currency_code', $cur)->count(),
            ];
        }

        return $result;
    }

    /**
     * قيمة أصول المخزون الحالية (كل المخازن) بتكلفة الصنف — خارج معادلة الربح.
     *
     * @return array{total: float, by_currency: array<string, float>}
     */
    public function inventoryAssetSummary(?string $currency = null): array
    {
        $byCurrency = $this->inventoryAssetByCurrency($currency);
        $total = round(array_sum($byCurrency), 2);

        return [
            'total' => $total,
            'by_currency' => $byCurrency,
        ];
    }

    /**
     * @return array{
     *   sales: float,
     *   purchases: float,
     *   expenses: float,
     *   salaries: float,
     *   net_profit: float,
     *   inventory_asset: float,
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
            'inventory_asset' => 0.0,
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
            $totals['inventory_asset'] += $row['inventory_asset'] * $rate;
        }

        // Inventory may exist for currencies with no P&L rows in the period
        if ($totals['inventory_asset'] == 0.0) {
            $asset = $this->inventoryAssetSummary($filters->currency);
            foreach ($asset['by_currency'] as $cur => $amount) {
                $rate = $fx->getRateToIls($cur, $rateDate);
                $totals['rates'][$cur] = $rate;
                $totals['inventory_asset'] += $amount * $rate;
            }
        }

        $totals['sales'] = round($totals['sales'], 2);
        $totals['purchases'] = round($totals['purchases'], 2);
        $totals['expenses'] = round($totals['expenses'], 2);
        $totals['salaries'] = round($totals['salaries'], 2);
        $totals['inventory_asset'] = round($totals['inventory_asset'], 2);
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

    /**
     * @param  SupportCollection<int, Sale>  $sales
     * @return array<int, float> product_id => unit cost
     */
    private function costMapForSales(SupportCollection $sales): array
    {
        $productIds = $sales->pluck('product_id')->unique()->filter()->values()->all();
        if ($productIds === []) {
            return [];
        }

        return ProductCurrencyPrice::query()
            ->whereIn('product_id', $productIds)
            ->where('currency_code', self::DEFAULT_CURRENCY)
            ->pluck('service_cost_price', 'product_id')
            ->map(fn ($v) => (float) $v)
            ->all();
    }

    /**
     * @param  SupportCollection<int, Sale>  $sales
     * @param  array<int, float>  $costMap
     */
    private function cogsForSales(SupportCollection $sales, array $costMap): float
    {
        $total = 0.0;
        foreach ($sales as $sale) {
            $unitCost = (float) ($costMap[(int) $sale->product_id] ?? 0);
            $total += (float) $sale->quantity * $unitCost;
        }

        return round($total, 2);
    }

    /**
     * @return array<string, float>
     */
    private function inventoryAssetByCurrency(?string $currencyFilter): array
    {
        $value = (float) DB::table('stock_balances as sb')
            ->join('warehouses as w', 'w.id', '=', 'sb.warehouse_id')
            ->join('products as p', 'p.id', '=', 'sb.product_id')
            ->leftJoin('product_currency_prices as c', function ($join) {
                $join->on('c.product_id', '=', 'p.id')
                    ->where('c.currency_code', '=', self::DEFAULT_CURRENCY);
            })
            ->whereNull('w.deleted_at')
            ->whereNull('p.deleted_at')
            ->where('sb.quantity', '>', 0)
            ->selectRaw('COALESCE(SUM(sb.quantity * COALESCE(c.service_cost_price, 0)), 0) as asset_value')
            ->value('asset_value');

        $ils = round($value, 2);
        if ($currencyFilter !== null && strtoupper($currencyFilter) !== self::DEFAULT_CURRENCY) {
            return [strtoupper($currencyFilter) => 0.0];
        }

        if ($currencyFilter !== null) {
            return [self::DEFAULT_CURRENCY => $ils];
        }

        return $ils > 0 ? [self::DEFAULT_CURRENCY => $ils] : [];
    }
}
