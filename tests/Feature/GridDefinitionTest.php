<?php

namespace PowerComponents\Turbine\Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use PowerComponents\Turbine\{Button, Column, Fields, GridDefinition, Turbine};
use PowerComponents\Turbine\Components\Filters\FilterInputText;
use PowerComponents\Turbine\Components\Rules\RuleActions;
use PowerComponents\Turbine\Components\SetUp\Footer;
use PowerComponents\Turbine\Contracts\Context;
use PowerComponents\Turbine\Tests\Fixtures\Models\Dish;

class DishGrid extends GridDefinition
{
    public string $tableName = 'dishes';

    public int $perPage = 5;

    public function datasource(): mixed
    {
        return Dish::query();
    }

    public function fields(): Fields
    {
        return (new Fields())->add('id')->add('name')->add('price');
    }

    public function columns(): array
    {
        return [
            Column::make('Id', 'id')->sortable(),
            Column::make('Name', 'name')->searchable()->sortable(),
        ];
    }

    public function filters(): array
    {
        return [new FilterInputText('name')];
    }

    public function actions(object $row): array
    {
        return [
            Button::add('edit')->slot('Edit')->dispatch('editDish', ['id' => (int) $row->id]),
        ];
    }

    public function actionRules(object $row): array
    {
        return [
            (new RuleActions('edit'))->when(fn ($r) => (int) $r->id === 1)->hide(),
        ];
    }
}

function equivalentTurbine(array $state = []): Turbine
{
    return Turbine::make()
        ->datasource(fn () => Dish::query())
        ->fields((new Fields())->add('id')->add('name')->add('price'))
        ->columns(fn () => [
            Column::make('Id', 'id')->sortable(),
            Column::make('Name', 'name')->searchable()->sortable(),
        ])
        ->filters([new FilterInputText('name')])
        ->actions(fn ($row) => [
            Button::add('edit')->slot('Edit')->dispatch('editDish', ['id' => (int) $row->id]),
        ])
        ->actionRules(fn ($row) => [
            (new RuleActions('edit'))->when(fn ($r) => (int) $r->id === 1)->hide(),
        ])
        ->tableName('dishes')
        ->perPage(5)
        ->state($state);
}

describe('GridDefinition', function () {
    it('produces the same envelope as the equivalent Turbine chain', function () {
        $request = Request::create('/grid', 'GET');

        $fromDefinition = (new DishGrid())->toArray($request);
        $fromBuilder = equivalentTurbine()->toArray();

        expect($fromDefinition)->toEqual($fromBuilder);
    });

    it('produces the full JSON envelope', function () {
        $envelope = (new DishGrid())->toArray(Request::create('/grid', 'GET'));

        expect($envelope['data'])->toHaveCount(5)
            ->and($envelope['data'][0])->toHaveKeys(['id', 'name', 'price'])
            ->and($envelope['meta']['pagination']['per_page'])->toBe(5)
            ->and($envelope['columns'])->toHaveCount(2)
            ->and($envelope['filters'][0])->toMatchArray(['key' => 'input_text', 'field' => 'name']);
    });

    it('flows request state through the definition', function () {
        $envelope = (new DishGrid())->toArray(Request::create('/grid', 'GET', ['search' => 'Pastel']));

        expect($envelope['meta']['search'])->toBe('Pastel')
            ->and($envelope['meta']['pagination']['total'])->toBe(2);
    });

    it('resolves actions and action rules per row', function () {
        $envelope = (new DishGrid())->toArray(Request::create('/grid', 'GET'));

        expect($envelope['actions']['1'][0])->toMatchArray(['id' => 'edit', 'visible' => false])
            ->and($envelope['actions']['2'][0])->toMatchArray(['id' => 'edit', 'visible' => true]);
    });

    it('produces a paginator with transformed rows', function () {
        $paginator = (new DishGrid())->toPaginator(Request::create('/grid', 'GET'));

        expect($paginator)->toBeInstanceOf(LengthAwarePaginator::class)
            ->and($paginator->perPage())->toBe(5)
            ->and($paginator->items()[0])->toBeArray()
            ->and($paginator->items()[0])->toHaveKeys(['id', 'name', 'price']);
    });

    it('exposes a context for exporting', function () {
        $context = (new DishGrid())->context(Request::create('/grid', 'GET'));

        expect($context)->toBeInstanceOf(Context::class)
            ->and($context->state()->tableName)->toBe('dishes');
    });

    it('produces a JSON response', function () {
        $response = (new DishGrid())->toResponse(Request::create('/grid', 'GET'));

        expect($response->getStatusCode())->toBe(200)
            ->and($response->headers->get('content-type'))->toContain('application/json');
    });

    it('serializes setUp declared on the definition', function () {
        $grid = new class() extends DishGrid
        {
            public function setUp(): array
            {
                return [
                    (new Footer())->showPerPage(50, [50, 100]),
                ];
            }
        };

        $envelope = $grid->toArray(Request::create('/grid', 'GET'));

        expect($envelope['meta']['setup']['footer'])
            ->toMatchArray(['perPage' => 50, 'perPageValues' => [50, 100]]);
    });
});
