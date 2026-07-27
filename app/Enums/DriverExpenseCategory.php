<?php

namespace App\Enums;

/**
 * تصنيف مصروف السائق/السيارة.
 */
enum DriverExpenseCategory: string
{
    case Fuel = 'fuel';
    case Maintenance = 'maintenance';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Fuel => 'وقود',
            self::Maintenance => 'صيانة',
            self::Other => 'أخرى',
        };
    }

    /**
     * خيارات القائمة المنسدلة: [value => label].
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $case) {
            $out[$case->value] = $case->label();
        }

        return $out;
    }
}
