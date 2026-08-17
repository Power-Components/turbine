<?php

namespace PowerComponents\Turbine\Export;

use Generator;
use Illuminate\Database\Eloquent;
use Illuminate\Database\Eloquent\{Builder, Model};
use Illuminate\Support\{Collection, LazyCollection, Str};
use PowerComponents\Turbine\Components\SetUp\Exportable;
use PowerComponents\Turbine\Contracts\Context;
use PowerComponents\Turbine\DataSource\{DataTransformer, ProcessDataSource};
use PowerComponents\Turbine\DataSource\Processors\Database\Handlers\{FilterHandler, SearchHandlerContract};
use PowerComponents\Turbine\DataSource\Support\Sql;
use PowerComponents\Turbine\Export\Drivers\{CsvExporter, ExportDriverInterface, XlsExporter};

class ExportEngine
{
    /** @var array<string, class-string<ExportDriverInterface>> */
    protected array $drivers = [
        'csv' => CsvExporter::class,
        'xls' => XlsExporter::class,
        'xlsx' => XlsExporter::class,
    ];

    /**
     * @param  Exportable|array<string, mixed>  $exportOptions
     */
    public function build(
        Context $context,
        string $exportType,
        string $fileName,
        Exportable|array $exportOptions,
        bool $selected = false
    ): string {
        $driverClass = $this->drivers[strtolower($exportType)] ?? CsvExporter::class;
        /** @var ExportDriverInterface $driver */
        $driver = new $driverClass();

        $columns = $this->prepareColumns($context);
        $dataset = $this->prepareDataset($context, $exportOptions, $selected);
        $stripTags = boolval(data_get($exportOptions, 'stripTags', false));

        $headers = $this->exportHeaders($columns);
        $rows = $this->streamRows($dataset, $columns, $stripTags);

        $ext = strtolower($exportType) === 'csv' ? 'csv' : 'xlsx';
        $fullPath = storage_path($fileName.'.'.$ext);

        $driver->build($fullPath, $headers, $rows, $exportOptions);

        return $fullPath;
    }

    /**
     * @return array<int, mixed>
     */
    public function prepareColumns(Context $context): array
    {
        $declaredColumns = $context->declaredColumns();
        $stateColumns = $context->state()->columns;

        $currentHiddenStates = [];
        foreach ($stateColumns as $col) {
            $field = data_get($col, 'field');
            if (is_string($field)) {
                $currentHiddenStates[$field] = data_get($col, 'hidden');
            }
        }

        return array_map(function ($column) use ($currentHiddenStates) {
            $field = data_get($column, 'field');
            if (is_string($field) && isset($currentHiddenStates[$field])) {
                data_set($column, 'hidden', $currentHiddenStates[$field]);
            }

            return $column;
        }, $declaredColumns);
    }

