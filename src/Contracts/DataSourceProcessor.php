<?php

namespace PowerComponents\Turbine\Contracts;

interface DataSourceProcessor
{
    public static function match(mixed $datasource): bool;

    /**
     * @param  array<string, mixed>  $properties
     * @return array{results: mixed, actionsByRow?: array<int|string, list<array<string, mixed>>>}
     */
    public function process(array $properties = [], mixed $datasource = null): array;

    public function resolveTable(mixed $datasource): ?string;
}
