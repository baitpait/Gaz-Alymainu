<?php

namespace App\Livewire;

use App\Enums\SalePaymentType;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\Warehouse;
use App\Services\CashBoxService;
use App\Services\DailyPriceService;
use App\Services\SalesService;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

/**
 * نقطة بيع مبسّطة: عدّاد تنازلي للمخزون + زرّان (نقدي / على الحساب).
 * بلا اسم زبون — مجرّد توثيق بيع يخصم من مخزون السيارة بسعر اليوم.
 * البيع على الحساب يطلب ملاحظة من السائق وتُحفظ في sales.notes.
 */
class SalesTerminal extends Component
{
    public ?int $warehouseId = null;

    /** @var array<int, string> الكمية لكل صنف (product_id => qty) */
    public array $qty = [];

    /** @var array<int, string> سعر البيع القابل للتعديل لكل صنف (product_id => price) */
    public array $price = [];

    /** @var array<int, string> ملاحظات البيع على الحساب لكل صنف */
    public array $creditNotes = [];

    /** صنف بانتظار إدخال ملاحظة قبل تأكيد البيع على الحساب */
    public ?int $awaitingCreditNotesFor = null;

    public function mount(): void
    {
        abort_unless(Gate::allows('record-sales'), 403);

        $user = auth()->user();
        if ($user->isDriver()) {
            $this->warehouseId = $user->assignedVehicle?->id;
        }
    }

    /**
     * Business Purpose: فتح خانة الملاحظات قبل توثيق بيع على الحساب.
     */
    public function beginCreditSale(int $productId): void
    {
        abort_unless(Gate::allows('record-sales'), 403);
        $this->awaitingCreditNotesFor = $productId;
        $this->creditNotes[$productId] = $this->creditNotes[$productId] ?? '';
    }

    /**
     * Business Purpose: إغلاق خانة ملاحظات الآجل دون بيع.
     */
    public function cancelCreditNotes(): void
    {
        $this->awaitingCreditNotesFor = null;
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
        $notes = null;

        if ($type === SalePaymentType::Credit) {
            $notes = trim((string) ($this->creditNotes[$productId] ?? ''));
            if ($notes === '') {
                $this->awaitingCreditNotesFor = $productId;
                $this->dispatch('toast', message: 'اكتب ملاحظة للبيع على الحساب', type: 'error');

                return;
            }
            if (mb_strlen($notes) > 2000) {
                $this->dispatch('toast', message: 'الملاحظة طويلة جداً', type: 'error');

                return;
            }
        }

        try {
            $sale = $sales->recordSale(
                $warehouse,
                $product,
                $quantity,
                $type,
                auth()->id(),
                null,
                $unitPrice,
                $notes,
            );
        } catch (\RuntimeException $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');

            return;
        }

        $this->qty[$productId] = '0';
        $this->creditNotes[$productId] = '';
        $this->awaitingCreditNotesFor = null;
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
        $today = \App\Support\AppDateTime::today();

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
