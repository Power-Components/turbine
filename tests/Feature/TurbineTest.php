<?php

namespace PowerComponents\Turbine\Tests\Feature;

use Illuminate\Pagination\LengthAwarePaginator;
use PowerComponents\Turbine\{Button, Column, Fields, Turbine};
use PowerComponents\Turbine\Components\Filters\FilterInputText;
use PowerComponents\Turbine\Components\Rules\RuleActions;
use PowerComponents\Turbine\Components\SetUp\{Cache, Detail, FilterBuilder, Header, Responsive};
use PowerComponents\Turbine\Components\SetUp\{Exportable, Footer};
use PowerComponents\Turbine\Tests\Fixtures\Models\Dish;

function turbineGrid(array $state = []): Turbine
{
    return Turbine::make()
        ->datasource(fn () => Dish::query())
        ->fields((new Fields())->add('id')->add('name')->add('price'))
        ->columns([
            Column::make('Id', 'id')->sortable(),
            Column::make('Name', 'name')->searchable()->sortable(),
        ])
        ->filters([new FilterInputText('name')])
        ->tableName('dishes')
        ->perPage(5)
        ->actions(fn ($row) => [
            Button::add('edit')->slot('Edit')->dispatch('editDish', ['id' => (int) $row->id]),
            Button::add('delete')->slot('Delete')->confirm('Delete this dish?'),
        ])
        ->state($state);
}

describe('Button agnostic action DSL', function () {
    it('describes events through eventMeta and never emits wire attributes', function () {
        $button = Button::add('edit')->dispatch('editDish', ['id' => 7]);

        expect($button->eventMeta)->toBe(['type' => 'dispatch', 'event' => 'editDish', 'params' => ['id' => 7]])
            ->and($button->attributes)->not->toHaveKey('wire:click');
    });

    it('builds a plain link without wire bindings', function () {
        $button = Button::add('open')->link('https://example.test', '_blank');

        expect($button->tag)->toBe('a')
            ->and($button->attributes)->toMatchArray(['href' => 'https://example.test', 'target' => '_blank'])
            ->and($button->eventMeta['type'])->toBe('link');
    });

    it('stores confirmation as a neutral descriptor', function () {
        $button = Button::add('delete')->confirm('Sure?');

        expect($button->confirm)->toBe('Sure?')
            ->and($button->confirmIsPrompt)->toBeFalse()
            ->and($button->attributes)->not->toHaveKey('wire:confirm');
    });
});

describe('Turbine builder', function () {
    it('produces the full JSON envelope', function () {
        $response = turbineGrid()->toArray();

        expect($response->data)->toHaveCount(5)
            ->and($response->data[0])->toHaveKeys(['id', 'name', 'price'])
            ->and($response->meta->pagination->perPage)->toBe(5)
            ->and($response->meta->pagination->total)->toBe(Dish::query()->count())
            ->and($response->columns)->toHaveCount(2)
            ->and($response->filters[0]->all())->toMatchArray(['key' => 'input_text', 'field' => 'name']);
    });

    it('resolves row actions with agnostic event descriptors', function () {
        $response = turbineGrid()->toArray();

        $firstId = (int) $response->data[0]['id'];
        $actions = $response->actions[(string) $firstId];

        expect($actions[0]->all())->toMatchArray([
            'id' => 'edit',
            'label' => 'Edit',
        ])
            ->and($actions[1]->all())->toMatchArray([
                'id' => 'delete',
            ]);
    });

    it('hides an action per row via action rules', function () {
        $response = Turbine::make()
            ->datasource(fn () => Dish::query())
            ->fields((new Fields())->add('id')->add('name'))
            ->columns([Column::make('Id', 'id')])
            ->tableName('dishes')
            ->perPage(5)
            ->primaryKey('id')
            ->actions(fn ($row) => [Button::add('delete')->slot('Delete')])
            ->actionRules(fn ($row) => [
                (new RuleActions('delete'))->when(fn ($r) => (int) $r->id === 1)->hide(),
            ])
            ->toArray();

        expect($response->actions['1'][0]->all())->toMatchArray(['id' => 'delete', 'visible' => false])
            ->and($response->actions['2'][0]->all())->toMatchArray(['id' => 'delete', 'visible' => true]);
    });

    it('narrows results from the request state', function () {
        $response = turbineGrid(['search' => 'Pastel'])->toArray();

        expect($response->meta->search)->toBe('Pastel')
            ->and($response->meta->pagination->total)->toBe(2);
    });

    it('produces a JSON response', function () {
        $response = turbineGrid()->toResponse();

        expect($response->getStatusCode())->toBe(200)
            ->and($response->headers->get('content-type'))->toContain('application/json');
    });

    it('produces a paginator with transformed rows', function () {
        $paginator = turbineGrid()->toPaginator();

        expect($paginator)->toBeInstanceOf(LengthAwarePaginator::class)
            ->and($paginator->total())->toBe(Dish::query()->count())
            ->and($paginator->perPage())->toBe(5)
            ->and($paginator->items()[0])->toBeArray()
            ->and($paginator->items()[0])->toHaveKeys(['id', 'name', 'price']);
    });

    it('requires a datasource', function () {
        Turbine::make()->toArray();
    })->throws(\LogicException::class);

    it('emits footer perPage in the setup envelope by default', function () {
        $response = turbineGrid()->toArray();

        expect($response->meta->setup['footer'])
            ->toMatchArray(['perPage' => 5, 'pageName' => 'page']);
    });

    it('builds SetUp components and fields through static factories', function () {
        expect(Turbine::header())->toBeInstanceOf(Header::class)
            ->and(Turbine::footer())->toBeInstanceOf(Footer::class)
            ->and(Turbine::detail())->toBeInstanceOf(Detail::class)
            ->and(Turbine::exportable('report'))->toBeInstanceOf(Exportable::class)
            ->and(Turbine::exportable('report')->fileName)->toBe('report')
            ->and(Turbine::cache())->toBeInstanceOf(Cache::class)
            ->and(Turbine::filterBuilder())->toBeInstanceOf(FilterBuilder::class)
            ->and(Turbine::responsive())->toBeInstanceOf(Responsive::class)
            ->and(Fields::make()->add('id')->fields)->toHaveKey('id');
    });

    it('serializes declared setUp components into the envelope', function () {
        $response = Turbine::make()
            ->datasource(fn () => Dish::query())
            ->fields((new Fields())->add('id')->add('name'))
            ->columns([Column::make('Id', 'id')])
            ->tableName('dishes')
            ->perPage(15)
            ->setUp([
                (new Footer())->showPerPage(25, [10, 25, 50]),
                (new Exportable('dishes'))->type(Exportable::TYPE_CSV),
            ])
            ->toArray();

        expect($response->meta->setup['footer'])
            ->toMatchArray(['perPage' => 25, 'perPageValues' => [10, 25, 50]])
            ->and($response->meta->setup['exportable'])
            ->toMatchArray(['name' => 'exportable', 'type' => ['csv']]);
    });
});
