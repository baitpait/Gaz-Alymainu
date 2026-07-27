<?php

namespace App\Models;

use App\Enums\SalePaymentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * بيع موثّق (بلا اسم زبون). نقدي أو على الحساب.
 */
class Sale extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'warehouse_id',
        'product_id',
        'driver_user_id',
        'payment_type',
        'quantity',
        'unit_price',
        'total_amount',
        'currency_code',
        'sale_date',
        'sold_at',
        'recorded_by_user_id',
        'notes',
    ];

    protected $casts = [
        'payment_type' => SalePaymentType::class,
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'total_amount' => 'decimal:4',
        'sale_date' => 'date',
        'sold_at' => 'datetime',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_user_id');
    }
}
