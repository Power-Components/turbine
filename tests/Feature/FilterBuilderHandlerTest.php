<?php

use PowerComponents\Turbine\{Column, Fields};
use PowerComponents\Turbine\Components\Filters\{FilterInputText, FilterNumber};
use PowerComponents\Turbine\Components\SetUp\FilterBuilder as FilterBuilderSetUp;
use PowerComponents\Turbine\Plugins\FilterBuilder\FilterBuilderHandler;
use PowerComponents\Turbine\Support\State\{ArrayGridContext, State};
use PowerComponents\Turbine\Tests\Fixtures\Models\Dish;

function makeFilterBuilderContext(array $fbState = []): ArrayGridContext
{
    return new ArrayGridContext(
        state: State::fromArray([
            'primaryKey' => 'id',
            'tableName' => 'dishes',
            'setUp' => [
                'filterBuilder' => (new FilterBuilderSetUp())->match('and')->maxConditions(10),
            ],
            'filterBuilder' => $fbState,
        ]),
        datasourceResolver: fn () => Dish::query(),
        fields: new Fields(),
        columns: [
            Column::add()->title('Name')->field('name')->searchable(),
            Column::add()->title('Price')->field('price')->sortable(),
        ],
        filters: [
            new FilterInputText('name'),
            new FilterNumber('price'),
        ]
    );
}

it('checks if FilterBuilderHandler is active', function () {
    $ctx = makeFilterBuilderContext();
    $handler = new FilterBuilderHandler($ctx);

    expect($handler->isActive())->toBeTrue();
});

it('applies FilterBuilder rows to Eloquent query and Collection', function () {
    $fbState = [
        'match' => 'and',
        'rows' => [
            ['column' => 'name', 'operator' => 'contains', 'value' => 'Peixe', 'boolean' => 'and'],
            ['column' => 'price', 'operator' => 'greater_equal', 'value' => 10, 'boolean' => 'and'],
        ],
    ];

    $ctx = makeFilterBuilderContext($fbState);
    $handler = new FilterBuilderHandler($ctx);

    $query = Dish::query();
    $handler->apply($query);

    expect($query->toSql())->toContain('where');

    $collection = collect([
        ['id' => 1, 'name' => 'Peixe Assado', 'price' => 25.0],
        ['id' => 2, 'name' => 'Peixe Frito', 'price' => 5.0],
        ['id' => 3, 'name' => 'Pastel', 'price' => 15.0],
    ]);

    $filtered = $handler->applyCollection($collection);

    expect($filtered)->toHaveCount(1)
        ->and($filtered->first()['id'])->toBe(1);
});

it('handles OR boolean grouping in applyCollection', function () {
    $fbState = [
        'match' => 'or',
        'rows' => [
            ['column' => 'name', 'operator' => 'contains', 'value' => 'Peixe', 'boolean' => 'and'],
            ['column' => 'name', 'operator' => 'contains', 'value' => 'Pastel', 'boolean' => 'or'],
        ],
    ];

    $ctx = makeFilterBuilderContext($fbState);
    $handler = new FilterBuilderHandler($ctx);

    $collection = collect([
        ['id' => 1, 'name' => 'Peixe Assado', 'price' => 25.0],
        ['id' => 2, 'name' => 'Pastel de Queijo', 'price' => 10.0],
        ['id' => 3, 'name' => 'Sopa', 'price' => 12.0],
    ]);

    $filtered = $handler->applyCollection($collection);

    expect($filtered)->toHaveCount(2);
});
