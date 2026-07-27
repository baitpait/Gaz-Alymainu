<?php

namespace App\Models;

use App\Enums\CollectionMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * سحب من صندوق السائق إلى الصندوق الرئيسي (كاش أو شيك).
 */
class CashHandover extends Model
{
    protected $fillable = [
        'driver_user_id',
        'amount',
        'currency_code',
        'method',
        'cheque_number',
        'handover_date',
        'handed_at',
        'received_by_user_id',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'method' => CollectionMethod::class,
        'handover_date' => 'date',
        'handed_at' => 'datetime',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_user_id');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by_user_id');
    }
}
