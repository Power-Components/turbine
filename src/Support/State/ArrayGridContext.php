<?php

namespace PowerComponents\Turbine\Support\State;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use PowerComponents\Turbine\{Button, Fields};
use PowerComponents\Turbine\Components\Filters\FilterBase;
use PowerComponents\Turbine\Components\Rules\BaseRule;
use PowerComponents\Turbine\Concerns\State\{ResolvesGridSorting, ResolvesSummaries};
use PowerComponents\Turbine\Contracts\Context;

final class ArrayGridContext implements Context
{
    use ResolvesGridSorting;
    use ResolvesSummaries;

    private string $currentTable = '';

    /** @var list<int|string> */
    private array $filteredKeys = [];

    /** @var array<string, mixed> */
    private array $summaryValues = [];

    /** @var array<int, mixed>|Closure(): array<int, mixed> */
    private array|Closure $columns = [];

    /**
     * @param  array<int, mixed>|Closure(): array<int, mixed>  $columns
     * @param  array<int, FilterBase>  $filters
     * @param  array<string, list<string>|string>  $relationSearch
     * @param  array<string, string>  $searchMorphs
     */
    public function __construct(
        private readonly State $state,
        private readonly Closure $datasourceResolver,
        private readonly Fields $fields,
        array|Closure $columns = [],
        private readonly array $filters = [],
        private readonly ?Closure $transformQueryResolver = null,
        private readonly array $relationSearch = [],
        private readonly array $searchMorphs = [],
        private readonly ?Closure $actionsResolver = null,
        private readonly ?Closure $actionRulesResolver = null,
    ) {
        $this->columns = $columns;
    }

    /** @return array<int, Button> */
    public function actions(object $row): array
    {
        return $this->actionsResolver !== null ? (array) ($this->actionsResolver)($row) : [];
    }

    /** @return array<int, BaseRule> */
    public function actionRules(object $row): array
    {
        return $this->actionRulesResolver !== null ? (array) ($this->actionRulesResolver)($row) : [];
    }

    public function state(): State
    {
        if (empty($this->state->columns) && ! empty($declared = $this->declaredColumns())) {
            return $this->state->withColumns(array_values($declared));
        }

        return $this->state;
    }

    public function datasource(mixed ...$args): mixed
    {
        return ($this->datasourceResolver)(...$args);
    }

    /** @return array<int, mixed> */
    public function declaredColumns(): array
    {
        if ($this->columns instanceof Closure) {
            $this->columns = ($this->columns)();
        }

        /** @var array<int, mixed> $columns */
        $columns = $this->columns;

        return $columns;
    }

    public function hasResolvedColumns(): bool
    {
        return ! ($this->columns instanceof Closure);
    }

    /** @return array<int, FilterBase> */
    public function declaredFilters(): array
    {
        return $this->filters;
    }

    /** @return array<string, list<string>|string> */
    public function relationSearch(): array
    {
        return $this->relationSearch;
    }

    /** @return array<string, string> */
    public function searchMorphs(): array
    {
        return $this->searchMorphs;
    }

    public function transformQuery(mixed $query): mixed
    {
        return $this->transformQueryResolver !== null
            ? ($this->transformQueryResolver)($query)
            : $query;
    }

    public function beforeFilterBuilderApply(mixed $query, array $conditions): mixed
    {
        return $query;
    }

    public function applyBeforeSearch(string $field, ?string $search): ?string
    {
        return $search;
    }

    public function summariesCacheTag(): string
    {
        $principal = auth()->id();

        if ($principal === null || $principal === '') {
            $sessionId = session()->getId();
            $principal = is_string($sessionId) && $sessionId !== '' ? $sessionId : 'anon';
        }

        $suffix = (string) $principal;

        try {
            $query = ($this->datasourceResolver)();

            if ($query instanceof Builder) {
                $query = $query->toBase();
            }

            if (is_object($query) && method_exists($query, 'toSql') && method_exists($query, 'getBindings')) {
                $suffix .= '-'.hash('sha256', $query->toSql().'|'.serialize($query->getBindings()));
            }
        } catch (\Throwable) {
        }

        /** @var mixed $secret */
        $secret = config('turbine.cache_tag_secret');

        if (! is_string($secret) || $secret === '') {
            $secret = config('app.key');
        }

        if (is_string($secret) && $secret !== '') {
            $suffix = substr(hash_hmac('sha256', $suffix, $secret), 0, 32);
        }

        return 'turbine-headless-'.$this->state->tableName.'-'.$suffix;
    }

    public function summariesCacheKey(): string
    {
        return hash('sha256', json_encode([
            'search' => $this->state->search,
            'filters' => $this->state->filters,
            'filterBuilder' => $this->state->filterBuilder,
        ]) ?: '');
    }

    public function fields(): Fields
    {
        return $this->fields;
    }

    public function shouldCollectActions(): bool
    {
        return false;
    }

    /** @return array<mixed> */
    public function prepareActionRulesForRows(mixed $row, ?object $loop = null): array
    {
        return [];
    }

    /** @return list<array<string, mixed>> */
    public function resolveActionRules(mixed $row): array
    {
        return [];
    }

    public function getCurrentTable(): string
    {
        return $this->currentTable;
    }

    public function setCurrentTable(string $table): void
    {
        $this->currentTable = $table;
    }

    /** @param  list<int|string>  $keys */
    public function setFilteredKeys(array $keys): void
    {
        $this->filteredKeys = $keys;
    }

    /** @return list<int|string> */
    public function getFilteredKeys(): array
    {
        return $this->filteredKeys;
    }

    /** @param  array<string, mixed>  $values */
    public function setSummaryValues(array $values): void
    {
        $this->summaryValues = $values;
    }

    /** @return array<string, mixed> */
    public function getSummaryValues(): array
    {
        return $this->summaryValues;
    }

    public function resetToFirstPage(string $pageName = 'page'): void {}
}
