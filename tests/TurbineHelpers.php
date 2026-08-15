<?php

use PowerComponents\Turbine\{Column, Fields};
use PowerComponents\Turbine\Components\Filters\FilterBase;
use PowerComponents\Turbine\Support\State\{ArrayGridContext, State};
use PowerComponents\Turbine\Tests\Fixtures\Models\Dish;

/**
 * Shared harness for the headless engine tests: drives the data engine through a
 * plain {@see ArrayGridContext} (no component, no container-bound state).
 */
function turbineFields(): Fields
{
    return (new Fields())
        ->add('id')
        ->add('name')
        ->add('price')
        ->add('in_stock');
}

/** @return array<int, Column> */
function turbineColumns(): array
{
    return [
        Column::add()->title('Id')->field('id')->sortable(),
        Column::add()->title('Name')->field('name')->searchable()->sortable(),
        Column::add()->title('Price')->field('price')->sortable(),
        Column::add()->title('In stock')->field('in_stock'),
    ];
}

/**
 * @param  array<string, mixed>  $statePayload
 * @param  array<int, FilterBase>  $filters
 */
function turbineContext(array $statePayload = [], ?callable $datasource = null, array $filters = []): ArrayGridContext
{
    $state = State::fromArray(array_merge([
        'primaryKey' => 'id',
        'tableName' => 'dishes',
        'sortField' => 'id',
        'setUp' => ['footer' => ['perPage' => 100, 'pageName' => 'page']],
    ], $statePayload));

    return new ArrayGridContext(
        state: $state,
        datasourceResolver: $datasource ?? fn () => Dish::query(),
        fields: turbineFields(),
        columns: turbineColumns(),
        filters: $filters,
    );
}
