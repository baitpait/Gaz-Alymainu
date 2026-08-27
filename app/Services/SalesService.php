<?php

namespace App\Services;

use App\Enums\SalePaymentType;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Warehouse;
use App\Support\AppDateTime;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * محرك المبيعات المبسّط (توثيق بيع بلا زبون).
 * كل بيع يخصم من مخزون السيارة بسعر اليوم، ويُسجَّل نقديًا أو على الحساب.
 */
class SalesService
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly DailyPriceService $prices,
        private readonly CashBoxService $cashBoxes,
    ) {}

    /**
     * تسجيل عملية بيع واحدة.
     *
     * سعر الوحدة: سعر الإدارة اليومي مجرّد اقتراح افتراضي؛ يمكن للبائع تمرير سعر مختلف
     * ($unitPriceOverride) فيُعتمد بدلًا منه (البيع حسب الطلب وغير ملزم بسعر الإدارة).
     */
    public function recordSale(
        Warehouse $warehouse,
        Product $product,
        float $quantity,
        SalePaymentType $paymentType,
        ?int $recordedByUserId = null,
        ?string $date = null,
        ?float $unitPriceOverride = null,
        ?string $notes = null,
    ): Sale {
        if ($quantity <= 0) {
            throw new RuntimeException('الكمية يجب أن تكون أكبر من صفر.');
        }

        $saleDate = $date ?? AppDateTime::today();

        // سعر البيع = السعر المُدخَل إن وُجد، وإلا سعر اليوم الافتراضي.
        $unitPrice = $unitPriceOverride
            ?? $this->prices->priceFor($product->id, $saleDate, DailyPriceService::DEFAULT_CURRENCY);

        if ($unitPrice === null || $unitPrice <= 0) {
            throw new RuntimeException('أدخل سعر بيع صحيحًا لهذا الصنف.');
        }

        $notes = $notes !== null ? trim($notes) : null;
        if ($notes === '') {
            $notes = null;
        }

        return DB::transaction(function () use ($warehouse, $product, $quantity, $paymentType, $recordedByUserId, $saleDate, $unitPrice, $notes) {
            // يخصم من المخزون ويكتب حركة (سيفشل إن كانت الكمية غير كافية).
            $this->inventory->saleOut($warehouse, $product, $quantity, $recordedByUserId, Carbon::now(), 'بيع '.$paymentType->label());

            return Sale::create([
                'warehouse_id' => $warehouse->id,
                'product_id' => $product->id,
                'driver_user_id' => $warehouse->assigned_user_id,
                'payment_type' => $paymentType,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_amount' => round($unitPrice * $quantity, 4),
                'currency_code' => DailyPriceService::DEFAULT_CURRENCY,
                'sale_date' => $saleDate,
                'sold_at' => Carbon::now(),
                'recorded_by_user_id' => $recordedByUserId,
                'notes' => $notes,
            ]);
        });
    }

    /**
     * Business Purpose: Correct quantity, unit price, and/or notes on an existing sale.
     * Quantity delta adjusts warehouse stock; lowering cash total is blocked when driver
     * cash no longer covers the reduction (handover/expense already taken).
     */
    public function updateSale(
        Sale $sale,
        float $quantity,
        float $unitPrice,
        ?string $notes = null,
        ?int $updatedByUserId = null,
    ): Sale {
        if ($sale->trashed()) {
            throw new RuntimeException('هذا البيع محذوف ولا يمكن تعديله.');
        }

        if ($quantity <= 0) {
            throw new RuntimeException('الكمية يجب أن تكون أكبر من صفر.');
        }

        if ($unitPrice <= 0) {
            throw new RuntimeException('أدخل سعر بيع صحيحًا.');
        }

        $notes = $notes !== null ? trim($notes) : null;
        if ($notes === '') {
            $notes = null;
        }

        $sale->loadMissing(['warehouse', 'product']);
        $warehouse = $sale->warehouse;
        $product = $sale->product;

        if (! $warehouse || ! $product) {
            throw new RuntimeException('تعذّر التعديل: المخزن أو الصنف غير موجود.');
        }

        $oldQty = (float) $sale->quantity;
        $newTotal = round($unitPrice * $quantity, 4);
        $oldTotal = (float) $sale->total_amount;
        $reduction = round($oldTotal - $newTotal, 4);

        if (
            $sale->payment_type === SalePaymentType::Cash
            && $reduction > 0.0001
            && $sale->driver_user_id
        ) {
            $available = $this->cashBoxes->balance((int) $sale->driver_user_id);
            if ($reduction > $available + 0.0001) {
                throw new RuntimeException(
                    'لا يمكن تخفيض مبلغ البيع النقدي: تم تسليم أو صرف جزء من كاش السائق.'
                );
            }
        }

        $qtyDelta = round($quantity - $oldQty, 4);

        return DB::transaction(function () use (
            $sale,
            $warehouse,
            $product,
            $quantity,
            $unitPrice,
            $newTotal,
            $notes,
            $qtyDelta,
            $updatedByUserId,
        ) {
            if ($qtyDelta > 0.0001) {
                $this->inventory->saleOut(
                    $warehouse,
                    $product,
                    $qtyDelta,
                    $updatedByUserId,
                    Carbon::now(),
                    'تعديل كمية بيع #'.$sale->id.' (+)',
                );
            } elseif ($qtyDelta < -0.0001) {
                $this->inventory->restoreForVoidedSale(
                    $warehouse,
                    $product,
                    abs($qtyDelta),
                    $updatedByUserId,
                    'تعديل كمية بيع #'.$sale->id.' (−)',
                );
            }

            $sale->quantity = $quantity;
            $sale->unit_price = $unitPrice;
            $sale->total_amount = $newTotal;
            $sale->notes = $notes;
            $sale->save();

            return $sale->refresh();
        });
    }

    /**
     * إلغاء بيع: إرجاع الكمية للمخزن/السيارة ثم حذف منطقي للسجل.
     * يؤثر تلقائياً على صندوق السائق ودين السوق لأنهما يُحسبان من المبيعات غير المحذوفة.
     * يُرفض حذف البيع النقدي إذا لم يعد رصيد كاش السائق يغطي مبلغ البيع (بعد تسليم/صرف).
     */
    public function voidSale(Sale $sale, ?int $voidedByUserId = null): void
    {
        if ($sale->trashed()) {
            throw new RuntimeException('هذا البيع محذوف مسبقاً.');
        }

        $sale->loadMissing(['warehouse', 'product']);

        $warehouse = $sale->warehouse;
        $product = $sale->product;

        if (! $warehouse || ! $product) {
            throw new RuntimeException('تعذّر إلغاء البيع: المخزن أو الصنف غير موجود.');
        }

        if (
            $sale->payment_type === SalePaymentType::Cash
            && $sale->driver_user_id
        ) {
            $available = $this->cashBoxes->balance((int) $sale->driver_user_id);
            if ((float) $sale->total_amount > $available + 0.0001) {
                throw new RuntimeException(
                    'لا يمكن حذف البيع النقدي بعد تسليم أو صرف الكاش المرتبط به.'
                );
            }
        }

        DB::transaction(function () use ($sale, $warehouse, $product, $voidedByUserId) {
            $this->inventory->restoreForVoidedSale(
                $warehouse,
                $product,
                (float) $sale->quantity,
                $voidedByUserId,
                'إلغاء بيع #'.$sale->id,
            );

            $sale->delete();
        });
    }
}
