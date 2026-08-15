<?php

namespace PowerComponents\Turbine\DataSource\Processors;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pipeline\Pipeline;
use Laravel\Scout\Builder as ScoutBuilder;
use PowerComponents\Turbine\DataSource\DataTransformer;
use PowerComponents\Turbine\DataSource\Processors\Pipelines as CommonPipelines;
use PowerComponents\Turbine\DataSource\Processors\Scout\Pipelines;

class ScoutBuilderProcessor extends DataSourceBase
{
    public static function match(mixed $key): bool
    {
        return $key instanceof ScoutBuilder;
    }

    /**
     * @param  array<string, mixed>  $properties
     * @return array{results: mixed}
     */
    public function process(array $properties = [], mixed $datasource = null): array
    {
        /** @var ScoutBuilder<Model> $datasource */
        $datasource = $datasource ?? $this->component->datasource($properties);

        /** @var ScoutBuilder<Model> $query */
        $query = app(Pipeline::class)
            ->send($datasource)
            ->through([
                new Pipelines\Search($this->component),
                new Pipelines\Filters($this->component),
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

        $dataTransformer = new DataTransformer($this->component);
        $transformResult = $dataTransformer->transform($paginate->getCollection());

        $paginate->setCollection($transformResult->collection);

        return [
            'results' => $paginate,
        ];
    }
}
