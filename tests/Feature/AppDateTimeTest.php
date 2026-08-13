<?php

use App\Support\AppDateTime;
use Illuminate\Support\Carbon;

test('format converts utc instant to palestine display timezone', function () {
    config([
        'app.timezone' => 'UTC',
        'app.display_timezone' => 'Asia/Jerusalem',
    ]);

    $utc = Carbon::parse('2026-08-13 10:00:00', 'UTC');

    // August = daylight saving (UTC+3) for Asia/Jerusalem
    expect(AppDateTime::format($utc, 'Y-m-d H:i'))->toBe('2026-08-13 13:00');
});

test('today uses palestine calendar date', function () {
    config([
        'app.timezone' => 'UTC',
        'app.display_timezone' => 'Asia/Jerusalem',
    ]);

    expect(AppDateTime::today())->toBe(Carbon::now('Asia/Jerusalem')->toDateString());
});

test('utc day bounds cover the full palestine local day', function () {
    config([
        'app.timezone' => 'UTC',
        'app.display_timezone' => 'Asia/Jerusalem',
    ]);

    [$start, $end] = AppDateTime::utcDayBounds('2026-08-13');

    expect($start->timezone('UTC')->format('Y-m-d H:i:s'))->toBe('2026-08-12 21:00:00')
        ->and($end->timezone('UTC')->format('Y-m-d H:i:s'))->toBe('2026-08-13 20:59:59');
});
