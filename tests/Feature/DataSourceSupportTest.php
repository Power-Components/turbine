<?php

namespace PowerComponents\Turbine\Tests\Feature;

use PowerComponents\Turbine\DataSource\Support\{FilterNormalizer, InputOperators, Sql};

class TestInputOperatorsFeatureClass
{
    use InputOperators;
}

it('normalizes filter inputs correctly with FilterNormalizer', function () {
    $rawFilters = [
        'input_text' => [
            'name' => 'John',
            'user.email' => 'john@example.com',
        ],
        'multi_select' => [
            'category_id.0' => 1,
            'category_id.1' => 2,
            'category_id.2' => 5,
        ],
        'number' => [
            'price.start' => 10,
            'price.end' => 100,
        ],
    ];

    $normalized = FilterNormalizer::normalize($rawFilters);

    expect($normalized['input_text.name'])->toBe('John')
        ->and($normalized['input_text.user.email'])->toBe('john@example.com')
        ->and($normalized['multi_select.category_id'])->toBe([1, 2, 5])
        ->and($normalized['number.price'])->toBe(['start' => 10, 'end' => 100]);
});

it('validates input text options with InputOperators trait', function () {
    $class = new TestInputOperatorsFeatureClass();

    $validFilter = [
        'input_text_options' => [
            'name' => 'starts_with',
        ],
    ];
    expect($class->validateInputTextOptions($validFilter, 'name'))->toBe('starts_with');

    $arrayFilter = [
        'input_text_options' => [
            'title' => ['starts_with', 'contains'],
        ],
    ];
    expect($class->validateInputTextOptions($arrayFilter, 'title'))->toBe('starts_with');

    $invalidFilter = [
        'input_text_options' => [
            'code' => 'sql_injection_attempt',
        ],
    ];
    expect($class->validateInputTextOptions($invalidFilter, 'code'))->toBe('contains');
});

it('sanitizes sort directions using Sql helper', function () {
    expect(Sql::sanitizeSortDirection('asc'))->toBe('asc')
        ->and(Sql::sanitizeSortDirection('DESC'))->toBe('desc')
        ->and(Sql::sanitizeSortDirection(' invalid '))->toBe('asc')
        ->and(Sql::sanitizeSortDirection(null))->toBe('asc');
});

it('validates sort field types using Sql helper', function () {
    expect(Sql::isValidSortFieldType('string'))->toBeTrue()
        ->and(Sql::isValidSortFieldType('varchar'))->toBeTrue()
        ->and(Sql::isValidSortFieldType('char'))->toBeTrue()
        ->and(Sql::isValidSortFieldType('integer'))->toBeFalse()
        ->and(Sql::isValidSortFieldType(null))->toBeFalse();
});

it('generates sort SQL by database driver using Sql helper', function () {
    // MySQL
    $mysqlDefault = Sql::getSortSqlByDriver('price', 'mysql', '5.7.0');
    expect($mysqlDefault)->toBe('price+0 {sortDirection}');

    $mysql8 = Sql::getSortSqlByDriver('price', 'mysql', '8.0.5');
    expect($mysql8)->toContain('REGEXP_REPLACE');

    // SQLite
    $sqlite = Sql::getSortSqlByDriver('price', 'sqlite', '3.35.0');
    expect($sqlite)->toBe('CAST(price AS INTEGER) {sortDirection}');

    // Postgres
    $pgsql = Sql::getSortSqlByDriver('price', 'pgsql', '13.0');
    expect($pgsql)->toContain('REGEXP_REPLACE');

    // Unknown driver fallback
    $unknown = Sql::getSortSqlByDriver('price', 'oracle', '1.0');
    expect($unknown)->toBe('price+0 {sortDirection}');
});

it('throws exception when getSortSqlByDriver receives empty arguments', function () {
    Sql::getSortSqlByDriver('', 'mysql', '8.0');
})->throws(\Exception::class, 'sortField, driverName and driverVersion must be informed');

it('returns correct like syntax per driver', function () {
    expect(Sql::like(null))->toBe('LIKE');
});
