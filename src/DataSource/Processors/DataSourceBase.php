<?php

namespace PowerComponents\Turbine\DataSource\Processors;

use Illuminate\Database\Query\Builder as QueryBuilder;
use PowerComponents\Turbine\Contracts\Context;

abstract class DataSourceBase
{
    public function __construct(
        public Context $component,
        public bool $isExport = false
    ) {}

    abstract public static function match(mixed $datasource): bool;

    /**
     * @param  array<string, mixed>  $properties
     * @return array{results: mixed, actionsByRow?: array<int|string, list<array<string, mixed>>>}
     */
    abstract public function process(array $properties = [], mixed $datasource = null): array;

    protected function setCurrentTable(mixed $datasource): void
    {
        if ($datasource instanceof QueryBuilder) {
            /** @var string $from */
            $from = $datasource->from;
            $this->component->setCurrentTable($from);

            return;
        }

        /** @phpstan-ignore-next-line */
        $this->component->setCurrentTable($datasource->getModel()->getTable());
    }
}
