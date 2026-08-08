<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * عكس أثر المخزون لفاتورة مشتريات رُحِّل مخزونها قبل الحذف المنطقي.
 */
class PurchaseOrderStockService
{
    public function __construct(
        private readonly InventoryService $inventory,
    ) {}

    /**
     * Business Purpose: عند حذف فاتورة مشتريات سبق أن زادت المخزون، تُخصم نفس الكميات من مخزن الاستلام.
     */
    public function reversePostedInventory(PurchaseOrder $purchaseOrder, ?int $recordedByUserId = null): void
    {
        if ($purchaseOrder->inventory_posted_at === null) {
            return;
        }

        if (! $purchaseOrder->receiving_warehouse_id) {
            throw new RuntimeException('لا يمكن عكس المخزون: مخزن الاستلام غير محدد.');
        }

        $purchaseOrder->loadMissing(['lines.product', 'receivingWarehouse']);
        $warehouse = $purchaseOrder->receivingWarehouse;
        if (! $warehouse) {
            throw new RuntimeException('لا يمكن عكس المخزون: مخزن الاستلام غير موجود.');
        }

        $ref = $purchaseOrder->legacy_po_no ?? $purchaseOrder->id;

        DB::transaction(function () use ($purchaseOrder, $warehouse, $recordedByUserId, $ref) {
            foreach ($purchaseOrder->lines as $line) {
                if ($line->product_id === null || $line->product === null) {
                    continue;
                }
                if (! $line->product->is_stock_tracked) {
                    continue;
                }

                $qty = (float) $line->quantity;
                if ($qty <= 0) {
                    continue;
                }

                $this->inventory->reversePurchaseIn(
                    $warehouse,
                    $line->product,
                    $qty,
                    $recordedByUserId,
                    'إلغاء فاتورة مشتريات #'.$ref,
                );
            }

            $purchaseOrder->update(['inventory_posted_at' => null]);
        });
    }
}
