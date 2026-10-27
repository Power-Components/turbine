<?php

namespace PowerComponents\Turbine\Tests\Feature;

use Illuminate\Support\Collection;
use PowerComponents\Turbine\Components\Filters\{FilterBoolean, FilterInputText, FilterMultiSelect, FilterNumber, FilterSelect};
use PowerComponents\Turbine\DataSource\ProcessDataSource;
use PowerComponents\Turbine\DataSource\Processors\Collection\Pipelines\Filters as CollectionFilters;

/**
 * Fixture (dishes): Sushi 30 in-stock, Pastel de Nata 10 in-stock,
 * Pastel de Belém 20 out, Pizza 45 in-stock, Coxinha 8 out.
 */
function pgFilter(string $type, string $field, mixed $value, array $filters): int
{
    $context = turbineContext(
        statePayload: ['filters' => [$field => ['type' => $type, 'value' => $value]]],
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

it('reads a filter record with the type its definition declares', function () {
    expect(pgFilter('number', 'name', 'Sushi', [new FilterSelect('name')]))->toBe(1)
        ->and(pgFilter('select', 'price', ['start' => 30], [new FilterNumber('price')]))->toBe(2);
});

it('falls back to contains when the request operator is not allowed', function () {
    $filter = (new FilterInputText('name'))->operators(['contains']);

    $context = turbineContext(
        statePayload: ['filters' => ['name' => ['type' => 'input_text', 'value' => 'Pastel', 'op' => 'is']]],
        filters: [$filter],
    );

    expect(ProcessDataSource::make($context)->get()['results']->total())->toBe(2);

    $rows = collect([
        ['id' => 1, 'name' => 'Sushi'],
        ['id' => 2, 'name' => 'Sushi Roll'],
    ]);
    $collectionContext = turbineContext(
        statePayload: ['filters' => ['name' => ['type' => 'input_text', 'value' => 'Sushi', 'op' => 'is']]],
        datasource: fn () => $rows,
        filters: [$filter],
    );

    $filtered = (new CollectionFilters($collectionContext))->handle($rows, fn (Collection $collection) => $collection);

    expect($filtered)->toHaveCount(2);
});

it('ignores a keyed filter value the declared type does not take', function () {
    expect(pgFilter('select', 'name', ['price' => 10], [new FilterInputText('name')]))->toBe(5)
        ->and(pgFilter('select', 'name', ['price' => 10], [new FilterSelect('name')]))->toBe(5)
        ->and(pgFilter('boolean', 'in_stock', ['price' => 10], [new FilterBoolean('in_stock')]))->toBe(5);
});
