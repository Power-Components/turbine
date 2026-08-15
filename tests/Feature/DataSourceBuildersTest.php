<?php

use Illuminate\Support\Collection;
use PowerComponents\Turbine\Components\Filters\{
    FilterInputText,
    FilterNumber
};
use PowerComponents\Turbine\DataSource\Builders\{
    Boolean,
    DatePicker,
    DateTimePicker,
    InputText,
    MultiSelect,
    Number,
    Select
};
use PowerComponents\Turbine\Fields;
use PowerComponents\Turbine\Support\State\{ArrayGridContext, State};
use PowerComponents\Turbine\Tests\Fixtures\Models\Dish;

function makeDummyContext(array $state = []): ArrayGridContext
{
    return new ArrayGridContext(
        state: State::fromArray(array_merge(['primaryKey' => 'id', 'tableName' => 'dishes'], $state)),
        datasourceResolver: fn () => Dish::query(),
        fields: new Fields()
    );
}

it('filters InputText builder with all operators on Eloquent Query and Collection', function () {
    $ctx = makeDummyContext();

    $operators = [
        'is' => 'Peixe',
        'is_not' => 'Pastel',
        'starts_with' => 'Pas',
        'ends_with' => 'tel',
        'contains_not' => 'xyz',
        'is_empty' => '',
        'is_not_empty' => '',
        'is_null' => '',
        'is_not_null' => '',
        'is_blank' => '',
        'is_not_blank' => '',
        'default' => 'Peixe',
    ];

    foreach ($operators as $op => $val) {
        $builder = new InputText($ctx);
        $query = Dish::query();
        $builder->builder($query, 'name', ['selected' => $op, 'value' => $val, 'searchMorphs' => null]);

        expect($query->toSql())->toContain('where');

        $collection = collect([
            ['name' => 'Peixe'],
            ['name' => 'Pastel'],
            ['name' => ''],
            ['name' => null],
        ]);
        $filtered = $builder->collection($collection, 'name', ['selected' => $op, 'value' => $val]);
        expect($filtered)->toBeInstanceOf(Collection::class);
    }
});

it('uses custom builder and collection closures on InputText', function () {
    $ctx = makeDummyContext();
    $filterBase = (new FilterInputText('name'))
        ->builder(fn ($q, $val) => $queryCalled = true)
        ->collection(fn ($c, $val) => collect([['name' => 'custom']]));

    $builder = new InputText($ctx, $filterBase);

    $c = collect();
    $result = $builder->collection($c, 'name', ['selected' => 'is', 'value' => 'test']);
    expect($result->toArray())->toBe([['name' => 'custom']]);
});

it('filters Number builder with ranges, thousands and decimals', function () {
    $ctx = makeDummyContext();
    $filterBase = (new FilterNumber('price'))
        ->thousands('.')
        ->decimal(',');

    $numberBuilder = new Number($ctx, $filterBase);

    // Range start only
    $q1 = Dish::query();
    $numberBuilder->builder($q1, 'price', ['start' => '1.000,50', 'end' => null]);
    expect($q1->toSql())->toContain('>=');

    // Range end only
    $q2 = Dish::query();
    $numberBuilder->builder($q2, 'price', ['start' => null, 'end' => '2.000,00']);
    expect($q2->toSql())->toContain('<=');

    // Range both
    $q3 = Dish::query();
    $numberBuilder->builder($q3, 'price', ['start' => '10,00', 'end' => '50,00']);
    expect($q3->toSql())->toContain('between');

    // Collection filtering
    $col = collect([
        ['price' => 10.0],
        ['price' => 20.0],
        ['price' => 30.0],
    ]);

    $res = $numberBuilder->collection($col, 'price', ['start' => '15,00', 'end' => '25,00']);
    expect($res->pluck('price')->values()->all())->toBe([20.0]);
});

it('filters DatePicker and DateTimePicker builders on Query and Collection', function () {
    $ctx = makeDummyContext();

    $dateBuilder = new DatePicker($ctx);
    $qDate = Dish::query();
    $dateBuilder->builder($qDate, 'created_at', ['start' => '2025-01-01', 'end' => '2025-01-31']);
    expect($qDate->toSql())->toContain('between');

    $colDate = collect([
        ['created_at' => '2025-01-15'],
        ['created_at' => '2025-02-15'],
    ]);
    $resDate = $dateBuilder->collection($colDate, 'created_at', ['start' => '2025-01-01', 'end' => '2025-01-31']);
    expect($resDate)->toHaveCount(1);

    $dateTimeBuilder = new DateTimePicker($ctx);
    $qDateTime = Dish::query();
    $dateTimeBuilder->builder($qDateTime, 'created_at', ['start' => '2025-01-01 00:00:00', 'end' => '2025-01-31 23:59:59']);
    expect($qDateTime->toSql())->toContain('between');

    $resDateTime = $dateTimeBuilder->collection($colDate, 'created_at', ['start' => '2025-01-01 00:00:00', 'end' => '2025-01-31 23:59:59']);
    expect($resDateTime)->toHaveCount(1);
});

it('filters Boolean builder with true, false, 1, 0, and all values', function () {
    $ctx = makeDummyContext();
    $boolBuilder = new Boolean($ctx);

    $qTrue = Dish::query();
    $boolBuilder->builder($qTrue, 'in_stock', 'true');
    expect($qTrue->toSql())->toContain('where');

    $qAll = Dish::query();
    $boolBuilder->builder($qAll, 'in_stock', 'all');
    expect($qAll->toSql())->not->toContain('where');

    $col = collect([
        ['in_stock' => true],
        ['in_stock' => false],
    ]);
    $resTrue = $boolBuilder->collection($col, 'in_stock', '1');
    expect($resTrue)->toHaveCount(1)
        ->and($resTrue->first()['in_stock'])->toBeTrue();

    $resAll = $boolBuilder->collection($col, 'in_stock', 'all');
    expect($resAll)->toHaveCount(2);
});

it('filters Select and MultiSelect builders on Query and Collection', function () {
    $ctx = makeDummyContext();

    $selectBuilder = new Select($ctx);
    $qSelect = Dish::query();
    $selectBuilder->builder($qSelect, 'category_id', '5');
    expect($qSelect->toSql())->toContain('where');

    $col = collect([
        ['category_id' => 1],
        ['category_id' => 5],
    ]);
    $resSelect = $selectBuilder->collection($col, 'category_id', '5');
    expect($resSelect)->toHaveCount(1);

    $multiBuilder = new MultiSelect($ctx);
    $qMulti = Dish::query();
    $multiBuilder->builder($qMulti, 'category_id', [1, 2, 5]);
    expect($qMulti->toSql())->toContain('in');

    $resMulti = multiBuilderCollection($multiBuilder, $col, 'category_id', [1, 5]);
    expect($resMulti)->toHaveCount(2);
});

function multiBuilderCollection($multiBuilder, $col, $field, $values)
{
    return $multiBuilder->collection($col, $field, $values);
}
