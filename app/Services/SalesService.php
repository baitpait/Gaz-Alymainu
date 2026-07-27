<?php

namespace App\Services;

use App\Enums\SalePaymentType;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Warehouse;
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
    ): Sale {
        if ($quantity <= 0) {
            throw new RuntimeException('الكمية يجب أن تكون أكبر من صفر.');
        }

        $saleDate = $date ?? Carbon::now()->toDateString();

        // سعر البيع = السعر المُدخَل إن وُجد، وإلا سعر اليوم الافتراضي.
        $unitPrice = $unitPriceOverride
            ?? $this->prices->priceFor($product->id, $saleDate, DailyPriceService::DEFAULT_CURRENCY);

        if ($unitPrice === null || $unitPrice <= 0) {
            throw new RuntimeException('أدخل سعر بيع صحيحًا لهذا الصنف.');
        }

        return DB::transaction(function () use ($warehouse, $product, $quantity, $paymentType, $recordedByUserId, $saleDate, $unitPrice) {
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
            ]);
        });
    }
}
