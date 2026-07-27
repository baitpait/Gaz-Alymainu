<?php

namespace App\Services;

use App\Models\ProductDailyPrice;
use Illuminate\Support\Carbon;

/**
 * إدارة سعر البيع اليومي للأصناف (بالشيكل افتراضيًا).
 */
class DailyPriceService
{
    public const DEFAULT_CURRENCY = 'ILS';

    /**
     * تعيين/تحديث سعر بيع صنف في تاريخ محدد.
     */
    public function setPrice(
        int $productId,
        string $date,
        float $salePrice,
        string $currencyCode = self::DEFAULT_CURRENCY,
        ?int $recordedByUserId = null,
    ): ProductDailyPrice {
        return ProductDailyPrice::updateOrCreate(
            [
                'product_id' => $productId,
                'price_date' => $date,
                'currency_code' => $currencyCode,
            ],
            [
                'sale_price' => $salePrice,
                'recorded_by_user_id' => $recordedByUserId,
            ],
        );
    }

    /**
     * سعر البيع لصنف في تاريخ محدد (أو null إن لم يُحدَّد).
     */
    public function priceFor(int $productId, string $date, string $currencyCode = self::DEFAULT_CURRENCY): ?float
    {
        $price = ProductDailyPrice::query()
            ->where('product_id', $productId)
            ->whereDate('price_date', $date)
            ->where('currency_code', $currencyCode)
            ->value('sale_price');

        return $price !== null ? (float) $price : null;
    }

    public function today(): string
    {
        return Carbon::now()->toDateString();
    }
}
