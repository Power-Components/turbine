<?php

namespace PowerComponents\Turbine;

use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Pagination\AbstractPaginator;
use PowerComponents\Turbine\Components\Filters\FilterBase;
use PowerComponents\Turbine\Contracts\{Context, GridSchema};

abstract class GridDefinition implements GridSchema
{
    public string $tableName = '';

    public string $primaryKey = 'id';

    public int $perPage = 15;

    public string $pageName = 'page';

    abstract public function datasource(): mixed;

    public function fields(): Fields
    {
        return new Fields();
    }

    /** @return array<int, mixed> */
    public function columns(): array
    {
        return [];
    }

    /** @return array<int, FilterBase> */
    public function filters(): array
    {
        return [];
    }

    /** @return array<int, mixed> */
    public function actions(object $row): array
    {
        return [];
    }

    /** @return array<int, mixed> */
    public function actionRules(object $row): array
    {
        return [];
    }

    /** @return array<string, list<string>|string> */
    public function relationSearch(): array
    {
        return [];
    }

    /** @return array<string, string> */
    public function searchMorphs(): array
    {
        return [];
    }

    public function transformQuery(mixed $query): mixed
    {
        return $query;
    }

    /** @return array<int, object> */
    public function setUp(): array
    {
        return [];
    }

    public function toTurbine(): Turbine
    {
        return Turbine::make()
            ->datasource(fn () => $this->datasource())
            ->fields($this->fields())
            ->columns(fn () => $this->columns())
            ->filters($this->filters())
            ->actions(fn (object $row) => $this->actions($row))
            ->actionRules(fn (object $row) => $this->actionRules($row))
            ->relationSearch($this->relationSearch())
            ->searchMorphs($this->searchMorphs())
            ->transformQuery(fn (mixed $query) => $this->transformQuery($query))
            ->setUp($this->setUp())
            ->tableName($this->tableName)
            ->primaryKey($this->primaryKey)
            ->perPage($this->perPage)
            ->pageName($this->pageName);
    }

    /** @return array{data: list<array<string, mixed>>, meta: array<string, mixed>, columns: list<array<string, mixed>>, filters?: list<array<string, mixed>>, actions?: array<string, list<array<string, mixed>>>} */
    public function toArray(Request $request): array
    {
        return $this->toTurbine()->fromRequest($request)->toArray();
    }

    public function toResponse(Request $request): JsonResponse
    {
        return $this->toTurbine()->fromRequest($request)->toResponse();
    }

    /**
     * @return AbstractPaginator<int|string, array<string, mixed>>
     */
    public function toPaginator(Request $request): AbstractPaginator
    {
        return $this->toTurbine()->fromRequest($request)->toPaginator();
    }

    public function context(Request $request): Context
    {
        return $this->toTurbine()->fromRequest($request)->context();
    }
}
