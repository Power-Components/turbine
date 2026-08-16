<?php

namespace PowerComponents\Turbine\Tests\Feature;

use PowerComponents\Turbine\{Column, Fields};
use PowerComponents\Turbine\DataSource\ProcessDataSource;
use PowerComponents\Turbine\Support\State\{ArrayGridContext, State};
use PowerComponents\Turbine\Tests\Fixtures\Models\Dish;

/** @param  array<int, Column> $columns */
function summaryContext(array $columns): ArrayGridContext
{
    return new ArrayGridContext(
        state: State::fromArray([
            'primaryKey' => 'id',
            'tableName' => 'dishes',
            'setUp' => ['footer' => ['perPage' => 100, 'pageName' => 'page']],
        ]),
        datasourceResolver: fn () => Dish::query(),
        fields: (new Fields())->add('id')->add('name')->add('price'),
        columns: $columns,
    );
}

function columnWithSummarize(string $field, array $summarize): Column
{
    $column = Column::add()->field($field);
    $column->properties['summarize'] = $summarize;

    return $column;
}

it('detects a built-in aggregate summary', function () {
    $context = summaryContext([columnWithSummarize('price', ['sum' => ['footer' => true]])]);

    expect($context->hasSummarizeInColumns())->toBeTrue();
});

it('detects a custom-only summary (regression: custom must switch the engine on)', function () {
    $context = summaryContext([columnWithSummarize('price', ['custom' => ['avg_price' => ['footer' => true]]])]);

    expect($context->hasSummarizeInColumns())->toBeTrue();
});

it('reports no summary when no column declares one', function () {
    $context = summaryContext([Column::add()->field('price')]);

    expect($context->hasSummarizeInColumns())->toBeFalse();
});

it('computes a sum aggregate headlessly through the engine', function () {
    $context = summaryContext([columnWithSummarize('price', ['sum' => ['footer' => true]])]);

    ProcessDataSource::make($context)->get();

    expect($context->getSummaryValues())
        ->toHaveKey('price.sum')
        ->and((float) $context->getSummaryValues()['price.sum'])
        ->toBe((float) Dish::query()->sum('price'));
});
