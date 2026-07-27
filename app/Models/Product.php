<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * منتج مبيعات — أسعار منفصلة لكل عملة (ILS, JOD, USD, EUR).
 *
 * @property-read Collection<int, ProductCurrencyPrice> $currencyPrices
 */
class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'imported_from_legacy_catalog_id',
        'name',
        'product_code',
        'description',
        'is_stock_tracked',
        'unit',
        'capacity_kg',
        'category',
    ];

    protected $casts = [
        'is_stock_tracked' => 'boolean',
        'capacity_kg' => 'decimal:2',
    ];

    /** @return list<string> */
    public static function billingCurrencies(): array
    {
        return ['ILS', 'JOD', 'USD', 'EUR'];
    }

    public function currencyPrices(): HasMany
    {
        return $this->hasMany(ProductCurrencyPrice::class);
    }

    public function dailyPrices(): HasMany
    {
        return $this->hasMany(ProductDailyPrice::class);
    }

    public function stockBalances(): HasMany
    {
        return $this->hasMany(StockBalance::class);
    }

    /** أصناف الغاز التي يُتتبَّع مخزونها. */
    public function scopeStockTracked(Builder $query): Builder
    {
        return $query->where('is_stock_tracked', true);
    }

    public function priceRowForCurrency(string $currencyCode): ?ProductCurrencyPrice
    {
        return $this->currencyPrices->firstWhere('currency_code', $currencyCode);
    }

    public function hasCompletePricingForCurrency(string $currencyCode): bool
    {
        $row = $this->relationLoaded('currencyPrices')
            ? $this->priceRowForCurrency($currencyCode)
            : $this->currencyPrices()->where('currency_code', $currencyCode)->first();

        if ($row === null) {
            return false;
        }

        return $row->sale_price !== null
            && $row->min_sale_price !== null
            && $row->service_cost_price !== null;
    }

    /** منتجات يمكن بيعها بعملة الفاتورة (الثلاثة أسعار معرّفة). */
    public function scopeForSaleInCurrency(Builder $query, string $currencyCode): Builder
    {
        return $query->whereHas('currencyPrices', function (Builder $q) use ($currencyCode) {
            $q->where('currency_code', $currencyCode)
                ->whereNotNull('sale_price')
                ->whereNotNull('min_sale_price')
                ->whereNotNull('service_cost_price');
        });
    }
}
