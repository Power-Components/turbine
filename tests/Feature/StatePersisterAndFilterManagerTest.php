<?php

use PowerComponents\Turbine\Column;
use PowerComponents\Turbine\Components\Filters\{FilterManager, FilterSelect};
use PowerComponents\Turbine\Support\State\StatePersister;

it('serializes state and generates correct key name', function () {
    $persister = new StatePersister();

    $key = $persister->getPersistKeyName('users_table', 'custom');
    expect($key)->toContain('pg:custom-users_table');

    $json = $persister->serializeState(
        persistItems: ['columns', 'filters', 'sorting'],
        tableItem: '',
        columns: [Column::make('Name', 'name')],
        filters: ['select' => ['status' => 'active']],
        enabledFilters: [['field' => 'status', 'label' => 'Status']],
        filterBuilder: [],
        sortField: 'name',
        sortDirection: 'asc',
        sortArray: [],
        multiSort: false
    );

    $decoded = json_decode($json, true);

    expect($decoded)->toHaveKeys(['columns', 'filters', 'sortField', 'sortDirection'])
        ->and($decoded)->not->toHaveKey('enabledFilters')
        ->and($decoded['sortField'])->toBe('name')
        ->and($decoded['filters']['select']['status'])->toBe('active');
});

it('applies default filters using FilterManager', function () {
    $manager = new FilterManager();

    $filter = (new FilterSelect('Status', 'status'))
        ->default('active');

    $filters = [];

    $applied = $manager->applyDefaults(
        declaredFilters: [$filter],
        filters: $filters,
    );

    expect($applied)->toBeTrue()
        ->and($filters['status'])->toMatchArray(['type' => 'select', 'value' => 'active']);
});