    /**
     * @param  Exportable|array<string, mixed>  $exportOptions
     * @return Eloquent\Collection<int, mixed>|Collection<int, mixed>|LazyCollection<int, mixed>
     */
    public function prepareDataset(
        Context $context,
        Exportable|array $exportOptions,
        bool $selected = false
    ): Eloquent\Collection|Collection|LazyCollection {
        $processDataSource = ProcessDataSource::make($context);
        $datasource = $processDataSource->resolveDatasource();

        $state = $context->state();
        /** @var array<int, mixed> $filtered */
        $filtered = [];

        if ($selected) {
            /** @var array<int, mixed> $filtered */
            $filtered = (array) data_get($exportOptions, 'selectedKeys', []);
        }

        if ($datasource instanceof Collection || is_array($datasource)) {
            $processed = $processDataSource->get(isExport: true);
            /** @var mixed $results */
            $results = data_get($processed, 'results', $processed);

            if (is_object($results) && method_exists($results, 'getCollection')) {
                $results = $results->getCollection();
            }

            /** @var Collection<int, mixed> $collection */
            $collection = $results instanceof Collection ? $results : collect(is_iterable($results) ? $results : []);

            if (! empty($filtered)) {
                $primaryKey = $context->state()->primaryKey;
                $collection = $collection->whereIn($primaryKey, $filtered)->values();
            }

            $dataTransformer = new DataTransformer($context);

            return $dataTransformer->transform($collection)->collection;
        }

        $currentTable = method_exists($context, 'currentTable') ? $context->currentTable() : $state->tableName;

        $property = function (string $prop) use ($context, $currentTable) {
            $value = $prop === 'primaryKey' ? $context->state()->primaryKey : data_get($context, $prop);
            $valueStr = is_scalar($value) ? (string) $value : '';

            return Str::of($valueStr)->contains('.')
                ? $valueStr
                : $currentTable.'.'.$valueStr;
        };

        /** @var array<string, mixed> $queryOptions */
        $queryOptions = (array) data_get($exportOptions, 'queryOptions', []);

        /** @var Builder<Model> $datasourceQuery */
        $datasourceQuery = $processDataSource->datasource;

        $results = $datasourceQuery
            ->where(function ($query) use ($context) {
                app()->makeWith(SearchHandlerContract::class, [
                    'component' => $context,
                ])->apply($query);
                (new FilterHandler($context))->apply($query);
            })
            ->when(! empty($filtered), function ($query) use ($property, $filtered) {
                return $query->whereIn($property('primaryKey'), $filtered);
            })
            ->when($state->sortField, function ($query) use ($state, $queryOptions) {
                $sortField = $queryOptions['sortField'] ?? $state->sortField;

                if (! is_string($sortField)) {
                    return $query;
                }

                $sortDirection = $state->sortDirection;

                if (is_string($queryOptions['sortDirection'] ?? null)) {
                    $sortDirection = $queryOptions['sortDirection'];
                }

                $sanitizedDirection = Sql::sanitizeSortDirection($sortDirection);
                /** @var 'asc'|'desc' $dir */
                $dir = in_array($sanitizedDirection, ['asc', 'desc'], true) ? $sanitizedDirection : 'asc';

                return $query->orderBy($sortField, $dir);
            })
            ->cursor();

        $dataTransformer = new DataTransformer($context);

        return $dataTransformer->transformForExport($results);
    }

    /**
     * @param  array<int, mixed>  $columns
     * @return list<string>
     */
    public function exportHeaders(array $columns): array
    {
        $headers = [];

        foreach ($columns as $column) {
            if (! $this->columnIsExportable($column)) {
                continue;
            }

            $title = data_get($column, 'title');
            $headers[] = is_string($title) ? $title : '';
        }

        return $headers;
    }

    /**
     * @param  iterable<int, mixed>  $data
     * @param  array<int, mixed>  $columns
     * @return Generator<int, list<mixed>>
     */
    public function streamRows(iterable $data, array $columns, bool $stripTags): Generator
    {
        $exportableColumns = collect($columns)
            ->filter(fn ($column): bool => $this->columnIsExportable($column))
            ->values();

        foreach ($data as $row) {
            if (is_object($row) && method_exists($row, 'withoutRelations')) {
                $row = $row->withoutRelations()->toArray();
            }

            $row = (array) $row;

            $values = [];

            foreach ($exportableColumns as $column) {
                /** @var string $field */
                $field = data_get($column, 'field');

                $value = data_get($row, $field, '');
                $value = is_scalar($value) ? (string) $value : '';

                if ($stripTags) {
                    $value = strip_tags($value);
                }

                $values[] = $this->neutralizeFormula(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            }

            yield $values;
        }
    }

    public function neutralizeFormula(string $value): string
    {
        if ($value === '') {
            return $value;
        }

        $first = $value[0];

        if (in_array($first, ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$value;
        }

        return $value;
    }

    public function columnIsExportable(mixed $column): bool
    {
        return (bool) data_get($column, 'visibleInExport')
            || (! data_get($column, 'hidden') && is_null(data_get($column, 'visibleInExport')));
    }
}
