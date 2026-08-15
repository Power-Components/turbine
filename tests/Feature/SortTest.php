<?php

use PowerComponents\Turbine\DataSource\ProcessDataSource;
use PowerComponents\Turbine\Tests\Fixtures\Models\Dish;

/** @return list<string> */
function pgSortedNames(array $statePayload): array
{
    return ProcessDataSource::make(turbineContext($statePayload))
        ->get()['results']
        ->getCollection()
        ->pluck('name')
        ->all();
}

it('orders ascending by a text column headlessly', function () {
    $names = pgSortedNames(['sortField' => 'name', 'sortDirection' => 'asc']);

    expect($names[0])->toBe('Coxinha')
        ->and($names)->toBe(['Coxinha', 'Pastel de Belém', 'Pastel de Nata', 'Pizza', 'Sushi']);
});

it('applies multi-sort (in_stock asc, price desc) headlessly', function () {
    $names = pgSortedNames([
        'multiSort' => true,
        'sortArray' => ['in_stock' => 'asc', 'price' => 'desc'],
    ]);

    // out-of-stock first (price desc within group), then in-stock (price desc)
    expect($names)->toBe(['Pastel de Belém', 'Coxinha', 'Pizza', 'Sushi', 'Pastel de Nata']);
});

it('ignores an undeclared sort field (mass-assignment guard) headlessly', function () {
    $result = ProcessDataSource::make(turbineContext(['sortField' => 'evil_column']))->get()['results'];

    expect($result->total())->toBe(Dish::query()->count());
});
