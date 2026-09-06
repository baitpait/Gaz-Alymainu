<?php

namespace App\Services;

use App\Enums\CollectionMethod;
use App\Enums\DriverExpenseCategory;
use App\Enums\SalePaymentType;
use App\Models\CashHandover;
use App\Models\ClientPayment;
use App\Models\Collection;
use App\Models\DriverExpense;
use App\Models\Expense;
use App\Models\Sale;
use App\Models\SalaryPayment;
use App\Models\SupplierPayment;
use App\Services\Finance\PaymentMethod;
use App\Support\AppDateTime;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as SupportCollection;
use RuntimeException;

/**
 * صندوق السائق: يتجمّع من المبيعات النقدية والتحصيل النقدي، ويُسحب منه (للصندوق الرئيسي)
 * وتُخصم منه مصروفات السائق النقدية.
 * الرصيد النقدي = (مبيعات نقدية + تحصيل نقدي) − سحب نقدي − مصروفات السائق.
 * الشيكات تُوثَّق منفصلة ولا تدخل الرصيد النقدي للسائق.
 *
 * الصندوق الرئيسي (أدمن): دفتر فعلي للكاش والشيكات —
 * دخول = سحوبات السائقين + دفعات العملاء (كاش/شيك)
 * خروج = دفعات موردين + رواتب مدفوعة (كاش/شيك) + مصروفات الشركة (نقد افتراضياً).
 * bank/transfer لا يدخلان صندوق الكاش/الشيك المادي.
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
            'cheque_number' => $method === CollectionMethod::Cheque ? $this->normalizeOptionalText($chequeNumber) : null,
            'collection_date' => AppDateTime::today(),
            'collected_at' => Carbon::now(),
            'recorded_by_user_id' => $recordedByUserId,
            'notes' => $this->normalizeOptionalText($notes),
        ]);
    }

    /**
     * Business Purpose: Correct amount/method/notes on a driver collection.
     * Reducing cash or cheque contribution is blocked if that money was already handed over or spent.
     */
    public function updateCollection(
        Collection $collection,
        float $amount,
        CollectionMethod $method,
        ?string $notes = null,
        ?string $chequeNumber = null,
    ): Collection {
        if ($collection->trashed()) {
            throw new RuntimeException('هذا التحصيل محذوف ولا يمكن تعديله.');
        }

        if ($amount <= 0) {
            throw new RuntimeException('المبلغ يجب أن يكون أكبر من صفر.');
        }

        $driverUserId = (int) $collection->driver_user_id;
        $oldMethod = $collection->method instanceof CollectionMethod
            ? $collection->method
            : CollectionMethod::from((string) $collection->method);
        $oldAmount = (float) $collection->amount;

        $this->assertCollectionBoxAllowsDelta(
            $driverUserId,
            $this->boxDecrease(
                $this->methodContribution($oldMethod, $oldAmount, CollectionMethod::Cash),
                $this->methodContribution($method, $amount, CollectionMethod::Cash),
            ),
            $this->boxDecrease(
                $this->methodContribution($oldMethod, $oldAmount, CollectionMethod::Cheque),
                $this->methodContribution($method, $amount, CollectionMethod::Cheque),
            ),
        );

        $collection->amount = $amount;
        $collection->method = $method;
        $collection->notes = $this->normalizeOptionalText($notes);
        $collection->cheque_number = $method === CollectionMethod::Cheque
            ? $this->normalizeOptionalText($chequeNumber)
            : null;
        $collection->save();

        return $collection->refresh();
    }

    /**
     * Business Purpose: Soft-delete a collection so driver cashbox and market debt exclude it.
     * Blocked when the collected cash/cheque is no longer sitting in the driver’s box.
     */
    public function voidCollection(Collection $collection): void
    {
        if ($collection->trashed()) {
            throw new RuntimeException('هذا التحصيل محذوف مسبقاً.');
        }

        $method = $collection->method instanceof CollectionMethod
            ? $collection->method
            : CollectionMethod::from((string) $collection->method);
        $amount = (float) $collection->amount;

        $this->assertCollectionBoxAllowsDelta(
            (int) $collection->driver_user_id,
            $this->methodContribution($method, $amount, CollectionMethod::Cash),
            $this->methodContribution($method, $amount, CollectionMethod::Cheque),
        );

        $collection->delete();
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
            'expense_date' => AppDateTime::today(),
            'spent_at' => Carbon::now(),
            'recorded_by_user_id' => $recordedByUserId,
            'notes' => $notes,
        ]);
    }

    /**
     * Business Purpose: Correct amount/category/notes on a driver expense.
     * Increasing the amount is allowed only within the driver’s remaining cash balance.
     */
    public function updateExpense(
        DriverExpense $expense,
        float $amount,
        DriverExpenseCategory $category,
        ?string $notes = null,
    ): DriverExpense {
        if ($expense->trashed()) {
            throw new RuntimeException('هذا المصروف محذوف ولا يمكن تعديله.');
        }

        if ($amount <= 0) {
            throw new RuntimeException('المبلغ يجب أن يكون أكبر من صفر.');
        }

        $notes = $notes !== null ? trim($notes) : null;
        if ($notes === '') {
            $notes = null;
        }

        $increase = round($amount - (float) $expense->amount, 4);
        if ($increase > 0.0001) {
            $available = $this->balance((int) $expense->driver_user_id);
            if ($increase > $available + 0.0001) {
                throw new RuntimeException('الزيادة أكبر من الرصيد النقدي المتاح لدى السائق.');
            }
        }

        $expense->amount = $amount;
        $expense->category = $category;
        $expense->notes = $notes;
        $expense->save();

        return $expense->refresh();
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
            'handover_date' => AppDateTime::today(),
            'handed_at' => Carbon::now(),
            'received_by_user_id' => $receivedByUserId,
            'notes' => $notes,
        ]);
    }

    /**
     * رصيد الصندوق الرئيسي حسب العملة (كاش/شيك بعد الدخول والخروج).
     * يُعيد: ['ILS' => ['cash' => .., 'cheque' => ..]].
     *
     * @return array<string, array{cash: float, cheque: float}>
     */
    public function mainBoxByCurrency(): array
    {
        $out = [];

        foreach ($this->mainBoxLedger() as $event) {
            $cur = $event['currency'];
            $bucket = $event['bucket'];
            if ($bucket === null) {
                continue;
            }
            $out[$cur] ??= ['cash' => 0.0, 'cheque' => 0.0];
            $out[$cur][$bucket] = round($out[$cur][$bucket] + $event['signed_amount'], 4);
        }

        foreach ($out as $cur => $buckets) {
            $out[$cur]['cash'] = round($buckets['cash'], 4);
            $out[$cur]['cheque'] = round($buckets['cheque'], 4);
        }

        return $out;
    }

    /**
     * دفتر حركات الصندوق الرئيسي (دخول/خروج) مرتّب زمنياً تنازلياً.
     *
     * @return SupportCollection<int, array{
     *   sort: string,
     *   at: ?\Illuminate\Support\Carbon,
     *   type: string,
     *   type_label: string,
     *   party: string,
     *   currency: string,
     *   method: ?string,
     *   bucket: ?string,
     *   amount: float,
     *   signed_amount: float,
     *   reference: string,
     *   notes: ?string
     * }>
     */
    public function mainBoxLedger(?int $limit = null): SupportCollection
    {
        $events = collect();

        foreach (CashHandover::query()->with('driver')->get() as $row) {
            $bucket = $this->boxBucketFromGasMethod($row->method);
            $events->push([
                'sort' => ($row->handed_at?->format('Y-m-d H:i:s') ?? $row->handover_date.' 00:00:00').'_handover_'.$row->id,
                'at' => $row->handed_at ?? Carbon::parse($row->handover_date),
                'type' => 'driver_handover',
                'type_label' => $bucket === 'cheque' ? 'سحب شيك من سائق' : 'سحب نقد من سائق',
                'party' => $row->driver?->full_name ?? '—',
                'currency' => $row->currency_code,
                'method' => $bucket === 'cheque' ? PaymentMethod::CHECK : PaymentMethod::CASH,
                'bucket' => $bucket,
                'amount' => (float) $row->amount,
                'signed_amount' => (float) $row->amount,
                'reference' => '#'.$row->id.($row->cheque_number ? ' / '.$row->cheque_number : ''),
                'notes' => $row->notes,
            ]);
        }

        foreach (ClientPayment::query()->with('client')->get() as $row) {
            $bucket = $this->boxBucketFromErpMethod($row->method);
            if ($bucket === null) {
                continue;
            }
            $events->push([
                'sort' => ($row->paid_at?->format('Y-m-d H:i:s') ?? '1970-01-01 00:00:00').'_client_'.$row->id,
                'at' => $row->paid_at,
                'type' => 'client_payment',
                'type_label' => $bucket === 'cheque' ? 'دفعة عميل (شيك)' : 'دفعة عميل (نقد)',
                'party' => $row->client?->displayName() ?? '—',
                'currency' => $row->currency_code,
                'method' => PaymentMethod::normalize($row->method),
                'bucket' => $bucket,
                'amount' => (float) $row->amount,
                'signed_amount' => (float) $row->amount,
                'reference' => $row->bank_reference ?: '#'.$row->id,
                'notes' => $row->notes,
            ]);
        }

        foreach (SupplierPayment::query()->with('supplier')->get() as $row) {
            $bucket = $this->boxBucketFromErpMethod($row->method);
            if ($bucket === null) {
                continue;
            }
            $events->push([
                'sort' => ($row->paid_at?->format('Y-m-d H:i:s') ?? '1970-01-01 00:00:00').'_supplier_'.$row->id,
                'at' => $row->paid_at,
                'type' => 'supplier_payment',
                'type_label' => $bucket === 'cheque' ? 'دفع مورد (شيك)' : 'دفع مورد (نقد)',
                'party' => $row->supplier?->displayName() ?? '—',
                'currency' => $row->currency_code,
                'method' => PaymentMethod::normalize($row->method),
                'bucket' => $bucket,
                'amount' => (float) $row->amount,
                'signed_amount' => -1 * (float) $row->amount,
                'reference' => $row->bank_reference ?: '#'.$row->id,
                'notes' => $row->notes,
            ]);
        }

        foreach (
            SalaryPayment::query()
                ->with('employee')
                ->where('status', SalaryPayment::STATUS_PAID)
                ->get() as $row
        ) {
            $bucket = $this->boxBucketFromErpMethod($row->method);
            if ($bucket === null) {
                continue;
            }
            $events->push([
                'sort' => ($row->paid_at?->format('Y-m-d').' 12:00:00' ?? '1970-01-01 00:00:00').'_salary_'.$row->id,
                'at' => $row->paid_at?->copy()->setTime(12, 0),
                'type' => 'salary_payment',
                'type_label' => $bucket === 'cheque' ? 'راتب (شيك)' : 'راتب (نقد)',
                'party' => $row->employee?->full_name ?? '—',
                'currency' => $row->currency_code,
                'method' => PaymentMethod::normalize($row->method),
                'bucket' => $bucket,
                'amount' => (float) $row->net_amount,
                'signed_amount' => -1 * (float) $row->net_amount,
                'reference' => $row->bank_reference ?: '#'.$row->id,
                'notes' => $row->notes,
            ]);
        }

        foreach (Expense::query()->get() as $row) {
            $events->push([
                'sort' => ($row->expense_date?->format('Y-m-d') ?? '1970-01-01').' 08:00:00_expense_'.$row->id,
                'at' => $row->expense_date?->copy()->setTime(8, 0),
                'type' => 'company_expense',
                'type_label' => 'مصروف شركة (نقد)',
                'party' => $row->description,
                'currency' => $row->currency_code,
                'method' => PaymentMethod::CASH,
                'bucket' => 'cash',
                'amount' => (float) $row->amount,
                'signed_amount' => -1 * (float) $row->amount,
                'reference' => '#'.$row->id,
                'notes' => $row->notes,
            ]);
        }

        $sorted = $events->sortByDesc('sort')->values();

        if ($limit !== null) {
            return $sorted->take($limit)->values();
        }

        return $sorted;
    }

    /**
     * Amount this collection currently contributes to a cash or cheque box.
     */
    private function methodContribution(CollectionMethod $method, float $amount, CollectionMethod $bucket): float
    {
        return $method === $bucket ? $amount : 0.0;
    }

    private function boxDecrease(float $oldContribution, float $newContribution): float
    {
        return round($oldContribution - $newContribution, 4);
    }

    /**
     * Reject an edit/void that would push driver cash or cheque balance below zero.
     */
    private function assertCollectionBoxAllowsDelta(int $driverUserId, float $cashDecrease, float $chequeDecrease): void
    {
        if ($cashDecrease > 0.0001 && $cashDecrease > $this->balance($driverUserId) + 0.0001) {
            throw new RuntimeException('لا يمكن تخفيض التحصيل النقدي بعد تسليم أو صرف الكاش المرتبط به.');
        }

        if ($chequeDecrease > 0.0001 && $chequeDecrease > $this->chequeBalance($driverUserId) + 0.0001) {
            throw new RuntimeException('لا يمكن تخفيض تحصيل الشيك بعد تسليم الشيك المرتبط به.');
        }
    }

    private function normalizeOptionalText(?string $value): ?string
    {
        $value = $value !== null ? trim($value) : null;

        return $value === '' ? null : $value;
    }

    /**
     * Map gas handover/collection method (cash|cheque) to main-box bucket.
     */
    private function boxBucketFromGasMethod(mixed $method): string
    {
        $value = $method instanceof CollectionMethod ? $method->value : (string) $method;

        return $value === CollectionMethod::Cheque->value ? 'cheque' : 'cash';
    }

    /**
     * Map ERP payment method to main-box bucket; bank/transfer ignored.
     */
    private function boxBucketFromErpMethod(?string $method): ?string
    {
        return match (PaymentMethod::normalize($method)) {
            PaymentMethod::CASH => 'cash',
            PaymentMethod::CHECK => 'cheque',
            default => null,
        };
    }
}
