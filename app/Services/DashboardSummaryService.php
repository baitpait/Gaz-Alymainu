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
use Illuminate\Support\Carbon;

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
    ) {}

    /**
     * Business Purpose: Aggregate all dashboard sections for a given calendar day (app timezone).
     *
     * @return array<string, mixed>
     */
    public function forDate(?string $date = null): array
    {
        $day = $date ?? $this->prices->today();
        $dayStart = Carbon::parse($day)->startOfDay();
        $dayEnd = Carbon::parse($day)->endOfDay();

        return [
            'date' => $day,
            'today' => $this->todayOps($day),
            'fleet' => $this->fleetAndStock($day, $dayStart, $dayEnd),
            'finance' => $this->financeBrief(),
            'alerts' => $this->alerts($day),
            'shortcuts' => $this->shortcuts(),
        ];
    }

    /**
     * @return array<string, float|int>
     */
    private function todayOps(string $day): array
    {
        $salesBase = Sale::query()->whereDate('sale_date', $day);

        $cashSales = (float) (clone $salesBase)->where('payment_type', SalePaymentType::Cash->value)->sum('total_amount');
        $creditSales = (float) (clone $salesBase)->where('payment_type', SalePaymentType::Credit->value)->sum('total_amount');
        $salesQty = (float) (clone $salesBase)->sum('quantity');
        $salesCount = (int) (clone $salesBase)->count();

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

        $sharingDrivers = count($this->locations->mapMarkers());

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
     * Brief multi-currency aware totals shown per ILS primary + counts for drafts.
     *
     * @return array<string, float|int>
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

        $main = $this->cashBoxes->mainBoxByCurrency()[$currency] ?? ['cash' => 0.0, 'cheque' => 0.0];

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

    /**
     * @return list<array{level: string, text: string, href: string|null}>
     */
    private function alerts(string $day): array
    {
        $items = [];

        $tracked = Product::query()->where('is_stock_tracked', true)->count();
        $priced = ProductDailyPrice::query()
            ->whereDate('price_date', $day)
            ->where('currency_code', DailyPriceService::DEFAULT_CURRENCY)
            ->pluck('product_id')
            ->unique()
            ->count();
        if ($tracked > 0 && $priced < $tracked) {
            $items[] = [
                'level' => 'warn',
                'text' => 'التسعير اليومي غير مكتمل ('.$priced.' من '.$tracked.' أصناف).',
                'href' => route('daily-prices.index'),
            ];
        }

        $zero = StockBalance::query()
            ->where('quantity', '<=', 0)
            ->whereHas('warehouse', fn ($q) => $q->where('type', WarehouseType::Vehicle->value)->where('is_active', true))
            ->count();
        if ($zero > 0) {
            $items[] = [
                'level' => 'danger',
                'text' => $zero.' رصيد صفر في سيارات — راجع المخزون.',
                'href' => route('reports.gas-stock-balances'),
            ];
        }

        $heavyCash = [];
        foreach ($this->activeDrivers() as $driver) {
            $bal = $this->cashBoxes->balance($driver->id);
            if ($bal >= 200) {
                $heavyCash[] = ['name' => $driver->full_name, 'cash' => $bal];
            }
        }
        usort($heavyCash, fn ($a, $b) => $b['cash'] <=> $a['cash']);
        foreach (array_slice($heavyCash, 0, 3) as $row) {
            $items[] = [
                'level' => 'warn',
                'text' => 'كاش لدى '.$row['name'].': '.number_format($row['cash'], 2).' ₪ — لم يُسلَّم بالكامل.',
                'href' => route('cash-handovers.index'),
            ];
        }

        $draftInvoices = (int) Invoice::query()->where('status', 'draft')->count();
        if ($draftInvoices > 0) {
            $items[] = [
                'level' => 'info',
                'text' => $draftInvoices.' فاتورة مسودة معلّقة.',
                'href' => route('invoices.index'),
            ];
        }

        return array_slice($items, 0, 8);
    }

    /**
     * @return list<array{label: string, href: string, gate: string|null}>
     */
    private function shortcuts(): array
    {
        return [
            ['label' => 'نقطة البيع', 'href' => route('pos.index'), 'gate' => 'record-sales'],
            ['label' => 'سحب الكاش', 'href' => route('cash-handovers.index'), 'gate' => 'manage-cash-handover'],
            ['label' => 'حركات المخزون', 'href' => route('stock-movements.index'), 'gate' => 'view-inventory'],
            ['label' => 'خريطة السائقين', 'href' => route('drivers.map'), 'gate' => 'view-driver-locations'],
            ['label' => 'التسعير اليومي', 'href' => route('daily-prices.index'), 'gate' => 'manage-daily-prices'],
            ['label' => 'مركز التقارير', 'href' => route('reports.index'), 'gate' => 'view-period-reports'],
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
