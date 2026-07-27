<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalaryPayment extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PAID = 'paid';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'employee_id',
        'period_year',
        'period_month',
        'base_amount',
        'worked_days',
        'daily_rate',
        'bonus_amount',
        'deduction_amount',
        'net_amount',
        'currency_code',
        'paid_at',
        'method',
        'bank_reference',
        'status',
        'notes',
        'recorded_by_user_id',
    ];

    protected $casts = [
        'base_amount' => 'decimal:4',
        'worked_days' => 'integer',
        'daily_rate' => 'decimal:4',
        'bonus_amount' => 'decimal:4',
        'deduction_amount' => 'decimal:4',
        'net_amount' => 'decimal:4',
        'paid_at' => 'date',
        'period_year' => 'integer',
        'period_month' => 'integer',
    ];

    public static function computeNet(float $base, float $bonus, float $deduction): float
    {
        return round(max(0, $base + $bonus - $deduction), 2);
    }

    public function periodLabel(): string
    {
        return sprintf('%02d/%d', (int) $this->period_month, (int) $this->period_year);
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            self::STATUS_PAID => 'مدفوع',
            self::STATUS_CANCELLED => 'ملغى',
            default => 'مسودة',
        };
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }
}
