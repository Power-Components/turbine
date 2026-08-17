<?php

namespace PowerComponents\Turbine\DataSource\Processors;

use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Pipeline\Pipeline;
use PowerComponents\Turbine\DataSource\DataTransformer;
use PowerComponents\Turbine\DataSource\Processors\Database\Pipelines;
use PowerComponents\Turbine\DataSource\Processors\Pipelines as CommonPipelines;

class ModelProcessor extends DataSourceBase
{
    public static function match(mixed $key): bool
    {
        return true;
    }

    /**
     * @param  array<string, mixed>  $properties
     * @return array{results: mixed, actionsByRow: array<int|string, list<array<string, mixed>>>}
     */
    public function process(array $properties = [], mixed $datasource = null): array
    {
        $datasource = $datasource ?? $this->component->datasource($properties);

        $this->setCurrentTable($datasource);

        $query = app(Pipeline::class)
            ->send($datasource)
            ->through([
                new Pipelines\Filters($this->component),
                new Pipelines\SoftDeletes($this->component),
                new Pipelines\ColumnRawQueries($this->component),
                new Pipelines\SelectColumns($this->component, $this->isExport),
                new CommonPipelines\Summaries($this->component),
                new Pipelines\Sorting($this->component),
            ])
            ->thenReturn();

        $query = $this->component->transformQuery($query);

        $paginate = app(Pipeline::class)
            ->send($query)
            ->through([
                new CommonPipelines\Pagination($this->component),
            ])
            ->thenReturn();

        /** @var AbstractPaginator<int, mixed> $paginate */
        $collection = $paginate->getCollection();

        $dataTransformer = new DataTransformer($this->component);
        $transformResult = $dataTransformer->transform($collection);

        return [
            'results' => $paginate->setCollection($transformResult->getCollection()),
            'actionsByRow' => $transformResult->getActionsByRow(),
        ];
    }
}
