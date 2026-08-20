<?php

namespace PowerComponents\Turbine\Contracts;

use PowerComponents\Turbine\Components\Filters\FilterBase;
use PowerComponents\Turbine\Fields;

interface GridSchema
{
    public function datasource(): mixed;

    public function fields(): Fields;

    /** @return array<int, mixed> */
    public function columns(): array;

    /** @return array<int, FilterBase> */
    public function filters(): array;

    /** @return array<int, mixed> */
    public function actions(object $row): array;

    /** @return array<int, mixed> */
    public function actionRules(object $row): array;

    /** @return array<string, list<string>|string> */
    public function relationSearch(): array;

    /** @return array<string, string> */
    public function searchMorphs(): array;

    public function transformQuery(mixed $query): mixed;

    /** @return array<int, object> */
    public function setUp(): array;
}
