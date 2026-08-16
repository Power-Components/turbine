<?php

namespace PowerComponents\Turbine\DataSource;

use PowerComponents\Turbine\Contracts\Context;
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

        /** @var DataSourceManager $manager */
        $manager = app(DataSourceManager::class);

        $table = $manager->resolveTable($datasource, $this->component);
        if ($table !== null) {
            $this->component->setCurrentTable($table);
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

        /** @var DataSourceManager $manager */
        $manager = app(DataSourceManager::class);

        $processor = $manager->resolveProcessor($datasource, $this->component, $isExport);

        return $processor->process($this->properties, $datasource);
    }
}
