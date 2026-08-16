<?php

namespace PowerComponents\Turbine\DataSource\Processors;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use PowerComponents\Turbine\Contracts\{Context, DataSourceProcessor};

abstract class DataSourceBase implements DataSourceProcessor
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

    public function resolveTable(mixed $datasource): ?string
    {
        if ($datasource instanceof QueryBuilder) {
            $from = $datasource->from;

            if (is_string($from)) {
                return $from;
            }

            if (is_object($from) && method_exists($from, 'getValue')) {
                try {
                    return (string) $from->getValue($datasource->getGrammar());
                } catch (\Throwable) {
                }
            }

            return ($from instanceof \Stringable || (is_object($from) && method_exists($from, '__toString')))
                ? (string) $from
                : null;
        }

        if ($datasource instanceof Relation || (is_object($datasource) && method_exists($datasource, 'getModel'))) {
            try {
                /** @var mixed $model */
                $model = $datasource->getModel();
                if (is_object($model) && method_exists($model, 'getTable')) {
                    /** @var string $table */
                    $table = $model->getTable();

                    return $table;
                }
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }

    protected function setCurrentTable(mixed $datasource): void
    {
        if ($table = $this->resolveTable($datasource)) {
            $this->component->setCurrentTable($table);
        }
    }
}
