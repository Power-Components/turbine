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
    $response = Response::make(responseContext())->toArray();

    expect($response->data)->toBeArray()->not->toBeEmpty()
        ->and($response->data[0])->toHaveKeys(['id', 'name'])
        ->and($response->meta->pagination->perPage)->toBe(5)
        ->and($response->meta->pagination->total)->toBe(Dish::query()->count())
        ->and($response->meta->pagination->currentPage)->toBe(1)
        ->and($response->meta->sort->field)->toBe('id')
        ->and($response->columns)->toHaveCount(2)
        ->and($response->columns[0]->all())->toMatchArray(['field' => 'id', 'sortable' => true, 'searchable' => false])
        ->and($response->columns[1]->all())->toMatchArray(['field' => 'name', 'searchable' => true])
        ->and($response->filters[0]->key)->toBe('input_text')
        ->and($response->filters[0]->field)->toBe('name');
});

it('keys resolved action descriptors by primary key', function () {
    $response = Response::make(responseContext())->toArray();

    $firstId = (int) $response->data[0]['id'];

    expect($response->actions)->toHaveKey((string) $firstId)
        ->and($response->actions[(string) $firstId][0]->all())->toMatchArray([
            'id' => 'edit',
            'label' => 'Edit',
        ]);
});

it('echoes search state and narrows data in the envelope', function () {
    $response = Response::make(responseContext(['search' => 'Pastel']))->toArray();

    expect($response->meta->search)->toBe('Pastel')
        ->and($response->meta->pagination->total)->toBe(2);
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

    $response = Response::make($context)->toArray();

    expect($response->filters)->toBeNull()
        ->and($response->actions)->toBeNull()
        ->and($response->data)->toBeArray()
        ->and($response->meta)->not->toBeNull()
        ->and($response->columns)->toBeArray();
});
