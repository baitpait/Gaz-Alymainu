<?php

namespace App\Enums;

/**
 * نوع المخزن: ثابت (مستودع) أو سيارة (مخزن متحرك للسائق).
 * السيارة تُحمَّل من المخزن الثابت، ويرجع المتبقّي إليها.
 */
enum WarehouseType: string
{
    case Fixed = 'fixed';
    case Vehicle = 'vehicle';

    public function label(): string
    {
        return match ($this) {
            self::Fixed => 'مخزن ثابت',
            self::Vehicle => 'سيارة (مخزن متحرك)',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return [
            self::Fixed->value => self::Fixed->label(),
            self::Vehicle->value => self::Vehicle->label(),
        ];
    }
}
