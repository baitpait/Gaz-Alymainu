<?php

namespace App\Livewire;

use App\Enums\StockMovementType;
use App\Enums\WarehouseType;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * تسجيل حركة مخزون: إدخال بالشراء / تحميل إلى سيارة / إرجاع إلى مخزن / تسوية جرد.
 */
class StockMovementForm extends Component
{
    public string $type = 'load';

    public ?int $product_id = null;

    public ?int $from_warehouse_id = null;

    public ?int $to_warehouse_id = null;

    public string $quantity = '';

    public string $moved_at = '';

    public string $notes = '';

    public function mount(): void
    {
        abort_unless(Gate::allows('manage-inventory'), 403);
        $this->moved_at = Carbon::now()->toDateString();
    }

    public function save(InventoryService $inventory): void
    {
        abort_unless(Gate::allows('manage-inventory'), 403);

        $this->validate([
            'type' => ['required', Rule::in(array_keys(StockMovementType::manualOptions()))],
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|gt:0',
            'moved_at' => 'required|date',
            'from_warehouse_id' => 'nullable|exists:warehouses,id',
            'to_warehouse_id' => 'nullable|exists:warehouses,id',
            'notes' => 'nullable|string|max:2000',
        ], [], [
            'type' => 'نوع الحركة',
            'product_id' => 'الصنف',
            'quantity' => 'الكمية',
            'moved_at' => 'التاريخ',
        ]);

        $product = Product::findOrFail($this->product_id);
        $qty = (float) $this->quantity;
        $movedAt = Carbon::parse($this->moved_at);
        $userId = auth()->id();
        $notes = trim($this->notes) !== '' ? trim($this->notes) : null;

        try {
            match ($this->type) {
                StockMovementType::PurchaseIn->value => $inventory->purchaseIn(
                    $this->requireWarehouse($this->to_warehouse_id, WarehouseType::Fixed, 'المخزن'),
                    $product, $qty, $userId, $movedAt, $notes,
                ),
                StockMovementType::Load->value => $inventory->load(
                    $this->requireWarehouse($this->from_warehouse_id, WarehouseType::Fixed, 'المخزن المصدر'),
                    $this->requireWarehouse($this->to_warehouse_id, WarehouseType::Vehicle, 'السيارة'),
                    $product, $qty, $userId, $movedAt, $notes,
                ),
                StockMovementType::ReturnToWarehouse->value => $inventory->returnToWarehouse(
                    $this->requireWarehouse($this->from_warehouse_id, WarehouseType::Vehicle, 'السيارة'),
                    $this->requireWarehouse($this->to_warehouse_id, WarehouseType::Fixed, 'المخزن'),
                    $product, $qty, $userId, $movedAt, $notes,
                ),
                StockMovementType::Transfer->value => $inventory->transfer(
                    $this->requireWarehouse($this->from_warehouse_id, null, 'المخزن المصدر'),
                    $this->requireWarehouse($this->to_warehouse_id, null, 'المخزن الوجهة'),
                    $product, $qty, $userId, $movedAt, $notes,
                ),
                StockMovementType::Adjustment->value => $inventory->adjustToQuantity(
                    $this->requireWarehouse($this->to_warehouse_id, null, 'المخزن'),
                    $product, $qty, $userId, $notes,
                ),
            };
        } catch (\RuntimeException $e) {
            $this->addError('quantity', $e->getMessage());

            return;
        }

        session()->flash('toast', 'تم تسجيل الحركة');
        $this->redirect(route('stock-movements.index'), navigate: true);
    }

    private function requireWarehouse(?int $id, ?WarehouseType $expectedType, string $label): Warehouse
    {
        if (! $id) {
            throw new \RuntimeException("يجب اختيار {$label}.");
        }

        $warehouse = Warehouse::findOrFail($id);

        if ($expectedType !== null && $warehouse->type !== $expectedType) {
            throw new \RuntimeException("النوع غير صحيح لـ {$label}.");
        }

        return $warehouse;
    }

    public function render()
    {
        $products = Product::query()->stockTracked()->orderBy('name')->get(['id', 'name']);
        $fixed = Warehouse::query()->where('type', 'fixed')->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $vehicles = Warehouse::query()->where('type', 'vehicle')->where('is_active', true)->with('driver')->orderBy('name')->get();

        $allWarehouses = Warehouse::query()->where('is_active', true)->orderBy('type')->orderBy('name')->get(['id', 'name', 'type']);

        return view('livewire.stock-movement-form', [
            'products' => $products,
            'fixedWarehouses' => $fixed,
            'vehicles' => $vehicles,
            'allWarehouses' => $allWarehouses,
            'typeOptions' => StockMovementType::manualOptions(),
        ]);
    }
}
