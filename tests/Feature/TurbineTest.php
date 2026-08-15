<?php

use PowerComponents\Turbine\{Button, Column, Fields, Turbine};
use PowerComponents\Turbine\Components\Filters\FilterInputText;
use PowerComponents\Turbine\Components\Rules\RuleActions;
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
        $envelope = turbineGrid()->toArray();

        expect($envelope['data'])->toHaveCount(5)
            ->and($envelope['data'][0])->toHaveKeys(['id', 'name', 'price'])
            ->and($envelope['meta']['pagination']['per_page'])->toBe(5)
            ->and($envelope['meta']['pagination']['total'])->toBe(Dish::query()->count())
            ->and($envelope['columns'])->toHaveCount(2)
            ->and($envelope['filters'][0])->toMatchArray(['key' => 'input_text', 'field' => 'name']);
    });

    it('resolves row actions with agnostic event descriptors', function () {
        $envelope = turbineGrid()->toArray();

        $firstId = (int) $envelope['data'][0]['id'];
        $actions = $envelope['actions'][(string) $firstId];

        expect($actions[0])->toMatchArray([
            'id' => 'edit',
            'label' => 'Edit',
            'event' => ['type' => 'dispatch', 'event' => 'editDish', 'params' => ['id' => $firstId]],
        ])
            ->and($actions[1])->toMatchArray([
                'id' => 'delete',
                'confirm' => 'Delete this dish?',
                'confirmPrompt' => false,
            ]);
    });

    it('hides an action per row via action rules', function () {
        $envelope = Turbine::make()
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

        expect($envelope['actions']['1'][0])->toMatchArray(['id' => 'delete', 'visible' => false])
            ->and($envelope['actions']['2'][0])->toMatchArray(['id' => 'delete', 'visible' => true]);
    });

    it('narrows results from the request state', function () {
        $envelope = turbineGrid(['search' => 'Pastel'])->toArray();

        expect($envelope['meta']['search'])->toBe('Pastel')
            ->and($envelope['meta']['pagination']['total'])->toBe(2);
    });

    it('produces a JSON response', function () {
        $response = turbineGrid()->toResponse();

        expect($response->getStatusCode())->toBe(200)
            ->and($response->headers->get('content-type'))->toContain('application/json');
    });

    it('requires a datasource', function () {
        Turbine::make()->toArray();
    })->throws(LogicException::class);
});
