<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Enums\WarehouseType;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * محرك المخزون: كل عملية تُحدِّث رصيد المخزن وتكتب حركة في السجل داخل معاملة واحدة
 * لضمان تطابق الرصيد مع سجل الحركات (Batched/Transactional).
 */
class InventoryService
{
    /**
     * إدخال مخزون بالشراء: زيادة رصيد مخزن ثابت.
     */
    public function purchaseIn(
        Warehouse $warehouse,
        Product $product,
        float $quantity,
        ?int $recordedByUserId = null,
        ?CarbonInterface $movedAt = null,
        ?string $notes = null,
    ): StockMovement {
        $this->assertPositive($quantity);

        return DB::transaction(function () use ($warehouse, $product, $quantity, $recordedByUserId, $movedAt, $notes) {
            $this->applyDelta($warehouse->id, $product->id, $quantity);

            return StockMovement::create([
                'type' => StockMovementType::PurchaseIn,
                'to_warehouse_id' => $warehouse->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'moved_at' => $movedAt ?? Carbon::now(),
                'recorded_by_user_id' => $recordedByUserId,
                'notes' => $notes,
            ]);
        });
    }

    /**
     * تحميل جرات من مخزن ثابت إلى سيارة سائق.
     */
    public function load(
        Warehouse $fromWarehouse,
        Warehouse $toVehicle,
        Product $product,
        float $quantity,
        ?int $recordedByUserId = null,
        ?CarbonInterface $movedAt = null,
        ?string $notes = null,
    ): StockMovement {
        $this->assertPositive($quantity);

        if ($fromWarehouse->type !== WarehouseType::Fixed) {
            throw new RuntimeException('التحميل يجب أن يكون من مخزن ثابت.');
        }
        if ($toVehicle->type !== WarehouseType::Vehicle) {
            throw new RuntimeException('التحميل يجب أن يكون إلى سيارة.');
        }

        return DB::transaction(function () use ($fromWarehouse, $toVehicle, $product, $quantity, $recordedByUserId, $movedAt, $notes) {
            $this->applyDelta($fromWarehouse->id, $product->id, -$quantity);
            $this->applyDelta($toVehicle->id, $product->id, $quantity);

            return StockMovement::create([
                'type' => StockMovementType::Load,
                'from_warehouse_id' => $fromWarehouse->id,
                'to_warehouse_id' => $toVehicle->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'moved_at' => $movedAt ?? Carbon::now(),
                'driver_user_id' => $toVehicle->assigned_user_id,
                'recorded_by_user_id' => $recordedByUserId,
                'notes' => $notes,
            ]);
        });
    }

    /**
     * إرجاع المتبقّي من السيارة إلى مخزن ثابت (نهاية اليوم).
     */
    public function returnToWarehouse(
        Warehouse $fromVehicle,
        Warehouse $toWarehouse,
        Product $product,
        float $quantity,
        ?int $recordedByUserId = null,
        ?CarbonInterface $movedAt = null,
        ?string $notes = null,
    ): StockMovement {
        $this->assertPositive($quantity);

        if ($fromVehicle->type !== WarehouseType::Vehicle) {
            throw new RuntimeException('الإرجاع يجب أن يكون من سيارة.');
        }
        if ($toWarehouse->type !== WarehouseType::Fixed) {
            throw new RuntimeException('الإرجاع يجب أن يكون إلى مخزن ثابت.');
        }

        return DB::transaction(function () use ($fromVehicle, $toWarehouse, $product, $quantity, $recordedByUserId, $movedAt, $notes) {
            $this->applyDelta($fromVehicle->id, $product->id, -$quantity);
            $this->applyDelta($toWarehouse->id, $product->id, $quantity);

            return StockMovement::create([
                'type' => StockMovementType::ReturnToWarehouse,
                'from_warehouse_id' => $fromVehicle->id,
                'to_warehouse_id' => $toWarehouse->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'moved_at' => $movedAt ?? Carbon::now(),
                'driver_user_id' => $fromVehicle->assigned_user_id,
                'recorded_by_user_id' => $recordedByUserId,
                'notes' => $notes,
            ]);
        });
    }

    /**
     * تحويل عام بين أي مخزنين (يشمل ثابت↔ثابت).
     */
    public function transfer(
        Warehouse $fromWarehouse,
        Warehouse $toWarehouse,
        Product $product,
        float $quantity,
        ?int $recordedByUserId = null,
        ?CarbonInterface $movedAt = null,
        ?string $notes = null,
    ): StockMovement {
        $this->assertPositive($quantity);

        if ($fromWarehouse->id === $toWarehouse->id) {
            throw new RuntimeException('لا يمكن التحويل إلى نفس المخزن.');
        }

        return DB::transaction(function () use ($fromWarehouse, $toWarehouse, $product, $quantity, $recordedByUserId, $movedAt, $notes) {
            $this->applyDelta($fromWarehouse->id, $product->id, -$quantity);
            $this->applyDelta($toWarehouse->id, $product->id, $quantity);

            return StockMovement::create([
                'type' => StockMovementType::Transfer,
                'from_warehouse_id' => $fromWarehouse->id,
                'to_warehouse_id' => $toWarehouse->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'moved_at' => $movedAt ?? Carbon::now(),
                'recorded_by_user_id' => $recordedByUserId,
                'notes' => $notes,
            ]);
        });
    }

