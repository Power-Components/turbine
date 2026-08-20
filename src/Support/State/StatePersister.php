<?php

namespace PowerComponents\Turbine\Support\State;

use Illuminate\Support\Facades\{Cache, Cookie, Session};

class StatePersister
{
    public function getPersistKeyName(string $tableName, string $prefix = ''): string
    {
        if ($prefix !== '') {
            return 'pg:'.$prefix.'-'.$tableName;
        }

        return 'pg:'.$tableName;
    }

    /**
     * @param  list<string>  $persistItems
     * @param  array<int, mixed>  $columns
     * @param  array<string, mixed>  $filters
     * @param  list<array<string, mixed>>  $enabledFilters
     * @param  array<string, mixed>  $filterBuilder
     * @param  array<string, string>  $sortArray
     */
    public function serializeState(
        array $persistItems,
        string $tableItem,
        array $columns,
        array $filters,
        array $enabledFilters,
        array $filterBuilder,
        ?string $sortField,
        ?string $sortDirection,
        array $sortArray,
        bool $multiSort,
        bool $persistFilterBuilder = false
    ): string {
        $state = [];

        if (in_array('columns', $persistItems) || $tableItem === 'columns') {
            $state['columns'] = collect($columns)
                ->map(fn ($column) => (object) $column)
                ->mapWithKeys(function ($column) {
                    $field = data_get($column, 'field');
                    $key = is_scalar($field) ? (string) $field : '';

                    return [$key => data_get($column, 'hidden')];
                })
                ->all();
        }

        $persistFilters = in_array('filters', $persistItems) || $tableItem === 'filters';

        if ($persistFilters) {
            $state['filters'] = $filters;
            $state['enabledFilters'] = $enabledFilters;
        }

        if (($persistFilters || $persistFilterBuilder) && ! empty($filterBuilder['rows'] ?? [])) {
            $state['filterBuilder'] = $filterBuilder;
        }

        if (in_array('sorting', $persistItems) || $tableItem === 'sorting') {
            $state['sortField'] = $sortField;
            $state['sortDirection'] = $sortDirection;
            $state['sortArray'] = $sortArray;
            $state['multiSort'] = $multiSort;
        }

        return strval(json_encode($state));
    }

    public function save(
        string $key,
        string $jsonState,
        ?string $driver = null,
        ?string $store = null
    ): void {
        $driver ??= config('turbine.persist_driver', 'cookies');
        $storeName = $store ?? config('turbine.persist_driver_store');
        $storeNameStr = is_string($storeName) ? $storeName : null;

        match ($driver) {
            'session' => Session::put($key, $jsonState),
            'cache' => Cache::store($storeNameStr)->put($key, $jsonState),
            default => Cookie::queue($key, $jsonState, 60 * 24 * 365 * 5),
        };
    }

    /**
     * @return array<string, mixed>|null
     */
    public function retrieve(
        string $key,
        ?string $driver = null,
        ?string $store = null
    ): ?array {
        $driver ??= config('turbine.persist_driver', 'cookies');
        $storeName = $store ?? config('turbine.persist_driver_store');
        $storeNameStr = is_string($storeName) ? $storeName : null;

        /** @var string|null $storage */
        $storage = match ($driver) {
            'session' => Session::get($key),
            'cache' => Cache::store($storeNameStr)->get($key),
            default => Cookie::get($key),
        };

        if ($storage === null || $storage === '') {
            return null;
        }

        $decoded = json_decode($storage, true);

        /** @var array<string, mixed>|null $result */
        $result = is_array($decoded) ? $decoded : null;

        return $result;
    }
}
