<?php

namespace PowerComponents\Turbine\DataSource\Processors\Database\Pipelines;

use Closure;
use Illuminate\Database\Eloquent\{Builder as EloquentBuilder, Model};
use Illuminate\Database\Query\Builder as QueryBuilder;
use PowerComponents\Turbine\Contracts\Context;
use PowerComponents\Turbine\DataSource\Processors\Database\Handlers\{FilterHandler, SearchHandlerContract};
use PowerComponents\Turbine\Plugins\FilterBuilder\FilterBuilderHandler;

class Filters
{
    public function __construct(protected Context $component) {}

    public function handle(mixed $query, Closure $next): mixed
    {
        /** @var EloquentBuilder<Model>|QueryBuilder $query */
        app()->makeWith(SearchHandlerContract::class, [
            'component' => $this->component,
        ])->apply($query);
        (new FilterHandler($this->component))->apply($query);

        $filterBuilder = new FilterBuilderHandler($this->component);

        if ($filterBuilder->isActive()) {
            $filterBuilder->apply($query);
        }

        return $next($query);
    }
}
