<?php

namespace PowerComponents\Turbine;

use Closure;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Pagination\AbstractPaginator;
use PowerComponents\Turbine\Components\Filters\FilterBase;
use PowerComponents\Turbine\Components\SetUp\{Cache, Detail, Exportable, FilterBuilder, Footer, Header, Responsive};
use PowerComponents\Turbine\Contracts\DataSourceProcessor;
use PowerComponents\Turbine\DataSource\DataSourceManager;
use PowerComponents\Turbine\Support\State\{ArrayGridContext, State};

/**
 * Fluent entry point for the Turbine engine.
 *
 * You describe the grid once in PHP — datasource, fields, columns, filters and
 * (optionally) row actions — feed it the request state, and get back the JSON
 * envelope. Search, filtering, sorting and pagination run in the core engine.
 *
 * ```php
 * return Turbine::make()
 *     ->datasource(fn () => User::query())
 *     ->fields((new Fields())->add('id')->add('name')->add('email'))
 *     ->columns([
 *         Column::make('Name', 'name')->searchable()->sortable(),
 *         Column::make('Email', 'email')->searchable(),
 *     ])
 *     ->filters([new FilterInputText('name')])
 *     ->tableName('users')
 *     ->perPage(15)
 *     ->fromRequest($request)
 *     ->toResponse();
 * ```
 */
final class Turbine
{
    private ?Closure $datasourceResolver = null;

    private ?Fields $fields = null;

    /** @var array<int, mixed>|Closure(): array<int, mixed> */
    private array|Closure $columns = [];

    /** @var array<int, FilterBase> */
    private array $filters = [];

    private ?Closure $actionsResolver = null;

    private ?Closure $actionRulesResolver = null;

    private ?Closure $transformQueryResolver = null;

    /** @var array<string, list<string>|string> */
    private array $relationSearch = [];

    /** @var array<string, string> */
    private array $searchMorphs = [];

    private string $primaryKey = 'id';

    private string $tableName = '';

    private int $perPage = 15;

    private string $pageName = 'page';

    /** @var array<string, mixed> */
    private array $requestState = [];

    /** @var array<int, object> */
    private array $setUp = [];

    public static function make(): self
    {
        return new self();
    }

    /** @param class-string<DataSourceProcessor> $processorClass */
    public static function registerDataSource(string $processorClass, bool $prepend = true): void
    {
        app(DataSourceManager::class)->register($processorClass, $prepend);
    }

    public static function header(): Header
    {
        return new Header();
    }

    public static function footer(): Footer
    {
        return new Footer();
    }

    public static function detail(): Detail
    {
        return new Detail();
    }

    public static function exportable(string $fileName = 'export'): Exportable
    {
        return new Exportable($fileName);
    }

    public static function cache(): Cache
    {
        return new Cache();
    }

    public static function filterBuilder(): FilterBuilder
    {
        return new FilterBuilder();
    }

    public static function responsive(): Responsive
    {
        return new Responsive();
    }

    public function datasource(Closure $resolver): self
    {
        $this->datasourceResolver = $resolver;

        return $this;
    }

    public function fields(Fields $fields): self
    {
        $this->fields = $fields;

        return $this;
    }

    /** @param  array<int, mixed>|Closure(): array<int, mixed>  $columns */
    public function columns(array|Closure $columns): self
    {
        $this->columns = $columns;

        return $this;
    }

    /** @param  array<int, FilterBase>  $filters */
    public function filters(array $filters): self
    {
        $this->filters = $filters;

        return $this;
    }

    /** @param  Closure(object): array<int, mixed>  $resolver */
    public function actions(Closure $resolver): self
    {
        $this->actionsResolver = $resolver;

        return $this;
    }

    /** @param  Closure(object): array<int, mixed>  $resolver */
    public function actionRules(Closure $resolver): self
    {
        $this->actionRulesResolver = $resolver;

        return $this;
    }

