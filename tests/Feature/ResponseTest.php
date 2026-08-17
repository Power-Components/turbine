<?php

namespace PowerComponents\Turbine\Tests\Feature;

use PowerComponents\Turbine\{Button, Column, Fields, Response};
use PowerComponents\Turbine\Components\Filters\FilterInputText;
use PowerComponents\Turbine\Support\State\{ArrayGridContext, State};
use PowerComponents\Turbine\Tests\Fixtures\Models\Dish;

function responseContext(array $statePayload = []): ArrayGridContext
{
    return new ArrayGridContext(
        state: State::fromArray(array_merge([
            'primaryKey' => 'id',
            'tableName' => 'dishes',
            'sortField' => 'id',
            'sortDirection' => 'asc',
            'setUp' => ['footer' => ['perPage' => 5, 'pageName' => 'page']],
        ], $statePayload)),
        datasourceResolver: fn () => Dish::query(),
        fields: (new Fields())->add('id')->add('name')->add('price'),
        columns: [
            Column::add()->title('Id')->field('id')->sortable(),
            Column::add()->title('Name')->field('name')->searchable()->sortable(),
        ],
        filters: [new FilterInputText('name')],
        actionsResolver: fn ($row) => [editButton((int) $row->id)],
    );
}

function editButton(int $id): Button
{
    $button = Button::add('edit')->slot('Edit');
    $button->eventMeta = ['type' => 'dispatch', 'event' => 'editDish', 'params' => ['id' => $id]];

    return $button;
}

it('builds a JSON envelope with data, meta, columns and filters', function () {
    $envelope = Response::make(responseContext())->toArray();

    expect($envelope['data'])->toBeArray()->not->toBeEmpty()
        ->and($envelope['data'][0])->toHaveKeys(['id', 'name'])
        ->and($envelope['meta']['pagination']['per_page'])->toBe(5)
        ->and($envelope['meta']['pagination']['total'])->toBe(Dish::query()->count())
        ->and($envelope['meta']['pagination']['current_page'])->toBe(1)
        ->and($envelope['meta']['sort']['field'])->toBe('id')
        ->and($envelope['columns'])->toHaveCount(2)
        ->and($envelope['columns'][0])->toMatchArray(['field' => 'id', 'sortable' => true, 'searchable' => false])
        ->and($envelope['columns'][1])->toMatchArray(['field' => 'name', 'searchable' => true])
        ->and($envelope['filters'][0]['key'])->toBe('input_text')
        ->and($envelope['filters'][0]['field'])->toBe('name');
});

it('keys resolved action descriptors by primary key', function () {
    $envelope = Response::make(responseContext())->toArray();

    $firstId = (int) $envelope['data'][0]['id'];

    expect($envelope['actions'])->toHaveKey((string) $firstId)
        ->and($envelope['actions'][(string) $firstId][0])->toMatchArray([
            'id' => 'edit',
            'label' => 'Edit',
            'event' => ['type' => 'dispatch', 'event' => 'editDish', 'params' => ['id' => $firstId]],
        ]);
});

it('echoes search state and narrows data in the envelope', function () {
    $envelope = Response::make(responseContext(['search' => 'Pastel']))->toArray();

    expect($envelope['meta']['search'])->toBe('Pastel')
        ->and($envelope['meta']['pagination']['total'])->toBe(2);
});

it('produces a JSON response', function () {
    $response = Response::make(responseContext())->toResponse();

    expect($response->getStatusCode())->toBe(200)
        ->and($response->headers->get('content-type'))->toContain('application/json');
});

it('omits filters and actions keys when they are empty', function () {
    $context = new ArrayGridContext(
        state: State::fromArray([
            'primaryKey' => 'id',
            'tableName' => 'dishes',
        ]),
        datasourceResolver: fn () => Dish::query(),
        fields: (new Fields())->add('id')->add('name'),
        columns: [
            Column::add()->title('Id')->field('id'),
        ],
        filters: [],
        actionsResolver: fn ($row) => [],
    );

    $envelope = Response::make($context)->toArray();

    expect($envelope)->not->toHaveKey('filters')
        ->and($envelope)->not->toHaveKey('actions')
        ->and($envelope)->toHaveKeys(['data', 'meta', 'columns']);
});
