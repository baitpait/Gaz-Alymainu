<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Business Purpose: آخر إحداثيات GPS لسائق أثناء مشاركة موقعه مع الإدارة.
 */
class DriverLocation extends Model
{
    protected $fillable = [
        'driver_user_id',
        'latitude',
        'longitude',
        'accuracy',
        'is_sharing',
        'recorded_at',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'accuracy' => 'decimal:2',
        'is_sharing' => 'boolean',
        'recorded_at' => 'datetime',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_user_id');
    }

    /** هل النقطة حديثة بما يكفي لاعتبار السائق «حيّاً» على الخريطة؟ */
    public function isFresh(int $maxAgeSeconds = 180): bool
    {
        if (! $this->is_sharing || $this->recorded_at === null) {
            return false;
        }

        return $this->recorded_at->diffInSeconds(now()) <= $maxAgeSeconds;
    }
}
