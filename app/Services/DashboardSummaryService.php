<?php

namespace App\Services;

use App\Enums\CollectionMethod;
use App\Enums\SalePaymentType;
use App\Enums\StockMovementType;
use App\Enums\WarehouseType;
use App\Models\ClientPayment;
use App\Models\Collection;
use App\Models\DriverExpense;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\ProductDailyPrice;
use App\Models\PurchaseOrder;
use App\Models\Sale;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\SupplierPayment;
use App\Models\User;
use App\Services\Reports\ProfitLossReportService;
use App\Services\Reports\ReportPeriodFilters;
use App\Support\AppDateTime;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Business Purpose: Build the executive dashboard snapshot for managers/accountants —
 * today's gas ops KPIs, inventory health, cash held by drivers, and brief receivables —
 * without turning the dashboard into a full report page.
 */
class DashboardSummaryService
{
    public function __construct(
        private readonly CashBoxService $cashBoxes,
        private readonly DriverLocationService $locations,
        private readonly DailyPriceService $prices,
        private readonly ProfitLossReportService $profitLoss,
    ) {}

    /**
     * Business Purpose: Aggregate all dashboard sections for a given calendar day
     * (Palestine display timezone). Failures in optional subsections are logged
     * and replaced with zeros so login never 500s.
     *
     * @return array<string, mixed>
     */
    public function forDate(?string $date = null): array
    {
        try {
            $day = $date ?? $this->prices->today();
            [$dayStart, $dayEnd] = AppDateTime::utcDayBounds($day);

            return [
                'date' => $day,
                'today' => $this->todayOps($day),
                'fleet' => $this->fleetAndStock($day, $dayStart, $dayEnd),
                'finance' => $this->financeBrief(),
            ];
        } catch (Throwable $e) {
            Log::error('DashboardSummaryService failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return $this->emptySnapshot($date ?? AppDateTime::today());
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function emptySnapshot(string $day): array
    {
        return [
            'date' => $day,
            'today' => [
                'sales_count' => 0,
                'sales_qty' => 0.0,
                'sales_cash' => 0.0,
                'sales_credit' => 0.0,
                'sales_total' => 0.0,
                'collections_cash' => 0.0,
                'collections_cheque' => 0.0,
                'collections_total' => 0.0,
                'driver_expenses' => 0.0,
                'drivers_cash_held' => 0.0,
                'drivers_cheque_held' => 0.0,
                'cogs' => 0.0,
                'expenses_total' => 0.0,
                'salaries' => 0.0,
                'profit' => 0.0,
                'pnl_sales' => 0.0,
            ],
            'fleet' => [
                'zero_vehicle_stock' => 0,
                'low_vehicle_stock' => 0,
                'loads_today' => 0,
                'returns_today' => 0,
                'tracked_products' => 0,
                'priced_today' => 0,
                'pricing_complete' => true,
                'sharing_drivers' => 0,
                'active_drivers' => 0,
            ],
            'finance' => [
                'currency' => DailyPriceService::DEFAULT_CURRENCY,
                'client_receivable' => 0.0,
                'supplier_payable' => 0.0,
                'main_cash' => 0.0,
                'main_cheque' => 0.0,
                'draft_invoices' => 0,
                'draft_purchase_orders' => 0,
            ],
        ];
    }

    /**
     * @return array<string, float|int>
     */
    private function todayOps(string $day): array
    {
        $cashSales = (float) Sale::query()
            ->whereDate('sale_date', $day)
            ->where('payment_type', SalePaymentType::Cash->value)
            ->sum('total_amount');
        $creditSales = (float) Sale::query()
            ->whereDate('sale_date', $day)
            ->where('payment_type', SalePaymentType::Credit->value)
            ->sum('total_amount');
        $salesQty = (float) Sale::query()->whereDate('sale_date', $day)->sum('quantity');
        $salesCount = (int) Sale::query()->whereDate('sale_date', $day)->count();

        $collectionsCash = (float) Collection::query()
            ->whereDate('collection_date', $day)
            ->where('method', CollectionMethod::Cash->value)
            ->sum('amount');
        $collectionsCheque = (float) Collection::query()
            ->whereDate('collection_date', $day)
            ->where('method', CollectionMethod::Cheque->value)
            ->sum('amount');

        $driverExpenses = (float) DriverExpense::query()
            ->whereDate('expense_date', $day)
            ->sum('amount');

        $driversCashHeld = 0.0;
        $driversChequeHeld = 0.0;
        foreach ($this->activeDrivers() as $driver) {
            $driversCashHeld += $this->cashBoxes->balance($driver->id);
            $driversChequeHeld += $this->cashBoxes->chequeBalance($driver->id);
        }

        // أرباح اليوم = نفس معادلة قائمة الربح والخسارة (كامل) لفترة يوم واحد.
        $pnl = $this->profitLoss->byCurrency(
            ReportPeriodFilters::fromArray([
                'date_from' => $day,
                'date_to' => $day,
                'currency' => ProfitLossReportService::DEFAULT_CURRENCY,
            ]),
            ProfitLossReportService::MODE_ACCRUAL,
        );
        $dayPnl = $pnl[ProfitLossReportService::DEFAULT_CURRENCY] ?? [
            'cogs' => 0.0,
            'expenses' => 0.0,
            'salaries' => 0.0,
            'net_profit' => 0.0,
            'sales' => 0.0,
        ];

        return [
            'sales_count' => $salesCount,
            'sales_qty' => $salesQty,
            'sales_cash' => round($cashSales, 2),
            'sales_credit' => round($creditSales, 2),
            'sales_total' => round($cashSales + $creditSales, 2),
            'collections_cash' => round($collectionsCash, 2),
            'collections_cheque' => round($collectionsCheque, 2),
            'collections_total' => round($collectionsCash + $collectionsCheque, 2),
            'driver_expenses' => round($driverExpenses, 2),
            'drivers_cash_held' => round($driversCashHeld, 2),
            'drivers_cheque_held' => round($driversChequeHeld, 2),
            'cogs' => round((float) ($dayPnl['cogs'] ?? 0), 2),
            'expenses_total' => round((float) ($dayPnl['expenses'] ?? 0), 2),
            'salaries' => round((float) ($dayPnl['salaries'] ?? 0), 2),
            'profit' => round((float) ($dayPnl['net_profit'] ?? 0), 2),
            'pnl_sales' => round((float) ($dayPnl['sales'] ?? 0), 2),
        ];
    }

    /**
     * @return array<string, int|bool>
     */
    private function fleetAndStock(string $day, Carbon $dayStart, Carbon $dayEnd): array
    {
        $zeroVehicleStock = StockBalance::query()
            ->where('quantity', '<=', 0)
            ->whereHas('warehouse', fn ($q) => $q->where('type', WarehouseType::Vehicle->value)->where('is_active', true))
            ->count();

        $lowVehicleStock = StockBalance::query()
            ->where('quantity', '>', 0)
            ->where('quantity', '<=', 2)
            ->whereHas('warehouse', fn ($q) => $q->where('type', WarehouseType::Vehicle->value)->where('is_active', true))
            ->count();

        $loadsToday = StockMovement::query()
            ->whereBetween('moved_at', [$dayStart, $dayEnd])
            ->where('type', StockMovementType::Load->value)
            ->count();

        $returnsToday = StockMovement::query()
            ->whereBetween('moved_at', [$dayStart, $dayEnd])
            ->where('type', StockMovementType::ReturnToWarehouse->value)
            ->count();

        $trackedProducts = Product::query()->where('is_stock_tracked', true)->count();
        $pricedToday = ProductDailyPrice::query()
            ->whereDate('price_date', $day)
            ->where('currency_code', DailyPriceService::DEFAULT_CURRENCY)
            ->pluck('product_id')
            ->unique()
            ->count();

        $sharingDrivers = 0;
        try {
            $sharingDrivers = count($this->locations->mapMarkers());
        } catch (Throwable $e) {
            Log::warning('Dashboard mapMarkers failed: '.$e->getMessage());
        }

        return [
            'zero_vehicle_stock' => $zeroVehicleStock,
            'low_vehicle_stock' => $lowVehicleStock,
            'loads_today' => $loadsToday,
            'returns_today' => $returnsToday,
            'tracked_products' => $trackedProducts,
            'priced_today' => $pricedToday,
            'pricing_complete' => $trackedProducts === 0 || $pricedToday >= $trackedProducts,
            'sharing_drivers' => $sharingDrivers,
            'active_drivers' => $this->activeDrivers()->count(),
        ];
    }

    /**
     * @return array<string, float|int|string>
     */
    private function financeBrief(): array
    {
        $currency = DailyPriceService::DEFAULT_CURRENCY;

        $invoiced = (float) Invoice::query()
            ->where('status', 'issued')
            ->where('currency_code', $currency)
            ->sum('total_amount');
        $clientPaid = (float) ClientPayment::query()
            ->where('currency_code', $currency)
            ->sum('amount');

        $purchased = (float) PurchaseOrder::query()
            ->where('status', 'issued')
            ->where('currency_code', $currency)
            ->sum('total_amount');
        $supplierPaid = (float) SupplierPayment::query()
            ->where('currency_code', $currency)
            ->sum('amount');

        $main = ['cash' => 0.0, 'cheque' => 0.0];
        try {
            $main = $this->cashBoxes->mainBoxByCurrency()[$currency] ?? $main;
        } catch (Throwable $e) {
            Log::warning('Dashboard mainBox failed: '.$e->getMessage());
        }

        return [
            'currency' => $currency,
            'client_receivable' => round($invoiced - $clientPaid, 2),
            'supplier_payable' => round($purchased - $supplierPaid, 2),
            'main_cash' => round((float) ($main['cash'] ?? 0), 2),
            'main_cheque' => round((float) ($main['cheque'] ?? 0), 2),
            'draft_invoices' => (int) Invoice::query()->where('status', 'draft')->count(),
            'draft_purchase_orders' => (int) PurchaseOrder::query()->where('status', 'draft')->count(),
        ];
    }

    /** @return \Illuminate\Support\Collection<int, User> */
    private function activeDrivers()
    {
        return User::query()
            ->where('role', 'driver')
            ->where('is_active', true)
            ->orderBy('full_name')
            ->get(['id', 'full_name']);
    }
}
