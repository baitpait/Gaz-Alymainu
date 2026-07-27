<?php

namespace App\Models;

use App\Enums\StockMovementType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * سجل حركة مخزنية واحدة (إدخال/تحميل/إرجاع/تسوية).
 */
class StockMovement extends Model
{
    protected $fillable = [
        'type',
        'from_warehouse_id',
        'to_warehouse_id',
        'product_id',
        'quantity',
        'moved_at',
        'driver_user_id',
        'recorded_by_user_id',
        'notes',
    ];

    protected $casts = [
        'type' => StockMovementType::class,
        'quantity' => 'decimal:4',
        'moved_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_user_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }
}