    /**
     * خروج مخزون بالبيع: خصم من مخزن/سيارة. يُستدعى من محرك المبيعات.
     */
    public function saleOut(
        Warehouse $fromWarehouse,
        Product $product,
        float $quantity,
        ?int $recordedByUserId = null,
        ?CarbonInterface $movedAt = null,
        ?string $notes = null,
    ): StockMovement {
        $this->assertPositive($quantity);

        return DB::transaction(function () use ($fromWarehouse, $product, $quantity, $recordedByUserId, $movedAt, $notes) {
            $this->applyDelta($fromWarehouse->id, $product->id, -$quantity);

            return StockMovement::create([
                'type' => StockMovementType::SaleOut,
                'from_warehouse_id' => $fromWarehouse->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'moved_at' => $movedAt ?? Carbon::now(),
                'driver_user_id' => $fromWarehouse->assigned_user_id,
                'recorded_by_user_id' => $recordedByUserId,
                'notes' => $notes,
            ]);
        });
    }

    /**
     * عكس إدخال مشتريات: خصم الكمية من المخزن مع حركة تسوية في السجل.
     */
    public function reversePurchaseIn(
        Warehouse $warehouse,
        Product $product,
        float $quantity,
        ?int $recordedByUserId = null,
        ?string $notes = null,
    ): StockMovement {
        $this->assertPositive($quantity);

        return DB::transaction(function () use ($warehouse, $product, $quantity, $recordedByUserId, $notes) {
            $this->applyDelta($warehouse->id, $product->id, -$quantity);

            return StockMovement::create([
                'type' => StockMovementType::Adjustment,
                'from_warehouse_id' => $warehouse->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'moved_at' => Carbon::now(),
                'driver_user_id' => $warehouse->assigned_user_id,
                'recorded_by_user_id' => $recordedByUserId,
                'notes' => $notes,
            ]);
        });
    }

    /**
     * إرجاع كمية بيع ملغى إلى المخزن/السيارة مع حركة تسوية في السجل.
     */
    public function restoreForVoidedSale(
        Warehouse $warehouse,
        Product $product,
        float $quantity,
        ?int $recordedByUserId = null,
        ?string $notes = null,
    ): StockMovement {
        $this->assertPositive($quantity);

        return DB::transaction(function () use ($warehouse, $product, $quantity, $recordedByUserId, $notes) {
            $this->applyDelta($warehouse->id, $product->id, $quantity);

            return StockMovement::create([
                'type' => StockMovementType::Adjustment,
                'to_warehouse_id' => $warehouse->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'moved_at' => Carbon::now(),
                'driver_user_id' => $warehouse->assigned_user_id,
                'recorded_by_user_id' => $recordedByUserId,
                'notes' => $notes,
            ]);
        });
    }

    /**
     * تسوية جرد: ضبط الرصيد إلى كمية جديدة مطلقة وتسجيل الفرق.
     */
    public function adjustToQuantity(
        Warehouse $warehouse,
        Product $product,
        float $newQuantity,
        ?int $recordedByUserId = null,
        ?string $notes = null,
    ): StockMovement {
        if ($newQuantity < 0) {
            throw new RuntimeException('الكمية الجديدة لا يمكن أن تكون سالبة.');
        }

        return DB::transaction(function () use ($warehouse, $product, $newQuantity, $recordedByUserId, $notes) {
            $balance = $this->lockBalance($warehouse->id, $product->id);
            $current = (float) ($balance->quantity ?? 0);
            $delta = $newQuantity - $current;

            $balance->quantity = $newQuantity;
            $balance->save();

            return StockMovement::create([
                'type' => StockMovementType::Adjustment,
                'to_warehouse_id' => $delta >= 0 ? $warehouse->id : null,
                'from_warehouse_id' => $delta < 0 ? $warehouse->id : null,
                'product_id' => $product->id,
                'quantity' => abs($delta),
                'moved_at' => Carbon::now(),
                'recorded_by_user_id' => $recordedByUserId,
                'notes' => $notes,
            ]);
        });
    }

    /** الرصيد الحالي لصنف في مخزن. */
    public function balanceFor(int $warehouseId, int $productId): float
    {
        return (float) (StockBalance::query()
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->value('quantity') ?? 0);
    }

    /**
     * تطبيق فرق (+/-) على الرصيد مع قفل السطر ومنع السالب.
     */
    private function applyDelta(int $warehouseId, int $productId, float $delta): void
    {
        $balance = $this->lockBalance($warehouseId, $productId);
        $newQty = (float) $balance->quantity + $delta;

        if ($newQty < 0) {
            throw new RuntimeException('الكمية غير كافية في المخزن لإتمام العملية.');
        }

        $balance->quantity = $newQty;
        $balance->save();
    }

    private function lockBalance(int $warehouseId, int $productId): StockBalance
    {
        $balance = StockBalance::query()
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->lockForUpdate()
            ->first();

        if (! $balance) {
            $balance = new StockBalance([
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'quantity' => 0,
            ]);
            $balance->save();
        }

        return $balance;
    }

    private function assertPositive(float $quantity): void
    {
        if ($quantity <= 0) {
            throw new RuntimeException('الكمية يجب أن تكون أكبر من صفر.');
        }
    }
}
