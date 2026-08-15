<?php

use PowerComponents\Turbine\Components\Filters\{FilterBoolean, FilterMultiSelect, FilterNumber, FilterSelect};
use PowerComponents\Turbine\DataSource\ProcessDataSource;

/**
 * Fixture (dishes): Sushi 30 in-stock, Pastel de Nata 10 in-stock,
 * Pastel de Belém 20 out, Pizza 45 in-stock, Coxinha 8 out.
 */
function pgFilter(string $type, string $field, mixed $value, array $filters): int
{
    $context = turbineContext(
        statePayload: ['filters' => [$type => [$field => $value]]],
        filters: $filters,
    );

    return ProcessDataSource::make($context)->get()['results']->total();
}

it('applies a boolean filter headlessly', function () {
    expect(pgFilter('boolean', 'in_stock', 'true', [new FilterBoolean('in_stock')]))->toBe(3)
        ->and(pgFilter('boolean', 'in_stock', 'false', [new FilterBoolean('in_stock')]))->toBe(2);
});

it('applies a select filter headlessly', function () {
    expect(pgFilter('select', 'name', 'Sushi', [new FilterSelect('name')]))->toBe(1);
});

it('applies a number range filter headlessly', function () {
    expect(pgFilter('number', 'price', ['start' => 10, 'end' => 20], [new FilterNumber('price')]))->toBe(2);
});

it('applies an open-ended number filter headlessly', function () {
    expect(pgFilter('number', 'price', ['start' => 30], [new FilterNumber('price')]))->toBe(2);
});

it('applies a multi-select filter headlessly', function () {
    expect(pgFilter('multi_select', 'name', ['Sushi', 'Pizza'], [new FilterMultiSelect('name')]))->toBe(2);
});

it('ignores a filter type whose field is not declared', function () {
    $total = pgFilter('select', 'name', 'Sushi', [new FilterSelect('price')]);

    expect($total)->toBe(5);
});
