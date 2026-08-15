<?php

use Illuminate\Pagination\Paginator;
use PowerComponents\Turbine\DataSource\ProcessDataSource;

it('respects perPage headlessly', function () {
    $paginator = ProcessDataSource::make(
        turbineContext(['setUp' => ['footer' => ['perPage' => 2, 'pageName' => 'page']]])
    )->get()['results'];

    expect($paginator->total())->toBe(5)
        ->and($paginator->perPage())->toBe(2)
        ->and($paginator->lastPage())->toBe(3)
        ->and($paginator->count())->toBe(2);
});

it('returns the requested page headlessly', function () {
    Paginator::currentPageResolver(fn () => 2);

    $paginator = ProcessDataSource::make(
        turbineContext(['setUp' => ['footer' => ['perPage' => 2, 'pageName' => 'page']]])
    )->get()['results'];

    expect($paginator->currentPage())->toBe(2)
        ->and($paginator->getCollection()->pluck('id')->all())->toBe([3, 4]);
});

it('clamps an unbounded page size to the configured max_per_page', function () {
    config()->set('turbine.max_per_page', 2);

    $paginator = ProcessDataSource::make(
        turbineContext(['setUp' => ['footer' => ['perPage' => 0, 'pageName' => 'page']]])
    )->get()['results'];

    expect($paginator->perPage())->toBe(2);
});
