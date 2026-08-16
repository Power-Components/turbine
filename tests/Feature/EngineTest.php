<?php

namespace PowerComponents\Turbine\Tests\Feature;

use Illuminate\Pagination\LengthAwarePaginator;
use PowerComponents\Turbine\Components\Filters\FilterInputText;
use PowerComponents\Turbine\DataSource\ProcessDataSource;
use PowerComponents\Turbine\Tests\Fixtures\Models\Dish;

/**
 * Exercises the data engine (search / filter / sort / paginate) through a plain
 * PHP ArrayGridContext (see TurbineHelpers) — no component, no container-bound
 * state. Proves the engine runs headless against Eloquent and Collection
 * datasources alike.
 */
it('paginates an eloquent datasource headlessly', function () {
    $context = turbineContext();

    $result = ProcessDataSource::make($context)->get();
    $paginator = $result['results'];

    expect($paginator)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($paginator->total())->toBe(Dish::query()->count())
        ->and($context->getCurrentTable())->toBe('dishes');
});

it('applies global search headlessly', function () {
    $paginator = ProcessDataSource::make(turbineContext(['search' => 'Pastel']))->get()['results'];

    expect($paginator->total())->toBe(2)
        ->and($paginator->getCollection()->pluck('name')->first())->toContain('Pastel');
});

it('orders results headlessly', function () {
    $paginator = ProcessDataSource::make(
        turbineContext(['sortField' => 'price', 'sortDirection' => 'desc'])
    )->get()['results'];

    expect((float) $paginator->getCollection()->first()->price)->toBe((float) Dish::query()->max('price'));
});

it('applies a column filter headlessly', function () {
    $context = turbineContext(
        statePayload: ['filters' => ['input_text' => ['name' => 'Pastel']]],
        filters: [new FilterInputText('name')],
    );

    $paginator = ProcessDataSource::make($context)->get()['results'];

    expect($paginator->total())->toBe(2);
});

it('runs search and sort over a collection datasource headlessly', function () {
    $rows = collect([
        ['id' => 1, 'name' => 'Sushi', 'price' => 30.0],
        ['id' => 2, 'name' => 'Pastel de Nata', 'price' => 10.0],
        ['id' => 3, 'name' => 'Pastel de Belém', 'price' => 20.0],
    ]);

    $result = ProcessDataSource::make(turbineContext(
        statePayload: ['search' => 'Pastel', 'sortField' => 'price', 'sortDirection' => 'asc'],
        datasource: fn () => $rows,
    ))->get();
    $collection = $result['results']->getCollection();

    expect($result['results']->total())->toBe(2)
        ->and($collection->pluck('name')->all())->toBe(['Pastel de Nata', 'Pastel de Belém']);
});

it('ignores an undeclared filter field (mass-assignment guard) headlessly', function () {
    $context = turbineContext(
        statePayload: ['filters' => ['input_text' => ['price' => '10']]],
        filters: [new FilterInputText('name')],
    );

    $paginator = ProcessDataSource::make($context)->get()['results'];

    expect($paginator->total())->toBe(Dish::query()->count());
});
