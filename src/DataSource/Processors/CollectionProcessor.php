<?php

namespace PowerComponents\Turbine\DataSource\Processors;

use Illuminate\Pagination\{LengthAwarePaginator, Paginator};
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\{Collection, Collection as BaseCollection};
use PowerComponents\Turbine\DataSource\DataTransformer;
use PowerComponents\Turbine\DataSource\Processors\Collection\Pipelines;
use PowerComponents\Turbine\DataSource\Processors\Pipelines as CommonPipelines;

class CollectionProcessor extends DataSourceBase
{
    public static function match(mixed $key): bool
    {
        return $key instanceof Collection
            || is_iterable($key);
    }

    public function resolveTable(mixed $datasource): ?string
    {
        if ($datasource instanceof BaseCollection && $datasource->isNotEmpty()) {
            $first = $datasource->first();
            if (is_object($first) && method_exists($first, 'getTable')) {
                /** @var string $table */
                $table = $first->getTable();

                return $table;
            }
        }

        if (is_array($datasource) && count($datasource) > 0) {
            $first = reset($datasource);
            if (is_object($first) && method_exists($first, 'getTable')) {
                /** @var string $table */
                $table = $first->getTable();

                return $table;
            }
        }

        return parent::resolveTable($datasource);
    }

    /**
     * @param  array<string, mixed>  $properties
     * @return array{results: mixed, actionsByRow: array<int|string, list<array<string, mixed>>>}
     */
    public function process(array $properties = [], mixed $datasource = null): array
    {
        $datasource = $datasource ?? $this->component->datasource($properties);

        /** @var array<int, mixed>|BaseCollection<int, mixed> $datasource */
        $collection = new BaseCollection($datasource);

        /** @var BaseCollection<int, mixed> $results */
        $results = app(Pipeline::class)
            ->send($collection)
            ->through([
                new Pipelines\GlobalSearch($this->component),
                new Pipelines\Filters($this->component),
                new Pipelines\Sorting($this->component),
                new CommonPipelines\Summaries($this->component),
            ])
            ->thenReturn();

        $results = $this->component->transformQuery($results);

        /** @var BaseCollection<int, mixed> $results */
        $paginated = $results;
        $dataTransformer = new DataTransformer($this->component);
        $actionsByRow = [];
        $timeInMs = 0;

        if ($results->count() > 0) {
            $plucked = $results->pluck($this->component->state()->primaryKey)->values();
            /** @var list<int|string> $filtered */
            $filtered = $plucked->toArray();
            $this->component->setFilteredKeys($filtered);
            $paginated = $this->paginate($results);

            $transformResult = $dataTransformer->transform($paginated->getCollection());
            $actionsByRow = $transformResult->getActionsByRow();

            $paginated->setCollection($transformResult->getCollection());
        }

        return [
            'results' => $paginated,
            'actionsByRow' => $actionsByRow,
        ];
    }

    /** @param  BaseCollection<int, mixed>  $results
     * @return LengthAwarePaginator<int, mixed> */
    private function paginate(BaseCollection $results): LengthAwarePaginator
    {
        /** @var mixed $perPageFromSetup */
        $perPageFromSetup = data_get($this->component->state()->setUp, 'footer.perPage', 10);
        $perPageVal = is_numeric($perPageFromSetup) ? (int) $perPageFromSetup : 10;
        $perPage = $this->isExport
            ? $results->count()
            : $perPageVal;

        $maxConfig = config('turbine.max_per_page', 1000);
        $maxPerPage = is_numeric($maxConfig) ? (int) $maxConfig : 1000;
        if (! $this->isExport && $maxPerPage > 0 && $perPage > $maxPerPage) {
            $perPage = $maxPerPage;
        }

        if ($perPage <= 0) {
            $perPage = $results->count();
        }
        /** @var string $pageName */
        $pageName = data_get($this->component->state()->setUp, 'footer.pageName', 'page');

        $page = Paginator::resolveCurrentPage($pageName);

        return new LengthAwarePaginator(
            items: $results->forPage($page, $perPage),
            total: $results->count(),
            perPage: $perPage,
            currentPage: $page,
            options: [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => $pageName,
            ]
        );
    }
}
