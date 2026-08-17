<?php

namespace PowerComponents\Turbine\DataSource\Processors\Pipelines;

use Closure;
use Illuminate\Pagination\{LengthAwarePaginator, Paginator};
use Laravel\Scout\Builder as ScoutBuilder;
use PowerComponents\Turbine\Contracts\Context;

class Pagination
{
    public function __construct(protected Context $component) {}

    /** @return LengthAwarePaginator<int, mixed>|Paginator<int, mixed> */
    public function handle(mixed $query, Closure $next): LengthAwarePaginator|Paginator
    {
        $setUp = $this->component->state()->setUp;
        $paginateRaw = $this->component->state()->paginateRaw;

        /** @var string $pageName */
        $pageName = data_get($setUp, 'footer.pageName', 'page');
        /** @var mixed $perPageFromSetup */
        $perPageFromSetup = data_get($setUp, 'footer.perPage');
        $perPage = is_numeric($perPageFromSetup) ? (int) $perPageFromSetup : 10;
        $maxConfig = config('turbine.max_per_page', 1000);
        $maxPerPage = is_numeric($maxConfig) ? (int) $maxConfig : 1000;
        if ($maxPerPage > 0 && $perPage > $maxPerPage) {
            $perPage = $maxPerPage;
        }
        /** @var string $recordCount */
        $recordCount = data_get($setUp, 'footer.recordCount');

        if ($query instanceof ScoutBuilder) {
            $paginate = match (true) {
                $recordCount == 'min' => 'simplePaginate',
                ($paginateRaw && $recordCount == 'min') => 'simplePaginateRaw', // @phpstan-ignore-line
                $paginateRaw => 'paginateRaw',
                default => 'paginateSafe',
            };
        } else {
            $paginate = match (true) {
                $recordCount === 'min' => 'simplePaginate',
                default => 'paginate',
            };
        }

        /** @var mixed $query */
        if ($perPage > 0) {
            return $query->$paginate($perPage, pageName: $pageName); // @phpstan-ignore-line
        }

        $count = $query->count(); // @phpstan-ignore-line

        $this->component->resetToFirstPage($pageName);

        $targetPerPage = $count ?: 10;
        if ($maxPerPage > 0 && $targetPerPage > $maxPerPage) {
            $targetPerPage = $maxPerPage;
        }

        return $query->$paginate($targetPerPage, pageName: $pageName); // @phpstan-ignore-line
    }
}
