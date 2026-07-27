<?php

namespace App\Enums;

/**
 * طريقة دفع التحصيل: نقدي (يدخل صندوق السائق) أو شيك (يُوثَّق منفصلًا).
 */
enum CollectionMethod: string
{
    case Cash = 'cash';
    case Cheque = 'cheque';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'نقدي',
            self::Cheque => 'شيك',
        };
    }
}
