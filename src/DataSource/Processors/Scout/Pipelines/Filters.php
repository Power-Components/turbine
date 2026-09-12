<?php

namespace PowerComponents\Turbine\DataSource\Processors\Scout\Pipelines;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Builder as ScoutBuilder;
use PowerComponents\Turbine\Contracts\Context;
use PowerComponents\Turbine\Support\FilterBag;

final class Filters
{
    public function __construct(protected Context $component) {}

    /** @param  ScoutBuilder<Model>  $builder
     * @return ScoutBuilder<Model> */
    public function handle(ScoutBuilder $builder, Closure $next): ScoutBuilder
    {
        $filterDefinitions = collect($this->component->declaredFilters());
        $filters = $this->component->state()->filters;

        if ($filterDefinitions->isEmpty() || empty($filters)) {
            return $next($builder);
        }

        foreach ($filters as $bagKey => $record) {
            if (! FilterBag::isRecord($record) || ! FilterBag::isActive($record)) {
                continue;
            }

            $bagKey = (string) $bagKey;
            $filter = $filterDefinitions->first(
                fn ($definition) => FilterBag::matchesDefinition($definition, $bagKey)
            );

            if ($filter === null) {
                continue;
            }

            $builder->where(FilterBag::sqlField($filter, $bagKey), $record['value'] ?? null);
        }

        return $next($builder);
    }
}
