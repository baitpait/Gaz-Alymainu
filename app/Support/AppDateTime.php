<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Business Purpose: Keep timestamps stored/queried in UTC (app.timezone) while
 * presenting and choosing business calendar days in Palestine local time
 * (Jerusalem/Hebron — Asia/Jerusalem).
 */
class AppDateTime
{
    public static function timezone(): string
    {
        return (string) config('app.display_timezone', 'Asia/Jerusalem');
    }

    /** Current instant expressed in the display timezone. */
    public static function now(): Carbon
    {
        return Carbon::now(self::timezone());
    }

    /** Business calendar date (Y-m-d) in Palestine local time. */
    public static function today(): string
    {
        return self::now()->toDateString();
    }

    /**
     * Format a UTC (app-timezone) datetime for UI in the display timezone.
     */
    public static function format(null|CarbonInterface|string $value, string $format = 'Y-m-d H:i'): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        $dt = $value instanceof CarbonInterface
            ? Carbon::instance($value)->copy()
            : Carbon::parse($value, config('app.timezone', 'UTC'));

        return $dt->timezone(self::timezone())->format($format);
    }

    /**
     * Local calendar day as UTC bounds for querying datetime columns stored in UTC.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function utcDayBounds(string $localDate): array
    {
        $start = Carbon::parse($localDate, self::timezone())->startOfDay()->utc();
        $end = Carbon::parse($localDate, self::timezone())->endOfDay()->utc();

        return [$start, $end];
    }

    /** Localized long date for dashboard headers, etc. */
    public static function translatedDate(?string $localDate = null): string
    {
        $dt = $localDate
            ? Carbon::parse($localDate, self::timezone())
            : self::now();

        return $dt->locale(app()->getLocale())->translatedFormat('l، d F Y');
    }
}
