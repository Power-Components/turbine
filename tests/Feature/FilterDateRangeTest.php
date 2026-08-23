<?php

namespace PowerComponents\Turbine\Tests\Feature;

use Illuminate\Support\Carbon;
use PowerComponents\Turbine\Support\FilterDateRange;

it('derives start and end from a formatted range for date type', function () {
    config()->set('app.timezone', 'UTC');

    $range = FilterDateRange::compute('date', '2026-01-01 to 2026-01-31');

    expect($range['formatted'])->toBe('2026-01-01 to 2026-01-31')
        ->and(Carbon::parse($range['start'])->format('Y-m-d H:i:s'))->toBe('2026-01-01 00:00:00')
        ->and(Carbon::parse($range['end'])->format('Y-m-d H:i:s'))->toBe('2026-01-31 23:59:59');
});

it('treats a single date as a same-day range', function () {
    config()->set('app.timezone', 'UTC');

    $range = FilterDateRange::compute('date', '2026-02-10');

    expect(Carbon::parse($range['start'])->format('Y-m-d H:i:s'))->toBe('2026-02-10 00:00:00')
        ->and(Carbon::parse($range['end'])->format('Y-m-d H:i:s'))->toBe('2026-02-10 23:59:59');
});

it('keeps the picked time for datetime type', function () {
    config()->set('app.timezone', 'UTC');

    $range = FilterDateRange::compute('datetime', '2026-01-01 08:30 to 2026-01-02 17:45');

    expect(Carbon::parse($range['start'])->format('Y-m-d H:i'))->toBe('2026-01-01 08:30')
        ->and(Carbon::parse($range['end'])->format('Y-m-d H:i'))->toBe('2026-01-02 17:45');
});
