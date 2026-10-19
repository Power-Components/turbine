<?php

use PowerComponents\Turbine\Support\FilterValue;

it('keeps only the date bounds that are present', function () {
    expect(FilterValue::dateRange(['start' => '2024-01-01', 'end' => '2024-02-01']))
        ->toBe(['start' => '2024-01-01', 'end' => '2024-02-01'])
        ->and(FilterValue::dateRange(['formatted' => '2024-01-01 to 2024-02-01']))->toBe([])
        ->and(FilterValue::dateRange(['start' => '', 'end' => null]))->toBe([]);
});

it('keeps number bounds in their own type and drops the rest', function () {
    expect(FilterValue::numberRange(['start' => 10, 'end' => '20.5']))
        ->toBe(['start' => 10, 'end' => '20.5'])
        ->and(FilterValue::numberRange(['start' => ['nested'], 'end' => 20]))->toBe(['end' => 20]);
});

it('hands selected values to the builder in the type they came in', function () {
    expect(FilterValue::items([1, '2', 3.5]))->toBe([1, '2', 3.5])
        ->and(FilterValue::items([1, ['nested'], null]))->toBe([1]);
});

it('narrows anything that is not a usable scalar to null', function () {
    expect(FilterValue::map(new stdClass()))->toBeNull()
        ->and(FilterValue::items(new stdClass()))->toBeNull()
        ->and(FilterValue::map(true))->toBe('true')
        ->and(FilterValue::map(false))->toBe('false')
        ->and(FilterValue::text(['a']))->toBe('')
        ->and(FilterValue::text(10))->toBe('10');
});
