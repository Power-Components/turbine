<?php

use PowerComponents\Turbine\{Column, Fields};
use PowerComponents\Turbine\Export\ExportEngine;
use PowerComponents\Turbine\Support\State\{ArrayGridContext, State};

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

it('neutralizes formula injection strings', function () {
    $engine = new ExportEngine();

    expect($engine->neutralizeFormula('=SUM(A1:A10)'))->toBe("'=SUM(A1:A10)")
        ->and($engine->neutralizeFormula('+123'))->toBe("'+123")
        ->and($engine->neutralizeFormula('-123'))->toBe("'-123")
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
