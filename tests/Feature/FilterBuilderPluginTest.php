<?php

namespace PowerComponents\Turbine\Tests\Feature;

use PowerComponents\Turbine\Plugins\FilterBuilder\FilterBuilderValidator;

it('returns supported operators by type', function () {
    $operators = FilterBuilderValidator::operators();

    expect($operators)->toHaveKeys(['input_text', 'number', 'select', 'boolean', 'date', 'datetime'])
        ->and($operators['number'])->toContain('between', 'greater_equal', 'less_equal')
        ->and($operators['select'])->toBe(['is'])
        ->and($operators['boolean'])->toBe(['is']);
});

it('validates and sanitizes FilterBuilder payload correctly', function () {
    $meta = [
        'name' => [
            'field' => 'name',
            'operators' => ['contains', 'is_empty'],
        ],
        'age' => [
            'field' => 'age',
            'operators' => ['between', 'greater_equal'],
        ],
    ];

    $payload = [
        'match' => 'or',
        'rows' => [
            ['column' => 'name', 'operator' => 'contains', 'value' => 'John', 'boolean' => 'and'],
            ['column' => 'name', 'operator' => 'is_empty', 'value' => null, 'boolean' => 'or'],
            ['column' => 'age', 'operator' => 'between', 'value' => 18, 'value2' => 30, 'boolean' => 'and'],
            ['column' => 'invalid_col', 'operator' => 'contains', 'value' => 'test'], // Column not in meta
            ['column' => 'name', 'operator' => 'invalid_op', 'value' => 'test'], // Disallowed operator
            ['column' => 'age', 'operator' => 'between', 'value' => 18, 'value2' => null], // Missing value2
            ['column' => 'name', 'operator' => 'contains', 'value' => ''], // Blank value
        ],
    ];

    $validated = FilterBuilderValidator::validate($payload, $meta, 10);

    expect($validated['match'])->toBe('or')
        ->and($validated['rows'])->toHaveCount(3)
        ->and($validated['rows'][0])->toBe([
            'column' => 'name',
            'operator' => 'contains',
            'value' => 'John',
            'value2' => null,
            'boolean' => 'and',
        ])
        ->and($validated['rows'][1])->toBe([
            'column' => 'name',
            'operator' => 'is_empty',
            'value' => null,
            'value2' => null,
            'boolean' => 'or',
        ])
        ->and($validated['rows'][2])->toBe([
            'column' => 'age',
            'operator' => 'between',
            'value' => 18,
            'value2' => 30,
            'boolean' => 'and',
        ]);
});

it('respects maxConditions limit during validation', function () {
    $meta = [
        'name' => [
            'field' => 'name',
            'operators' => ['contains'],
        ],
    ];

    $payload = [
        'match' => 'and',
        'rows' => [
            ['column' => 'name', 'operator' => 'contains', 'value' => 'A'],
            ['column' => 'name', 'operator' => 'contains', 'value' => 'B'],
            ['column' => 'name', 'operator' => 'contains', 'value' => 'C'],
        ],
    ];

    $validated = FilterBuilderValidator::validate($payload, $meta, 2);

    expect($validated['rows'])->toHaveCount(2);
});
