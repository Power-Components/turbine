<?php

namespace PowerComponents\Turbine\DataSource\Processors\Scout\Pipelines;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\{Str, Stringable};
use Laravel\Scout\Builder as ScoutBuilder;
use PowerComponents\Turbine\Contracts\Context;

final class Search
{
    public function __construct(protected Context $component) {}

    /** @param  ScoutBuilder<Model>  $builder
     * @return ScoutBuilder<Model> */
    public function handle(ScoutBuilder $builder, Closure $next): ScoutBuilder
    {
        $search = $this->component->state()->search;

        if (blank($search)) {
            return $next($builder);
        }

        $builder->query = Str::of($builder->query)
            ->when(
                $search,
                fn (Stringable $self) => $self->prepend($search.',')
            )
            ->toString();

        return $next($builder);
    }
}
