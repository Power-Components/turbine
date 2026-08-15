<?php

use PowerComponents\Turbine\Components\Filters\{
    FilterBoolean,
    FilterDatePicker,
    FilterDateTimePicker,
    FilterDynamic,
    FilterEnumSelect,
    FilterInputText,
    FilterManager,
    FilterMultiSelect,
    FilterMultiSelectAsync,
    FilterNumber,
    FilterSelect
};

enum FeatureStatusEnum: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';

    public function labelTurbineFilter(): string
    {
        return $this === self::ACTIVE ? 'Ativo' : 'Inativo';
    }
}

enum FeatureSimpleEnum: string
{
    case YES = 'yes';
    case NO = 'no';
}

it('instantiates all filters via FilterManager', function () {
    $manager = new FilterManager();

    expect($manager->multiSelect('category'))->toBeInstanceOf(FilterMultiSelect::class)
        ->and($manager->multiSelectAsync('user_id'))->toBeInstanceOf(FilterMultiSelectAsync::class)
        ->and($manager->inputText('name'))->toBeInstanceOf(FilterInputText::class)
        ->and($manager->select('status'))->toBeInstanceOf(FilterSelect::class)
        ->and($manager->enumSelect('enum_status'))->toBeInstanceOf(FilterEnumSelect::class)
        ->and($manager->number('price'))->toBeInstanceOf(FilterNumber::class)
        ->and($manager->dynamic('custom'))->toBeInstanceOf(FilterDynamic::class)
        ->and($manager->datepicker('created_at'))->toBeInstanceOf(FilterDatePicker::class)
        ->and($manager->datetimepicker('updated_at'))->toBeInstanceOf(FilterDateTimePicker::class)
        ->and($manager->boolean('is_active'))->toBeInstanceOf(FilterBoolean::class);
});

it('configures FilterBase methods properly', function () {
    $filter = new FilterInputText('name', 'user_name');

    expect($filter->column)->toBe('name')
        ->and($filter->field)->toBe('user_name');

    $filter->filterRelation('user', 'name');
    expect($filter->filterRelation)->toBe(['relation' => 'user', 'field' => 'name']);

    $filter->default('john');
    expect($filter->defaultValue)->toBe('john');

    $filter->baseClass('bg-gray-100');
    expect($filter->baseClass)->toBe('bg-gray-100');

    $filter->component('custom-input', ['placeholder' => 'Search']);
    expect($filter->component)->toBe('custom-input')
        ->and($filter->attributes)->toBe(['placeholder' => 'Search']);

    $builderClosure = fn ($query, $val) => $query;
    $filter->builder($builderClosure);
    expect($filter->builder)->toBe($builderClosure);

    $collectionClosure = fn ($collection, $val) => $collection;
    $filter->collection($collectionClosure);
    expect($filter->collection)->toBe($collectionClosure);
});

it('configures FilterBoolean correctly', function () {
    $filter = new FilterBoolean('is_active');
    expect($filter->trueLabel)->toBe('Yes')
        ->and($filter->falseLabel)->toBe('No');

    $filter->label('Sim', 'Não');
    expect($filter->trueLabel)->toBe('Sim')
        ->and($filter->falseLabel)->toBe('Não');
});

it('configures FilterDynamic correctly', function () {
    $filter = new FilterDynamic('custom_field');
    $filter->attributes(['data-test' => 'dynamic'])
        ->baseClass('dynamic-class');

    expect($filter->attributes)->toBe(['data-test' => 'dynamic'])
        ->and($filter->baseClass)->toBe('dynamic-class');
});

it('executes FilterEnumSelect with labelTurbineFilter method', function () {
    $filter = new FilterEnumSelect('status');
    $filter->dataSource(FeatureStatusEnum::cases())
        ->execute();

    expect($filter->optionLabel)->toBe('name')
        ->and($filter->optionValue)->toBe('value')
        ->and($filter->dataSource->toArray())->toBe([
            ['name' => 'Ativo', 'value' => 'active'],
            ['name' => 'Inativo', 'value' => 'inactive'],
        ]);
});

it('executes FilterEnumSelect without labelTurbineFilter method', function () {
    $filter = new FilterEnumSelect('simple');
    $filter->dataSource(FeatureSimpleEnum::cases())
        ->execute();

    expect($filter->optionLabel)->toBe('value')
        ->and($filter->optionValue)->toBe('value');
});

it('configures FilterMultiSelectAsync parameters', function () {
    $filter = new FilterMultiSelectAsync('user_id');
    $filter->url('https://api.example.com/users')
        ->method('POST')
        ->parameters(['role' => 'admin']);

    expect($filter->url)->toBe('https://api.example.com/users')
        ->and($filter->method)->toBe('POST')
        ->and($filter->parameters)->toBe(['role' => 'admin']);
});

it('configures FilterInputText operators and placeholders', function () {
    $filter = new FilterInputText('title');
    $filter->operators(['contains', 'starts_with'])
        ->placeholder('Type title...');

    expect($filter->operators)->toBe(['contains', 'starts_with'])
        ->and($filter->placeholder)->toBe('Type title...');
});

it('configures FilterMultiSelect properties', function () {
    $filter = new FilterMultiSelect('categories');
    $filter->dataSource(collect([['id' => 1, 'name' => 'Cat 1']]))
        ->optionValue('id')
        ->optionLabel('name')
        ->params(['active' => true]);

    expect($filter->key)->toBe('multi_select')
        ->and($filter->optionValue)->toBe('id')
        ->and($filter->optionLabel)->toBe('name')
        ->and($filter->params)->toBe(['active' => true]);
});

it('configures FilterNumber properties', function () {
    $filter = new FilterNumber('price');
    $filter->thousands('.')
        ->decimal(',')
        ->placeholder('10.00', '100.00');

    expect($filter->key)->toBe('number')
        ->and($filter->thousands)->toBe('.')
        ->and($filter->decimal)->toBe(',')
        ->and($filter->placeholder)->toBe(['min' => '10.00', 'max' => '100.00']);
});

it('configures FilterSelect properties', function () {
    $filter = new FilterSelect('status');
    $filter->depends(['country_id'])
        ->dataSource(collect([['val' => '1', 'txt' => 'One']]))
        ->computedDatasource('getStatuses')
        ->optionValue('val')
        ->optionLabel('txt')
        ->params(['limit' => 10]);

    expect($filter->key)->toBe('select')
        ->and($filter->depends)->toBe(['country_id'])
        ->and($filter->computedDatasource)->toBe('getStatuses')
        ->and($filter->optionValue)->toBe('val')
        ->and($filter->optionLabel)->toBe('txt')
        ->and($filter->params)->toBe(['limit' => 10]);
});
