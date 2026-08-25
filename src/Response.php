<?php

namespace PowerComponents\Turbine;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\{AbstractPaginator, LengthAwarePaginator};
use Illuminate\Support\Collection;
use PowerComponents\Turbine\Contracts\Context;
use PowerComponents\Turbine\DataSource\ProcessDataSource;
use PowerComponents\Turbine\Response\{ColumnSchema, FilterSchema, GridResponse, MetaResponse, PaginationResponse, SortResponse};
use PowerComponents\Turbine\Support\Actions\ActionsResolver;

final readonly class Response
{
    public function __construct(private Context $context) {}

    public static function make(Context $context): self
    {
        return new self($context);
    }

    /**
     * @throws \Throwable
     */
    public function envelope(?ActionsResolver $actionsResolver = null): GridResponse
    {
        $results = ProcessDataSource::make($this->context)->get()['results'];

        $items = $this->items($results);
        $primaryKey = $this->context->state()->primaryKey;
        $actionsResolver ??= new ActionsResolver($this->context);

        $data = [];
        $actions = [];

        foreach ($items as $item) {
            $row = is_object($item) ? $item : (object) $item;
            $data[] = $this->rowToArray($row);

            $resolved = $actionsResolver->forRow($row);

            if ($resolved !== []) {
                $key = data_get($row, $primaryKey);

                if (is_scalar($key)) {
                    $actions[(string) $key] = $resolved;
                }
            }
        }

        $filters = $this->filtersSchema();

        return new GridResponse(
            data: $data,
            meta: $this->meta($results),
            columns: $this->columnsSchema(),
            filters: $filters !== [] ? $filters : null,
            actions: $actions !== [] ? $actions : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(?ActionsResolver $actionsResolver = null): array
    {
        return $this->envelope($actionsResolver)->all();
    }

    public function toResponse(?ActionsResolver $actionsResolver = null): JsonResponse
    {
        return new JsonResponse($this->envelope($actionsResolver));
    }

    /**
     * @return AbstractPaginator<int|string, array<string, mixed>>
     */
    public function toPaginator(): AbstractPaginator
    {
        $results = ProcessDataSource::make($this->context)->get()['results'];

        $paginator = $results instanceof AbstractPaginator
            ? $results
            : $this->wrapInPaginator($this->items($results));

        return $paginator->through(
            fn (mixed $item): array => $this->rowToArray(is_object($item) ? $item : (object) $item)
        );
    }

    /**
     * @param  Collection<int, mixed>  $items
     * @return AbstractPaginator<int, mixed>
     */
    private function wrapInPaginator(Collection $items): AbstractPaginator
    {
        return new LengthAwarePaginator($items, $items->count(), max($items->count(), 1), 1);
    }

    /**
     * @return Collection<int, mixed>
     */
    private function items(mixed $results): Collection
    {
        if ($results instanceof AbstractPaginator) {
            /** @var Collection<int, mixed> $collection */
            $collection = $results->getCollection();

            return $collection;
        }

        if ($results instanceof Collection) {
            return $results;
        }

        $value = $results instanceof Arrayable ? $results->toArray() : $results;

        return collect(is_iterable($value) ? $value : []);
    }

    private function asString(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }

    /**
     * @return array<string, mixed>
     */
    private function rowToArray(object $row): array
    {
        $data = $row instanceof Model ? $row->toArray() : (array) $row;

        foreach (array_keys($data) as $key) {
            if (str_starts_with((string) $key, '__turbine')) {
                unset($data[$key]);
            }
        }

        $allowed = $this->allowedOutputFields();

        if ($allowed !== null) {
            $data = array_intersect_key($data, $allowed);
        }

        return $data;
    }

    /**
     * @return array<string, bool>|null
     */
    private function allowedOutputFields(): ?array
    {
        if (! $this->context->hasResolvedColumns()) {
            return null;
        }

        $state = $this->context->state();
        $allowed = [$state->primaryKey => true];

        if (is_string($state->primaryKeyAlias) && $state->primaryKeyAlias !== '') {
            $allowed[$state->primaryKeyAlias] = true;
        }

        foreach (array_keys($this->context->fields()->fields) as $fieldKey) {
            if (is_string($fieldKey) && $fieldKey !== '') {
                $allowed[$fieldKey] = true;
            }
        }

        foreach ($this->context->declaredColumns() as $column) {
            foreach (['dataField', 'field'] as $key) {
                $value = data_get($column, $key);

                if (is_string($value) && $value !== '') {
                    $allowed[$value] = true;

                    if (str_contains($value, '.')) {
                        $allowed[explode('.', $value)[0]] = true;
                    }
                }
            }
        }

        return $allowed;
    }

    private function meta(mixed $results): MetaResponse
    {
        $state = $this->context->state();

        $pagination = new PaginationResponse(
            currentPage: 1,
            perPage: 1,
        );

        if ($results instanceof AbstractPaginator) {
            $pagination = new PaginationResponse(
                currentPage: $results->currentPage(),
                perPage: $results->perPage(),
                from: $results->firstItem(),
                to: $results->lastItem(),
                total: $results instanceof LengthAwarePaginator ? $results->total() : null,
                lastPage: $results instanceof LengthAwarePaginator ? $results->lastPage() : null,
            );
        }

        return new MetaResponse(
            pagination: $pagination,
            sort: new SortResponse(
                field: $state->sortField,
                direction: $state->sortDirection,
                multiSort: $state->multiSort,
                sortArray: $state->sortArray,
            ),
            search: $state->search,
            filters: $state->filters,
            filterBuilder: $state->filterBuilder,
            setup: $this->setup(),
        );
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function setup(): array
    {
        $out = [];

        /** @var mixed $config */
        foreach ($this->context->state()->setUp as $name => $config) {
            $out[(string) $name] = match (true) {
                is_object($config) => get_object_vars($config),
                is_array($config) => $config,
                default => [],
            };
        }

        return $out;
    }

    /**
     * @return list<ColumnSchema>
     */
    private function columnsSchema(): array
    {
        if (! $this->context->hasResolvedColumns()) {
            return [];
        }

        $schema = [];

        foreach ($this->context->declaredColumns() as $column) {
            if (data_get($column, 'isAction') === true) {
                continue;
            }

            $field = $this->asString(data_get($column, 'dataField') ?: data_get($column, 'field'));

            if ($field === '') {
                continue;
            }

            $schema[] = new ColumnSchema(
                field: $field,
                title: $this->asString(data_get($column, 'title')),
                sortable: (bool) data_get($column, 'sortable'),
                searchable: (bool) data_get($column, 'searchable'),
                hidden: (bool) data_get($column, 'hidden'),
            );
        }

        return $schema;
    }

    /**
     * @return list<FilterSchema>
     */
    private function filtersSchema(): array
    {
        $schema = [];

        foreach ($this->context->declaredFilters() as $filter) {
            $field = $this->asString(data_get($filter, 'field') ?: data_get($filter, 'column'));

            if ($field === '') {
                continue;
            }

            $schema[] = new FilterSchema(
                key: $this->asString(data_get($filter, 'key')),
                field: $field,
                column: $this->asString(data_get($filter, 'column')),
                title: $this->asString(data_get($filter, 'title')),
            );
        }

        return $schema;
    }
}
