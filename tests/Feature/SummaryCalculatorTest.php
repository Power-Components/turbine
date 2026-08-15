<?php

use PowerComponents\Turbine\{Column, Fields};
use PowerComponents\Turbine\DataSource\Summaries\SummaryCalculator;
use PowerComponents\Turbine\Support\State\{ArrayGridContext, State};
use PowerComponents\Turbine\Tests\Fixtures\Models\Dish;

function makeSummaryContext(array $columns = []): ArrayGridContext
{
    return new ArrayGridContext(
        state: State::fromArray([
            'primaryKey' => 'id',
            'tableName' => 'dishes',
        ]),
        datasourceResolver: fn () => Dish::query(),
        fields: new Fields(),
        columns: $columns
    );
}

it('returns empty summary when no summarize property exists in columns', function () {
    $ctx = makeSummaryContext([
        Column::add()->title('Price')->field('price'),
    ]);

    $calculator = new SummaryCalculator($ctx);
    expect($calculator->compute(Dish::query()))->toBe([])
        ->and($calculator->compute(collect()))->toBe([]);
});

it('computes query summaries for sum, count, avg, min, and max', function () {
    $colPrice = Column::add()->title('Price')->field('price');
    $colPrice->properties['summarize'] = [
        'sum' => true,
        'count' => true,
        'avg' => true,
        'min' => true,
        'max' => true,
    ];

    $ctx = makeSummaryContext([$colPrice]);
    $calculator = new SummaryCalculator($ctx);

    $summary = $calculator->compute(Dish::query());

    expect($summary)->toHaveKeys(['price.sum', 'price.count', 'price.avg', 'price.min', 'price.max'])
        ->and($summary['price.count'])->toBeGreaterThan(0);
});

it('computes collection summaries for sum, count, avg, min, and max', function () {
    $colPrice = Column::add()->title('Price')->field('price');
    $colPrice->properties['summarize'] = [
        'sum' => true,
        'count' => true,
        'avg' => true,
        'min' => true,
        'max' => true,
    ];

    $ctx = makeSummaryContext([$colPrice]);
    $calculator = new SummaryCalculator($ctx);

    $collection = collect([
        ['price' => 10],
        ['price' => 20],
        ['price' => 30],
    ]);

    $summary = $calculator->compute($collection);

    expect($summary['price.sum'])->toEqual(60)
        ->and($summary['price.count'])->toBe(3)
        ->and($summary['price.avg'])->toEqual(20)
        ->and($summary['price.min'])->toBe(10)
        ->and($summary['price.max'])->toBe(30);
});

it('supports custom summary closures on collection', function () {
    $colPrice = Column::add()->title('Price')->field('price');
    $colPrice->properties['summarize']['custom']['tax'] = true;
    $colPrice->summaryCallbacks['tax'] = fn ($col) => $col->sum('price') * 0.1;

    $ctx = makeSummaryContext([$colPrice]);
    $calculator = new SummaryCalculator($ctx);

    $collection = collect([
        ['price' => 100],
        ['price' => 200],
    ]);

    $summary = $calculator->compute($collection);

    expect($summary)->toHaveKey('custom.tax')
        ->and($summary['custom.tax'])->toBe(30.0);
});
