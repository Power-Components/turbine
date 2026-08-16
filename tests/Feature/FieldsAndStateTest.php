<?php

namespace PowerComponents\Turbine\Tests\Feature;

use Illuminate\Http\Request;
use PowerComponents\Turbine\{Column, Fields};
use PowerComponents\Turbine\Components\Filters\FilterInputText;
use PowerComponents\Turbine\Support\State\{ArrayGridContext, State};

it('adds fields to Fields repository with custom and default closures', function () {
    $fields = new Fields();

    $fields->add('name');
    $fields->add('formatted_price', fn ($model) => '$'.number_format($model['price'], 2));

    expect($fields->fields)->toHaveKeys(['name', 'formatted_price']);

    $row = ['name' => 'Widget', 'price' => 10.5];

    $nameClosure = $fields->fields['name'];
    $priceClosure = $fields->fields['formatted_price'];

    expect($nameClosure($row))->toBe('Widget')
        ->and($priceClosure($row))->toBe('$10.50');
});

it('supports macros in Fields', function () {
    Fields::macro('customMacro', fn () => 'macro_result');

    $fields = new Fields();
    expect($fields->customMacro())->toBe('macro_result');
});

it('instantiates State from array with default and custom values', function () {
    $state = State::fromArray([
        'search' => 'laptop',
        'sortField' => 'price',
        'sortDirection' => 'desc',
        'multiSort' => true,
        'sortArray' => ['price' => 'desc', 'name' => 'asc'],
        'filters' => ['status' => 'active'],
        'primaryKey' => 'uuid',
        'primaryKeyAlias' => 'dishes.uuid',
        'tableName' => 'dishes',
    ]);

    expect($state->search)->toBe('laptop')
        ->and($state->sortField)->toBe('price')
        ->and($state->sortDirection)->toBe('desc')
        ->and($state->multiSort)->toBeTrue()
        ->and($state->sortArray)->toBe(['price' => 'desc', 'name' => 'asc'])
        ->and($state->filters)->toBe(['status' => 'active'])
        ->and($state->primaryKey)->toBe('uuid')
        ->and($state->primaryKeyAlias)->toBe('dishes.uuid')
        ->and($state->realPrimaryKey())->toBe('dishes.uuid')
        ->and($state->tableName)->toBe('dishes');
});

it('creates State from Request object', function () {
    $request = Request::create('/grid', 'GET', [
        'search' => 'phone',
        'sortField' => 'created_at',
        'turbine' => [
            'tableName' => 'products',
            'filters' => ['category' => 'tech'],
        ],
    ]);

    $state = State::fromRequest($request);

    expect($state->search)->toBe('phone')
        ->and($state->sortField)->toBe('created_at')
        ->and($state->tableName)->toBe('products')
        ->and($state->filters)->toBe(['category' => 'tech']);
});

it('interacts with ArrayGridContext methods', function () {
    $state = new State(tableName: 'users');
    $fields = new Fields();
    $filter = new FilterInputText('name');
    $columns = [Column::make('Name', 'name')];

    $context = new ArrayGridContext(
        state: $state,
        datasourceResolver: fn () => ['user1', 'user2'],
        fields: $fields,
        columns: $columns,
        filters: [$filter],
        relationSearch: ['user' => 'name'],
        searchMorphs: ['imageable' => 'title'],
        actionsResolver: fn ($row) => ['action_button'],
        actionRulesResolver: fn ($row) => ['rule_1']
    );

    expect($context->state())->toBe($state)
        ->and($context->fields())->toBe($fields)
        ->and($context->datasource())->toBe(['user1', 'user2'])
        ->and($context->declaredColumns())->toBe($columns)
        ->and($context->hasResolvedColumns())->toBeTrue()
        ->and($context->declaredFilters())->toBe([$filter])
        ->and($context->relationSearch())->toBe(['user' => 'name'])
        ->and($context->searchMorphs())->toBe(['imageable' => 'title'])
        ->and($context->actions((object) []))->toBe(['action_button'])
        ->and($context->actionRules((object) []))->toBe(['rule_1'])
        ->and($context->shouldCollectActions())->toBeFalse()
        ->and($context->summariesCacheTag())->toBe('turbine-headless-users')
        ->and($context->transformQuery('base_query'))->toBe('base_query')
        ->and($context->beforeFilterBuilderApply('base_query', []))->toBe('base_query')
        ->and($context->applyBeforeSearch('field', 'term'))->toBe('term');

    $context->setCurrentTable('users_table');
    expect($context->getCurrentTable())->toBe('users_table');

    $context->setFilteredKeys([1, 2, 3]);
    expect($context->getFilteredKeys())->toBe([1, 2, 3]);

    $context->setSummaryValues(['sum' => 100]);
    expect($context->getSummaryValues())->toBe(['sum' => 100]);
});

it('supports delayed closure resolution for columns in ArrayGridContext', function () {
    $state = new State();
    $fields = new Fields();

    $context = new ArrayGridContext(
        state: $state,
        datasourceResolver: fn () => [],
        fields: $fields,
        columns: fn () => [Column::make('Delayed', 'delayed_col')]
    );

    expect($context->hasResolvedColumns())->toBeFalse();
    $cols = $context->declaredColumns();
    expect($context->hasResolvedColumns())->toBeTrue()
        ->and($cols)->toHaveCount(1);
});
