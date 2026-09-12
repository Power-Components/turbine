<?php

use PowerComponents\Turbine\Support\FilterBag;

it('keys the bag by column when the SQL field contains a dot', function () {
    expect(FilterBag::bagKey('name', 'dishes.name'))->toBe('name')
        ->and(FilterBag::bagKey('status', 'status'))->toBe('status')
        ->and(FilterBag::bagKey('Status', 'status'))->toBe('status');
});

it('treats valueless operators as active even with an empty value', function () {
    expect(FilterBag::isActive(FilterBag::record('input_text', null, 'is_empty')))->toBeTrue()
        ->and(FilterBag::isActive(FilterBag::record('input_text', '', 'contains')))->toBeFalse()
        ->and(FilterBag::isActive(FilterBag::record('boolean', 'all')))->toBeFalse()
        ->and(FilterBag::isActive(FilterBag::record('input_text', null, 'contains')))->toBeFalse();
});
