<?php

use PowerComponents\Turbine\{Column, Fields};
use PowerComponents\Turbine\Components\Filters\FilterInputText;
use PowerComponents\Turbine\Export\ExportEngine;
use PowerComponents\Turbine\Support\State\{ArrayGridContext, State};
use PowerComponents\Turbine\Tests\Fixtures\Models\Dish;
use PowerComponents\Turbine\Turbine;

it('extracts export headers correctly from exportable columns', function () {
    $engine = new ExportEngine();

    $columns = [
        Column::make('Name', 'name'),
        Column::make('Secret', 'secret')->hidden(),
        Column::make('Email', 'email')->visibleInExport(true),
    ];

    $headers = $engine->exportHeaders($columns);

    expect($headers)->toBe(['Name', 'Email']);
});

it('does not export forceHidden columns unless visibleInExport is true', function () {
    $engine = new ExportEngine();

    $secret = Column::make('Secret', 'secret')->hidden();
    $secret->hidden = false;

    expect($engine->columnIsExportable($secret))->toBeFalse()
        ->and($engine->columnIsExportable(Column::make('Secret', 'secret')->hidden()->visibleInExport(true)))->toBeTrue();
});

it('jails export file names to a basename under storage', function () {
    $engine = new ExportEngine();

    $context = new ArrayGridContext(
        state: new State(),
        datasourceResolver: fn () => [['id' => 1, 'name' => 'Alice']],
        fields: (new Fields())->add('id')->add('name'),
        columns: [
            Column::make('ID', 'id'),
            Column::make('Name', 'name'),
        ]
    );

    $filePath = $engine->build(
        context: $context,
        exportType: 'csv',
        fileName: '../../public/pwned',
        exportOptions: ['csvSeparator' => ',', 'csvDelimiter' => '"']
    );

    $again = $engine->build(
        context: $context,
        exportType: 'csv',
        fileName: '../../public/pwned',
        exportOptions: ['csvSeparator' => ',', 'csvDelimiter' => '"']
    );

    expect($filePath)->toStartWith(storage_path('pwned-'))
        ->and($filePath)->toEndWith('.csv')
        ->and(dirname($filePath))->toBe(rtrim(storage_path(), DIRECTORY_SEPARATOR))
        ->and($filePath)->not->toBe($again)
        ->and(file_exists($filePath))->toBeTrue();

    @unlink($filePath);
    @unlink($again);
});

it('neutralizes formula injection strings', function () {
    $engine = new ExportEngine();

    expect($engine->neutralizeFormula('=SUM(A1:A10)'))->toBe("'=SUM(A1:A10)")
        ->and($engine->neutralizeFormula('+123'))->toBe("'+123")
        ->and($engine->neutralizeFormula('-123'))->toBe("'-123")
        ->and($engine->neutralizeFormula(' =1+1'))->toBe("' =1+1")
        ->and($engine->neutralizeFormula("\t=cmd"))->toBe("'\t=cmd")
        ->and($engine->neutralizeFormula('Normal Text'))->toBe('Normal Text');
});

it('streams formatted rows and strips HTML tags when requested', function () {
    $engine = new ExportEngine();

    $columns = [
        Column::make('Name', 'name'),
        Column::make('Bio', 'bio'),
    ];

    $data = [
        ['name' => 'John', 'bio' => '<b>Developer</b> &amp; Author'],
        ['name' => '=Formula', 'bio' => '<i>Designer</i>'],
    ];

    $rowsWithTags = iterator_to_array($engine->streamRows($data, $columns, stripTags: false));
    expect($rowsWithTags[0])->toBe(['John', '<b>Developer</b> & Author']);

    $rowsStripped = iterator_to_array($engine->streamRows($data, $columns, stripTags: true));
    expect($rowsStripped[0])->toBe(['John', 'Developer & Author'])
        ->and($rowsStripped[1][0])->toBe("'=Formula");
});

