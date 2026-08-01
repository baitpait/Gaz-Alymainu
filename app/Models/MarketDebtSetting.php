<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * إعداد دين السوق للشركة (سجل واحد): مبلغ افتتاحي + تاريخ بداية الاحتساب.
 */
class MarketDebtSetting extends Model
{
    protected $fillable = [
        'opening_amount',
        'as_of_date',
        'currency_code',
        'notes',
        'updated_by_user_id',
    ];

    protected $casts = [
        'opening_amount' => 'decimal:4',
        'as_of_date' => 'date',
    ];

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    /**
     * السجل الوحيد للشركة؛ يُنشأ عند الحاجة بمبلغ صفر وتاريخ اليوم.
     */
    public static function current(): self
    {
        $row = static::query()->orderBy('id')->first();
        if ($row) {
            return $row;
        }

        return static::query()->create([
            'opening_amount' => 0,
            'as_of_date' => Carbon::today()->toDateString(),
            'currency_code' => 'ILS',
        ]);
    }
}
