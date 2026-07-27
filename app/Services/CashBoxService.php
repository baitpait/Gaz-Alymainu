<?php

namespace App\Services;

use App\Enums\CollectionMethod;
use App\Enums\DriverExpenseCategory;
use App\Enums\SalePaymentType;
use App\Models\CashHandover;
use App\Models\Collection;
use App\Models\DriverExpense;
use App\Models\Sale;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * صندوق السائق: يتجمّع من المبيعات النقدية والتحصيل النقدي، ويُسحب منه (للصندوق الرئيسي)
 * وتُخصم منه مصروفات السائق النقدية.
 * الرصيد النقدي = (مبيعات نقدية + تحصيل نقدي) − سحب نقدي − مصروفات السائق.
 * الشيكات تُوثَّق منفصلة ولا تدخل الرصيد النقدي.
 */
class CashBoxService
{
    public const CURRENCY = 'ILS';

    /** إجمالي المبيعات النقدية للسائق. */
    public function totalCashSales(int $driverUserId): float
    {
        return (float) Sale::query()
            ->where('driver_user_id', $driverUserId)
            ->where('payment_type', SalePaymentType::Cash->value)
            ->sum('total_amount');
    }

    /** إجمالي التحصيل النقدي للسائق. */
    public function totalCashCollections(int $driverUserId): float
    {
        return (float) Collection::query()
            ->where('driver_user_id', $driverUserId)
            ->where('method', CollectionMethod::Cash->value)
            ->sum('amount');
    }

    /** إجمالي تحصيل الشيكات للسائق. */
    public function totalChequeCollections(int $driverUserId): float
    {
        return (float) Collection::query()
            ->where('driver_user_id', $driverUserId)
            ->where('method', CollectionMethod::Cheque->value)
            ->sum('amount');
    }

    /** تحصيل نقدي اليوم للسائق. */
    public function cashCollectionsForDate(int $driverUserId, string $date): float
    {
        return (float) Collection::query()
            ->where('driver_user_id', $driverUserId)
            ->where('method', CollectionMethod::Cash->value)
            ->whereDate('collection_date', $date)
            ->sum('amount');
    }

    /** تحصيل شيكات اليوم للسائق. */
    public function chequeCollectionsForDate(int $driverUserId, string $date): float
    {
        return (float) Collection::query()
            ->where('driver_user_id', $driverUserId)
            ->where('method', CollectionMethod::Cheque->value)
            ->whereDate('collection_date', $date)
            ->sum('amount');
    }

    /**
     * تسجيل عملية تحصيل (بلا ذمم/زبون): المبلغ + طريقة الدفع فقط.
     */
    public function recordCollection(
        int $driverUserId,
        float $amount,
        CollectionMethod $method,
        ?int $warehouseId = null,
        ?string $chequeNumber = null,
        ?int $recordedByUserId = null,
        ?string $notes = null,
    ): Collection {
        if ($amount <= 0) {
            throw new RuntimeException('المبلغ يجب أن يكون أكبر من صفر.');
        }

        return Collection::create([
            'driver_user_id' => $driverUserId,
            'warehouse_id' => $warehouseId,
            'method' => $method,
            'amount' => $amount,
            'currency_code' => self::CURRENCY,
            'cheque_number' => $method === CollectionMethod::Cheque ? $chequeNumber : null,
            'collection_date' => Carbon::now()->toDateString(),
            'collected_at' => Carbon::now(),
            'recorded_by_user_id' => $recordedByUserId,
            'notes' => $notes,
        ]);
    }

    /** إجمالي ما سُحب من السائق (كاش + شيك). */
    public function totalHandedOver(int $driverUserId): float
    {
        return (float) CashHandover::query()
            ->where('driver_user_id', $driverUserId)
            ->sum('amount');
    }

    /** إجمالي الكاش المسحوب من السائق. */
    public function totalCashHandedOver(int $driverUserId): float
    {
        return (float) CashHandover::query()
            ->where('driver_user_id', $driverUserId)
            ->where('method', CollectionMethod::Cash->value)
            ->sum('amount');
    }

    /** إجمالي الشيكات المسحوبة من السائق. */
    public function totalChequeHandedOver(int $driverUserId): float
    {
        return (float) CashHandover::query()
            ->where('driver_user_id', $driverUserId)
            ->where('method', CollectionMethod::Cheque->value)
            ->sum('amount');
    }

    /** إجمالي مصروفات السائق النقدية. */
    public function totalDriverExpenses(int $driverUserId): float
    {
        return (float) DriverExpense::query()
            ->where('driver_user_id', $driverUserId)
            ->sum('amount');
    }

    /** مصروفات السائق ليوم محدّد. */
    public function driverExpensesForDate(int $driverUserId, string $date): float
    {
        return (float) DriverExpense::query()
            ->where('driver_user_id', $driverUserId)
            ->whereDate('expense_date', $date)
            ->sum('amount');
    }

