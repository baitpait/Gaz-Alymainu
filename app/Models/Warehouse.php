<?php

namespace App\Models;

use App\Enums\WarehouseType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * مخزن ثابت أو سيارة سائق (مخزن متحرك).
 */
class Warehouse extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'type',
        'assigned_user_id',
        'vehicle_plate',
        'is_active',
        'notes',
        'recorded_by_user_id',
    ];

    protected $casts = [
        'type' => WarehouseType::class,
        'is_active' => 'boolean',
    ];

    public function isVehicle(): bool
    {
        return $this->type === WarehouseType::Vehicle;
    }

    public function isFixed(): bool
    {
        return $this->type === WarehouseType::Fixed;
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function allowedProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'warehouse_product')->withTimestamps();
    }

    public function balances(): HasMany
    {
        return $this->hasMany(StockBalance::class);
    }
}
