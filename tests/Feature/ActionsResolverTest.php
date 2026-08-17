<?php

use PowerComponents\Turbine\Components\Rules\RuleManager;
use PowerComponents\Turbine\Fields;
use PowerComponents\Turbine\Support\Actions\ActionsResolver;
use PowerComponents\Turbine\Support\State\{ArrayGridContext, State};

it('determines if a row is selectable based on turbine rules', function () {
    $context = new ArrayGridContext(
        state: new State(),
        datasourceResolver: fn () => [],
        fields: new Fields()
    );

    $resolver = new ActionsResolver($context);

    $selectableRow = (object) [
        'id' => 1,
        '__turbine_rules' => [],
    ];

    expect($resolver->isRowSelectable($selectableRow, RuleManager::TYPE_CHECKBOX))->toBeTrue();

    $hiddenRow = (object) [
        'id' => 2,
        '__turbine_rules' => [
            [
                'forAction' => RuleManager::TYPE_CHECKBOX,
                'apply' => true,
                'hide' => true,
            ],
        ],
    ];

    expect($resolver->isRowSelectable($hiddenRow, RuleManager::TYPE_CHECKBOX))->toBeFalse();

    $disabledRow = (object) [
        'id' => 3,
        '__turbine_rules' => [
            [
                'forAction' => RuleManager::TYPE_CHECKBOX,
                'apply' => true,
                'disable' => true,
            ],
        ],
    ];

    expect($resolver->isRowSelectable($disabledRow, RuleManager::TYPE_CHECKBOX))->toBeFalse();
});
