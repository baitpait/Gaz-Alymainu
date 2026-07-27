<?php

namespace App\Models;

use App\Enums\DriverExpenseCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * مصروف سائق/سيارة مستقل، يُخصم من الرصيد النقدي لصندوق السائق.
 */
class DriverExpense extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'driver_user_id',
        'warehouse_id',
        'amount',
        'currency_code',
        'category',
        'expense_date',
        'spent_at',
        'recorded_by_user_id',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'category' => DriverExpenseCategory::class,
        'expense_date' => 'date',
        'spent_at' => 'datetime',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_user_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }
}