it('builds CSV file using export engine', function () {
    $engine = new ExportEngine();

    $context = new ArrayGridContext(
        state: new State(),
        datasourceResolver: fn () => [
            ['id' => 1, 'name' => 'Alice', 'role' => 'Admin'],
            ['id' => 2, 'name' => 'Bob', 'role' => 'User'],
        ],
        fields: (new Fields())
            ->add('id')
            ->add('name')
            ->add('role'),
        columns: [
            Column::make('ID', 'id'),
            Column::make('Name', 'name'),
            Column::make('Role', 'role'),
        ]
    );

    $fileName = 'test_export_'.time();
    $filePath = $engine->build(
        context: $context,
        exportType: 'csv',
        fileName: $fileName,
        exportOptions: ['csvSeparator' => ',', 'csvDelimiter' => '"']
    );

    expect(file_exists($filePath))->toBeTrue();

    $content = file_get_contents($filePath);
    expect($content)->toContain('ID,Name,Role')
        ->and($content)->toContain('1,Alice,Admin')
        ->and($content)->toContain('2,Bob,User');

    @unlink($filePath);
});

describe('database export scoping', function () {
    $exportGrid = fn (): Turbine => Turbine::make()
        ->datasource(fn () => Dish::query())
        ->fields((new Fields())->add('id')->add('name'))
        ->columns([Column::make('Id', 'id'), Column::make('Name', 'name')])
        ->filters([new FilterInputText('name')])
        ->tableName('dishes');

    $exportedNames = fn (Turbine $grid): array => collect(
        (new ExportEngine())->prepareDataset($grid->context(), [])
    )->pluck('name')->sort()->values()->all();

    it('applies transformQuery', function () use ($exportGrid, $exportedNames) {
        $grid = $exportGrid()->transformQuery(fn ($query) => $query->where('name', 'Pizza'));

        expect($exportedNames($grid))->toBe(['Pizza']);
    });

    it('applies column raw queries', function () use ($exportedNames) {
        $price = Column::make('Price', 'price');
        $price->rawQueries = [['sql' => 'dishes.price > ?', 'bindings' => [25]]];

        $grid = Turbine::make()
            ->datasource(fn () => Dish::query())
            ->fields((new Fields())->add('id')->add('name'))
            ->columns([Column::make('Name', 'name'), $price])
            ->tableName('dishes');

        expect($exportedNames($grid))->toBe(['Pizza', 'Sushi']);
    });

    it('keeps orWhere raw queries inside the datasource constraints', function () use ($exportedNames) {
        $name = Column::make('Name', 'name');
        $name->rawQueries = [[
            'method' => 'orWhereRaw',
            'sql' => 'dishes.name LIKE ?',
            'bindings' => [fn ($context) => '%'.$context->state()->search.'%'],
        ]];

        $grid = Turbine::make()
            ->datasource(fn () => Dish::query()->where('in_stock', false))
            ->fields((new Fields())->add('id')->add('name'))
            ->columns([Column::make('Id', 'id'), $name])
            ->tableName('dishes')
            ->state(['search' => 'Pastel']);

        expect($exportedNames($grid))->toBe(['Pastel de Belém']);
    });

    it('applies the filter builder', function () use ($exportGrid, $exportedNames) {
        $grid = $exportGrid()
            ->setUp([Turbine::filterBuilder()])
            ->state(['filterBuilder' => [
                'match' => 'and',
                'rows' => [['column' => 'name', 'operator' => 'starts_with', 'value' => 'Pastel', 'boolean' => 'and']],
            ]]);

        expect($exportedNames($grid))->toBe(['Pastel de Belém', 'Pastel de Nata']);
    });

    it('still applies search and filters', function () use ($exportGrid, $exportedNames) {
        $grid = $exportGrid()->state(['search' => 'Pastel', 'filters' => [
            'name' => ['type' => 'input_text', 'value' => 'Nata', 'op' => 'contains'],
        ]]);

        expect($exportedNames($grid))->toBe(['Pastel de Nata']);
    });

    it('scopes selected rows by the datasource table, not the component table name', function () {
        $grid = Turbine::make()
            ->datasource(fn () => Dish::query())
            ->fields((new Fields())->add('id')->add('name'))
            ->columns([Column::make('Id', 'id'), Column::make('Name', 'name')])
            ->tableName('dishTable');

        $ids = Dish::query()->whereIn('name', ['Pizza', 'Sushi'])->pluck('id')->all();

        $names = collect((new ExportEngine())->prepareDataset($grid->context(), ['selectedKeys' => $ids], selected: true))
            ->pluck('name')->sort()->values()->all();

        expect($names)->toBe(['Pizza', 'Sushi']);
    });
});
