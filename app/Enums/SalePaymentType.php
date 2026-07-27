<?php

namespace App\Enums;

/**
 * طريقة دفع البيع: نقدي (يدخل صندوق السائق) أو على الحساب (بلا اسم زبون).
 */
enum SalePaymentType: string
{
    case Cash = 'cash';
    case Credit = 'credit';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'نقدي',
            self::Credit => 'على الحساب',
        };
    }
}
