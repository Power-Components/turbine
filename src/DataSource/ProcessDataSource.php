<?php

namespace PowerComponents\Turbine\DataSource;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use PowerComponents\Turbine\Contracts\Context;
use PowerComponents\Turbine\DataSource\Processors\{CollectionProcessor, DataSourceBase, ModelProcessor, ScoutBuilderProcessor};
use Throwable;

class ProcessDataSource
{
    public mixed $datasource = null;

    /** @param  array<string, mixed>  $properties */
    public function __construct(
        public Context $component,
        public array $properties = [],
    ) {}

    /** @param  array<string, mixed>  $properties */
    public static function make(Context $component, array $properties = []): ProcessDataSource
    {
        return new self($component, $properties);
    }

    public function resolveDatasource(): mixed
    {
        if (is_null($this->datasource)) {
            $this->datasource = $this->component->datasource($this->properties);
        }

        $datasource = $this->datasource;

        if ($datasource instanceof QueryBuilder) {
            /** @var string $from */
            $from = $datasource->from;
            $this->component->setCurrentTable($from);
        } elseif ($datasource instanceof Relation || (is_object($datasource) && method_exists($datasource, 'getModel'))) {
            $this->component->setCurrentTable($datasource->getModel()->getTable());
        }

        return $datasource;
    }

    /**
     * @return array{results: mixed, actionsByRow?: array<int|string, list<array<string, mixed>>>}
     *
     * @throws Throwable
     */
    public function get(bool $isExport = false): array
    {
        if (is_null($this->datasource)) {
            $this->datasource = $this->component->datasource($this->properties);
        }

        $datasource = is_object($this->datasource) ? clone $this->datasource : $this->datasource;

        /** @var list<class-string<DataSourceBase>> $processors */
        $processors = [
            CollectionProcessor::class,
            ScoutBuilderProcessor::class,
        ];

        foreach ($processors as $processor) {
            if ($processor::match($datasource)) {
                $instance = new $processor($this->component, $isExport);

                return $instance->process($this->properties, $datasource);
            }
        }

        return (new ModelProcessor($this->component, $isExport))->process($this->properties, $datasource);
    }
}