    /** @param  Closure(mixed): mixed  $resolver */
    public function transformQuery(Closure $resolver): self
    {
        $this->transformQueryResolver = $resolver;

        return $this;
    }

    /** @param  array<string, list<string>|string>  $relationSearch */
    public function relationSearch(array $relationSearch): self
    {
        $this->relationSearch = $relationSearch;

        return $this;
    }

    /** @param  array<string, string>  $searchMorphs */
    public function searchMorphs(array $searchMorphs): self
    {
        $this->searchMorphs = $searchMorphs;

        return $this;
    }

    public function primaryKey(string $primaryKey): self
    {
        $this->primaryKey = $primaryKey;

        return $this;
    }

    public function tableName(string $tableName): self
    {
        $this->tableName = $tableName;

        return $this;
    }

    public function perPage(int $perPage): self
    {
        $this->perPage = $perPage;

        return $this;
    }

    public function pageName(string $pageName): self
    {
        $this->pageName = $pageName;

        return $this;
    }

    /** @param  array<int, object>  $setUp */
    public function setUp(array $setUp): self
    {
        $this->setUp = $setUp;

        return $this;
    }

    public function fromRequest(Request $request, string $key = 'turbine'): self
    {
        $flatKeys = ['search', 'sortField', 'sortDirection', 'filters', 'sortArray', 'softDeletes', 'filterBuilder'];
        $flat = [];
        foreach ($flatKeys as $flatKey) {
            if ($request->has($flatKey)) {
                $flat[$flatKey] = $request->input($flatKey);
            }
        }

        /** @var array<string, mixed> $nested */
        $nested = (array) ($request->input($key) ?? []);

        $this->requestState = array_merge($flat, $nested);

        return $this;
    }

    /**
     * @param  array<string, mixed>  $state
     */
    public function state(array $state): self
    {
        $this->requestState = $state;

        return $this;
    }

    public function context(): ArrayGridContext
    {
        if ($this->datasourceResolver === null) {
            throw new \LogicException('Turbine requires a datasource(). Call ->datasource(fn () => Model::query()).');
        }

        return new ArrayGridContext(
            state: $this->buildState(),
            datasourceResolver: $this->datasourceResolver,
            fields: $this->fields ?? new Fields(),
            columns: $this->columns,
            filters: $this->filters,
            transformQueryResolver: $this->transformQueryResolver,
            relationSearch: $this->relationSearch,
            searchMorphs: $this->searchMorphs,
            actionsResolver: $this->actionsResolver,
            actionRulesResolver: $this->actionRulesResolver,
        );
    }

    /** @return array{data: list<array<string, mixed>>, meta: array<string, mixed>, columns: list<array<string, mixed>>, filters?: list<array<string, mixed>>, actions?: array<string, list<array<string, mixed>>>} */
    public function toArray(): array
    {
        return Response::make($this->context())->toArray();
    }

    public function toResponse(): JsonResponse
    {
        return Response::make($this->context())->toResponse();
    }

    /**
     * @return AbstractPaginator<int|string, array<string, mixed>>
     */
    public function toPaginator(): AbstractPaginator
    {
        return Response::make($this->context())->toPaginator();
    }

    private function buildState(): State
    {
        return State::fromArray(array_merge($this->requestState, [
           'primaryKey' => $this->primaryKey,
           'tableName' => $this->tableName,
           'setUp' => $this->buildSetUp(),
        ]));
    }

    /** @return array<string, mixed> */
    private function buildSetUp(): array
    {
        $setUp = [];

        foreach ($this->setUp as $component) {
            $name = data_get($component, 'name');

            if (is_string($name)) {
                $setUp[$name] = $component;
            }
        }

        $setUp['footer'] = array_merge(
            ['perPage' => $this->perPage, 'pageName' => $this->pageName],
            (array) ($setUp['footer'] ?? [])
        );

        return $setUp;
    }
}
