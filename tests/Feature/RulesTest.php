<?php

namespace PowerComponents\Turbine\Tests\Feature;

use PowerComponents\Turbine\Components\Rules\{
    RuleActions,
    RuleCheckbox,
    RuleEditOnClick,
    RuleManager,
    RuleRadio,
    RuleRows,
    RuleToggleable
};
use PowerComponents\Turbine\Components\Rules\Support\{
    DisableRule,
    HideRule,
    SlotRule
};

it('instantiates rules via RuleManager and manages modifiers', function () {
    $manager = new RuleManager();

    expect($manager->button('edit'))->toBeInstanceOf(RuleActions::class)
        ->and($manager->rows())->toBeInstanceOf(RuleRows::class)
        ->and($manager->checkbox())->toBeInstanceOf(RuleCheckbox::class)
        ->and($manager->radio())->toBeInstanceOf(RuleRadio::class);

    RuleManager::registerModifiers(['customModifier']);
    expect(RuleManager::applicableModifiers())->toContain('customModifier');
});

it('configures RuleActions conditions and modifiers', function () {
    $rule = new RuleActions('delete');

    $whenClosure = fn ($row) => $row->id === 1;
    $rule->when($whenClosure)
        ->hide()
        ->slot('Trash')
        ->setAttribute('class', 'btn-danger')
        ->bladeComponent('my-button', ['color' => 'red']);

    expect($rule->forAction)->toBe('delete')
        ->and($rule->rule['when'])->toBe($whenClosure)
        ->and($rule->rule['hide'])->toBeTrue()
        ->and($rule->rule['slot'])->toBe('Trash')
        ->and($rule->rule['setAttribute'])->toBe([['attribute' => 'class', 'value' => 'btn-danger']])
        ->and($rule->rule['bladeComponent'])->toBe(['component' => 'my-button', 'params' => ['color' => 'red']]);
});

it('throws exception if multiple conditions are set on RuleActions', function () {
    $rule = new RuleActions('edit');
    $rule->when(fn () => true);
    $rule->unless(fn () => false);
})->throws(\InvalidArgumentException::class, 'A rule must have only one condition.');

it('configures RuleRows modifiers and loop conditions', function () {
    $rule = new RuleRows();

    $rule->setAttribute('class', 'row-active')
        ->detailView('details.view', ['param' => 123])
        ->showToggleable()
        ->hideToggleable()
        ->enableEditOnClick()
        ->disableEditOnClick()
        ->showToggleDetail()
        ->hideToggleDetail();

    expect($rule->forAction)->toBe(RuleManager::TYPE_ROWS)
        ->and($rule->rule['setAttribute'])->toBe(['attribute' => 'class', 'value' => 'row-active'])
        ->and($rule->rule['detailView'])->toBe(['detailView' => 'details.view', 'options' => ['param' => 123]])
        ->and($rule->rule['toggleableVisibility'])->toBe('hide')
        ->and($rule->rule['editOnClickVisibility'])->toBe('hide')
        ->and($rule->rule['toggleDetailVisibility'])->toBe('hide');

    $firstRule = (new RuleRows())->firstOnPage();
    expect($firstRule->rule)->toHaveKey('loop');

    $lastRule = (new RuleRows())->lastOnPage();
    expect($lastRule->rule)->toHaveKey('loop');

    $evenRule = (new RuleRows())->alternating();
    expect($evenRule->rule)->toHaveKey('loop');
});

it('configures RuleCheckbox and RuleRadio', function () {
    $checkbox = new RuleCheckbox();
    $checkbox->setAttribute('disabled', 'disabled')
        ->hide()
        ->disable()
        ->applyRowClasses('disabled-row');

    expect($checkbox->forAction)->toBe(RuleManager::TYPE_CHECKBOX)
        ->and($checkbox->rule['setAttribute'])->toBe(['attribute' => 'disabled', 'value' => 'disabled'])
        ->and($checkbox->rule['hide'])->toBeTrue()
        ->and($checkbox->rule['disable'])->toBeTrue()
        ->and($checkbox->rule['rowClasses'])->toBe('disabled-row');

    $radio = new RuleRadio();
    $radio->setAttribute('name', 'selected')
        ->hide()
        ->disable()
        ->applyRowClasses('radio-row');

    expect($radio->forAction)->toBe(RuleManager::TYPE_RADIO)
        ->and($radio->rule['setAttribute'])->toBe(['attribute' => 'name', 'value' => 'selected'])
        ->and($radio->rule['hide'])->toBeTrue()
        ->and($radio->rule['disable'])->toBeTrue()
        ->and($radio->rule['rowClasses'])->toBe('radio-row');
});

it('configures RuleToggleable and RuleEditOnClick', function () {
    $toggleable = new RuleToggleable('is_active');
    $toggleable->hide();
    expect($toggleable->forAction)->toBe('is_active')
        ->and($toggleable->rule['fieldHideToggleable'])->toBeTrue();

    $toggleable->show();
    expect($toggleable->rule['fieldHideToggleable'])->toBeFalse();

    $editOnClick = new RuleEditOnClick('name');
    $editOnClick->disable();
    expect($editOnClick->forAction)->toBe('name')
        ->and($editOnClick->rule['fieldHideEditOnClick'])->toBeTrue();

    $editOnClick->enable();
    expect($editOnClick->rule['fieldHideEditOnClick'])->toBeFalse();
});

it('applies Support rules', function () {
    $disableRule = new DisableRule();
    expect($disableRule->apply(true))->toBe(['attributes' => ['disabled' => 'disabled']])
        ->and($disableRule->apply(false))->toBe([]);

    $hideRule = new HideRule();
    expect($hideRule->apply(true))->toBe(['hide' => true])
        ->and($hideRule->apply(false))->toBe([]);

    $slotRule = new SlotRule();
    expect($slotRule->apply('New Label'))->toBe(['slot' => 'New Label']);
});
