<?php

namespace App\Enums;

/**
 * نوع حركة المخزون في سجل الحركات (stock_movements).
 * - PurchaseIn: إدخال مخزون بالشراء (زيادة في مخزن ثابت).
 * - Load: تحميل من مخزن ثابت إلى سيارة.
 * - ReturnToWarehouse: إرجاع المتبقّي من السيارة إلى مخزن ثابت.
 * - Adjustment: تسوية يدوية (جرد / تصحيح).
 */
enum StockMovementType: string
{
    case PurchaseIn = 'purchase_in';
    case Load = 'load';
    case ReturnToWarehouse = 'return';
    case Transfer = 'transfer';
    case SaleOut = 'sale_out';
    case Adjustment = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::PurchaseIn => 'إدخال بالشراء',
            self::Load => 'تحميل إلى سيارة',
            self::ReturnToWarehouse => 'إرجاع إلى المخزن',
            self::Transfer => 'تحويل بين المخازن',
            self::SaleOut => 'خروج بالبيع',
            self::Adjustment => 'تسوية جرد',
        };
    }

    /**
     * الأنواع المتاحة يدويًا في نموذج الحركات (البيع يُسجَّل من نقطة البيع).
     *
     * @return array<string, string>
     */
    public static function manualOptions(): array
    {
        return [
            self::PurchaseIn->value => self::PurchaseIn->label(),
            self::Load->value => self::Load->label(),
            self::ReturnToWarehouse->value => self::ReturnToWarehouse->label(),
            self::Transfer->value => self::Transfer->label(),
            self::Adjustment->value => self::Adjustment->label(),
        ];
    }
}
