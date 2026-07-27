<?php

namespace App\Models;

use App\Enums\CollectionMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * تحصيل مبلغ بلا ذمم/زبون: المبلغ + طريقة الدفع (نقدي/شيك).
 */
class Collection extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'driver_user_id',
        'warehouse_id',
        'method',
        'amount',
        'currency_code',
        'cheque_number',
        'collection_date',
        'collected_at',
        'recorded_by_user_id',
        'notes',
    ];

    protected $casts = [
        'method' => CollectionMethod::class,
        'amount' => 'decimal:4',
        'collection_date' => 'date',
        'collected_at' => 'datetime',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_user_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}