    /** الرصيد النقدي الحالي في صندوق السائق (لم يُسحب بعد، بعد خصم المصروفات). */
    public function balance(int $driverUserId): float
    {
        return round(
            $this->totalCashSales($driverUserId)
            + $this->totalCashCollections($driverUserId)
            - $this->totalCashHandedOver($driverUserId)
            - $this->totalDriverExpenses($driverUserId),
            4
        );
    }

    /**
     * تسجيل مصروف سائق/سيارة يُخصم من الرصيد النقدي.
     * يُمنع تجاوز الرصيد النقدي المتاح.
     */
    public function recordExpense(
        int $driverUserId,
        float $amount,
        DriverExpenseCategory $category,
        ?int $warehouseId = null,
        ?int $recordedByUserId = null,
        ?string $notes = null,
    ): DriverExpense {
        if ($amount <= 0) {
            throw new RuntimeException('المبلغ يجب أن يكون أكبر من صفر.');
        }

        if ($amount > $this->balance($driverUserId) + 0.0001) {
            throw new RuntimeException('المبلغ أكبر من الرصيد النقدي المتاح لدى السائق.');
        }

        return DriverExpense::create([
            'driver_user_id' => $driverUserId,
            'warehouse_id' => $warehouseId,
            'amount' => $amount,
            'currency_code' => self::CURRENCY,
            'category' => $category,
            'expense_date' => Carbon::now()->toDateString(),
            'spent_at' => Carbon::now(),
            'recorded_by_user_id' => $recordedByUserId,
            'notes' => $notes,
        ]);
    }

    /** رصيد الشيكات الحالي لدى السائق (محصّلة ولم تُسحب بعد). */
    public function chequeBalance(int $driverUserId): float
    {
        return round(
            $this->totalChequeCollections($driverUserId)
            - $this->totalChequeHandedOver($driverUserId),
            4
        );
    }

    /** مبيعات نقدية اليوم للسائق. */
    public function cashSalesForDate(int $driverUserId, string $date): float
    {
        return (float) Sale::query()
            ->where('driver_user_id', $driverUserId)
            ->where('payment_type', SalePaymentType::Cash->value)
            ->whereDate('sale_date', $date)
            ->sum('total_amount');
    }

    /** مبيعات على الحساب اليوم للسائق. */
    public function creditSalesForDate(int $driverUserId, string $date): float
    {
        return (float) Sale::query()
            ->where('driver_user_id', $driverUserId)
            ->where('payment_type', SalePaymentType::Credit->value)
            ->whereDate('sale_date', $date)
            ->sum('total_amount');
    }

    /**
     * سحب مبلغ من صندوق السائق إلى الصندوق الرئيسي (كاش أو شيك).
     * الكاش يُخصم من الرصيد النقدي، والشيك من رصيد الشيكات.
     */
    public function handOver(
        int $driverUserId,
        float $amount,
        CollectionMethod $method = CollectionMethod::Cash,
        ?int $receivedByUserId = null,
        ?string $notes = null,
        ?string $chequeNumber = null,
    ): CashHandover {
        if ($amount <= 0) {
            throw new RuntimeException('المبلغ يجب أن يكون أكبر من صفر.');
        }

        $available = $method === CollectionMethod::Cash
            ? $this->balance($driverUserId)
            : $this->chequeBalance($driverUserId);

        if ($amount > $available + 0.0001) {
            throw new RuntimeException(
                $method === CollectionMethod::Cash
                    ? 'المبلغ أكبر من الرصيد النقدي لدى السائق.'
                    : 'المبلغ أكبر من رصيد الشيكات لدى السائق.'
            );
        }

        return CashHandover::create([
            'driver_user_id' => $driverUserId,
            'amount' => $amount,
            'currency_code' => self::CURRENCY,
            'method' => $method,
            'cheque_number' => $method === CollectionMethod::Cheque ? $chequeNumber : null,
            'handover_date' => Carbon::now()->toDateString(),
            'handed_at' => Carbon::now(),
            'received_by_user_id' => $receivedByUserId,
            'notes' => $notes,
        ]);
    }

    /**
     * رصيد الصندوق الرئيسي حسب العملة (كل ما سُحب من السائقين).
     * يُعيد مصفوفة: ['ILS' => ['cash' => .., 'cheque' => ..]].
     */
    public function mainBoxByCurrency(): array
    {
        $rows = CashHandover::query()
            ->selectRaw('currency_code, method, SUM(amount) as total')
            ->groupBy('currency_code', 'method')
            ->get();

        $out = [];
        foreach ($rows as $row) {
            $cur = $row->currency_code;
            $method = $row->method instanceof CollectionMethod ? $row->method->value : (string) $row->method;
            $out[$cur] ??= ['cash' => 0.0, 'cheque' => 0.0];
            $out[$cur][$method] = (float) $row->total;
        }

        return $out;
    }
}
