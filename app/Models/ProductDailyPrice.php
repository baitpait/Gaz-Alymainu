<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * سعر بيع صنف في تاريخ محدد (تسعير يومي).
 */
class ProductDailyPrice extends Model
{
    protected $fillable = [
        'product_id',
        'price_date',
        'currency_code',
        'sale_price',
        'recorded_by_user_id',
    ];

    protected $casts = [
        'price_date' => 'date',
        'sale_price' => 'decimal:4',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
