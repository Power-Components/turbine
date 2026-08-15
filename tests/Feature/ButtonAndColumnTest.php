<?php

use PowerComponents\Turbine\{Button, Column};

it('creates and configures Button fluently', function () {
    $button = Button::make('edit', 'Edit Label')
        ->tag('a')
        ->view('buttons.custom')
        ->attributes(['class' => 'btn btn-primary'])
        ->icon('pencil', ['class' => 'w-4 h-4']);

    expect($button->action)->toBe('edit')
        ->and($button->slot)->toBe('Edit Label')
        ->and($button->tag)->toBe('a')
        ->and($button->view)->toBe('buttons.custom')
        ->and($button->attributes)->toBe(['class' => 'btn btn-primary'])
        ->and($button->icon)->toBe('pencil')
        ->and($button->iconAttributes)->toBe(['class' => 'w-4 h-4']);

    $addBtn = Button::add('create');
    expect($addBtn->action)->toBe('create');
});

it('creates and configures Column fluently', function () {
    $column = Column::make('Name', 'name', 'users.name')
        ->searchable()
        ->sortable()
        ->hidden(false)
        ->visibleInExport(true)
        ->headerAttribute('th-class', 'color: red;')
        ->bodyAttribute('td-class', 'background: blue;')
        ->fixedOnResponsive()
        ->index();

    expect($column->title)->toBe('Name')
        ->and($column->field)->toBe('name')
        ->and($column->dataField)->toBe('users.name')
        ->and($column->searchable)->toBeTrue()
        ->and($column->sortable)->toBeTrue()
        ->and($column->enableSort)->toBeTrue()
        ->and($column->hidden)->toBeFalse()
        ->and($column->visibleInExport)->toBeTrue()
        ->and($column->headerClass)->toBe('th-class')
        ->and($column->headerStyle)->toBe('color: red;')
        ->and($column->bodyClass)->toBe('td-class')
        ->and($column->bodyStyle)->toBe('background: blue;')
        ->and($column->fixedOnResponsive)->toBeTrue()
        ->and($column->index)->toBeTrue();

    $actionCol = Column::action('Actions');
    expect($actionCol->isAction)->toBeTrue()
        ->and($actionCol->title)->toBe('Actions');
});

it('tests all ButtonMacros DSL actions', function () {
    $btn = Button::make('edit');

    $btn->class('btn btn-sm')
        ->id('edit-btn')
        ->tooltip('Edit user')
        ->can(true)
        ->disable(true)
        ->link('https://example.com', '_blank')
        ->call('save', ['id' => 10])
        ->dispatch('userUpdated', ['id' => 10])
        ->dispatchTo('user-grid', 'userUpdated', ['id' => 10])
        ->dispatchSelf('userUpdated', ['id' => 10])
        ->parent('reload', [1])
        ->openModal('edit-modal', ['id' => 10])
        ->toggleDetail(10)
        ->confirm('Are you sure?')
        ->confirmPrompt('Delete account?', 'DELETE');

    expect($btn->attributes)->toMatchArray(['class' => 'btn btn-sm', 'id' => 'edit-btn', 'title' => 'Edit user', 'disabled' => 'disabled'])
        ->and($btn->can)->toBeTrue()
        ->and($btn->confirm)->toContain('Delete account?')
        ->and($btn->confirmIsPrompt)->toBeTrue()
        ->and($btn->eventMeta)->toBe([
            'type' => 'toggleDetail',
            'rowId' => 10,
        ]);
});

it('tests Column additional methods and custom sorting', function () {
    $column = Column::add()
        ->title('Title')
        ->field('title', 'data_title')
        ->contentClassField('status')
        ->contentClasses(['class-a', 'class-b'])
        ->template()
        ->sortUsing(fn ($query, $direction) => $query->orderBy('data_title', $direction));

    expect($column->title)->toBe('Title')
        ->and($column->field)->toBe('title')
        ->and($column->dataField)->toBe('data_title')
        ->and($column->contentClassField)->toBe('status')
        ->and($column->contentClasses)->toBe(['class-a', 'class-b'])
        ->and($column->template)->toBeTrue()
        ->and($column->sortCallback)->toBeInstanceOf(Closure::class);
});
