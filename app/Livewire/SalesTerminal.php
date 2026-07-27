<?php

namespace App\Livewire;

use App\Enums\SalePaymentType;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\Warehouse;
use App\Services\CashBoxService;
use App\Services\DailyPriceService;
use App\Services\SalesService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

/**
 * نقطة بيع مبسّطة: عدّاد تنازلي للمخزون + زرّان (نقدي / على الحساب).
 * بلا اسم زبون — مجرّد توثيق بيع يخصم من مخزون السيارة بسعر اليوم.
 */
class SalesTerminal extends Component
{
    public ?int $warehouseId = null;

    /** @var array<int, string> الكمية لكل صنف (product_id => qty) */
    public array $qty = [];

    /** @var array<int, string> سعر البيع القابل للتعديل لكل صنف (product_id => price) */
    public array $price = [];

    public function mount(): void
    {
        abort_unless(Gate::allows('record-sales'), 403);

        $user = auth()->user();
        if ($user->isDriver()) {
            $this->warehouseId = $user->assignedVehicle?->id;
        }
    }

    /**
     * تنفيذ عملية بيع لصنف واحد.
     */
    public function sell(int $productId, string $paymentType, SalesService $sales): void
    {
        abort_unless(Gate::allows('record-sales'), 403);

        $warehouse = $this->resolveWarehouse();
        if (! $warehouse) {
            $this->dispatch('toast', message: 'اختر السيارة أولًا', type: 'error');

            return;
        }

        $product = Product::find($productId);
        if (! $product) {
            return;
        }

        $quantity = (float) ($this->qty[$productId] ?? 0);
        if ($quantity <= 0) {
            $this->dispatch('toast', message: 'أدخل الكمية', type: 'error');

            return;
        }

        $unitPrice = isset($this->price[$productId]) && $this->price[$productId] !== ''
            ? (float) $this->price[$productId]
            : null;

        if ($unitPrice === null || $unitPrice <= 0) {
            $this->dispatch('toast', message: 'أدخل سعر بيع صحيحًا', type: 'error');

            return;
        }

        $type = SalePaymentType::from($paymentType);

        try {
            $sale = $sales->recordSale($warehouse, $product, $quantity, $type, auth()->id(), null, $unitPrice);
        } catch (\RuntimeException $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');

            return;
        }

        $this->qty[$productId] = '0';
        $this->dispatch('sold', productId: $productId);
        $this->dispatch('toast', message: "تم توثيق بيع {$type->label()} — {$sale->total_amount} ش");
    }

    private function resolveWarehouse(): ?Warehouse
    {
        if (! $this->warehouseId) {
            return null;
        }

        $warehouse = Warehouse::find($this->warehouseId);
        if (! $warehouse || $warehouse->type->value !== 'vehicle') {
            return null;
        }

        $user = auth()->user();
        if ($user->isDriver() && $warehouse->assigned_user_id !== $user->id) {
            return null;
        }

        return $warehouse;
    }

    public function render(CashBoxService $cashBox, DailyPriceService $prices)
    {
        $user = auth()->user();
        $today = Carbon::now()->toDateString();

        $vehicles = collect();
        if (! $user->isDriver()) {
            $vehicles = Warehouse::query()
                ->where('type', 'vehicle')
                ->where('is_active', true)
                ->with('driver')
                ->orderBy('name')
                ->get();
        }

        $warehouse = $this->resolveWarehouse();
        $items = collect();
        $driverUserId = null;

        if ($warehouse) {
            $driverUserId = $warehouse->assigned_user_id;
            $items = StockBalance::query()
                ->where('warehouse_id', $warehouse->id)
                ->with('product')
                ->get()
                ->filter(fn ($b) => $b->product !== null)
                ->sortBy(fn ($b) => $b->product->name)
                ->map(function ($b) use ($prices, $today) {
                    $this->qty[$b->product_id] = $this->qty[$b->product_id] ?? '1';

                    $dailyPrice = $prices->priceFor($b->product_id, $today, DailyPriceService::DEFAULT_CURRENCY);

                    // سعر الإدارة اليومي مجرّد افتراضي؛ يُملأ مرة واحدة ثم يبقى قابلًا للتعديل.
                    if (! isset($this->price[$b->product_id]) || $this->price[$b->product_id] === '') {
                        $this->price[$b->product_id] = $dailyPrice !== null ? (string) $dailyPrice : '';
                    }

                    return [
                        'product' => $b->product,
                        'quantity' => (float) $b->quantity,
                        'price' => $dailyPrice,
                    ];
                })
                ->values();
        }

        $cashToday = $driverUserId ? $cashBox->cashSalesForDate($driverUserId, $today) : 0;
        $creditToday = $driverUserId ? $cashBox->creditSalesForDate($driverUserId, $today) : 0;
        $collectCashToday = $driverUserId ? $cashBox->cashCollectionsForDate($driverUserId, $today) : 0;
        $collectChequeToday = $driverUserId ? $cashBox->chequeCollectionsForDate($driverUserId, $today) : 0;
        $boxBalance = $driverUserId ? $cashBox->balance($driverUserId) : 0;

        return view('livewire.sales-terminal', [
            'vehicles' => $vehicles,
            'warehouse' => $warehouse,
            'items' => $items,
            'today' => $today,
            'cashToday' => $cashToday,
            'creditToday' => $creditToday,
            'collectCashToday' => $collectCashToday,
            'collectChequeToday' => $collectChequeToday,
            'boxBalance' => $boxBalance,
            'isDriver' => $user->isDriver(),
        ]);
    }
}
