<?php

namespace PowerComponents\Turbine\DataSource;

use PowerComponents\Turbine\Contracts\{Context, DataSourceProcessor};
use PowerComponents\Turbine\DataSource\Processors\{CollectionProcessor, ModelProcessor, ScoutBuilderProcessor};
use PowerComponents\Turbine\Fields;
use PowerComponents\Turbine\Support\State\{ArrayGridContext, State};
use Throwable;

class DataSourceManager
{
    /** @var list<class-string<DataSourceProcessor>> */
    protected array $processors = [];

    /** @var array<string, class-string<DataSourceProcessor>> */
    protected array $matchCache = [];

    protected static ?Context $nullContext = null;

    public function __construct()
    {
        $this->registerDefaultProcessors();
    }

    public function registerDefaultProcessors(): void
    {
        /** @var list<class-string<DataSourceProcessor>> $configured */
        $configured = config('turbine.datasources', [
            CollectionProcessor::class,
            ScoutBuilderProcessor::class,
            ModelProcessor::class,
        ]);

        foreach ($configured as $processor) {
            $this->register($processor);
        }
    }

    /** @param class-string<DataSourceProcessor> $processor */
    public function register(string $processor, bool $prepend = false): self
    {
        if (! in_array($processor, $this->processors, true)) {
            if ($prepend) {
                array_unshift($this->processors, $processor);
            } else {
                $this->processors[] = $processor;
            }
            $this->matchCache = [];
        }

        return $this;
    }

    public function flush(): self
    {
        $this->processors = [];
        $this->matchCache = [];

        return $this;
    }

    public function reset(): self
    {
        $this->flush();
        $this->registerDefaultProcessors();

        return $this;
    }

    /**
     * @return list<class-string<DataSourceProcessor>>
     */
    public function getProcessors(): array
    {
        return $this->processors;
    }

    /**
     * @return class-string<DataSourceProcessor>
     */
    public function findProcessorClass(mixed $datasource): string
    {
        $cacheKey = is_object($datasource) ? $datasource::class : gettype($datasource);

        if (isset($this->matchCache[$cacheKey])) {
            return $this->matchCache[$cacheKey];
        }

        foreach ($this->processors as $processor) {
            if ($processor::match($datasource)) {
                return $this->matchCache[$cacheKey] = $processor;
            }
        }

        return $this->matchCache[$cacheKey] = ModelProcessor::class;
    }

    public function resolveProcessor(mixed $datasource, Context $component, bool $isExport = false): DataSourceProcessor
    {
        $processorClass = $this->findProcessorClass($datasource);

        return new $processorClass($component, $isExport);
    }

    public function resolveTable(mixed $datasource, ?Context $component = null): ?string
    {
        $processorClass = $this->findProcessorClass($datasource);

        try {
            $context = $component ?? $this->getNullContext();
            $instance = new $processorClass($context);

            return $instance->resolveTable($datasource);
        } catch (Throwable) {
            return null;
        }
    }

    protected function getNullContext(): Context
    {
        if ($this->isBoundContextAvailable()) {
            return app(Context::class);
        }

        if (self::$nullContext === null) {
            self::$nullContext = new ArrayGridContext(
                state: State::fromArray([]),
                datasourceResolver: static fn () => null,
                fields: new Fields(),
                columns: [],
                filters: []
            );
        }

        return self::$nullContext;
    }

    private function isBoundContextAvailable(): bool
    {
        try {
            return app()->bound(Context::class);
        } catch (Throwable) {
            return false;
        }
    }
}
