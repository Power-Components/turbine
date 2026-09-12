<?php

namespace PowerComponents\Turbine\DataSource\Processors\Collection\Pipelines;

use Closure;
use Illuminate\Support\Collection;
use PowerComponents\Turbine\Contracts\Context;
use PowerComponents\Turbine\DataSource\Builders\{Boolean, DatePicker, DateTimePicker, InputText, MultiSelect, Number, Select};
use PowerComponents\Turbine\DataSource\Support\InputOperators;
use PowerComponents\Turbine\Plugins\FilterBuilder\FilterBuilderHandler;
use PowerComponents\Turbine\Support\{FilterBag, FilterValue};

final class Filters
{
    use InputOperators;

    public function __construct(protected Context $component) {}

    /**
     * @param  Collection<int, mixed>  $collection
     * @return Collection<int, mixed>
     */
    public function handle(Collection $collection, Closure $next): Collection
    {
        $filterBuilder = new FilterBuilderHandler($this->component);
        $filters = $this->component->state()->filters;

        if (blank($filters)) {
            return $next(
                $filterBuilder->isActive() ? $filterBuilder->applyCollection($collection) : $collection
            );
        }

        $definitions = collect($this->component->declaredFilters());
        $results = $collection;

        foreach ($filters as $bagKey => $record) {
            if (! FilterBag::isRecord($record) || ! FilterBag::isActive($record)) {
                continue;
            }

            $bagKey = (string) $bagKey;
            $definition = $definitions->first(
                fn ($filter) => FilterBag::matchesDefinition($filter, $bagKey)
            );

            if (! $definition) {
                continue;
            }

            $sqlField = FilterBag::sqlField($definition, $bagKey);
            $filterType = $record['type'];
            $value = $record['value'] ?? null;

            $results = match ($filterType) {
                'datetime' => (new DateTimePicker($this->component, $definition))->collection($results, $sqlField, FilterValue::dateRange($value)),
                'date' => (new DatePicker($this->component, $definition))->collection($results, $sqlField, FilterValue::dateRange($value)),
                'multi_select' => (new MultiSelect($this->component, $definition))->collection($results, $sqlField, FilterValue::items($value)),
                'select' => (new Select($this->component, $definition))->collection($results, $sqlField, FilterValue::map($value)),
                'boolean' => (new Boolean($this->component, $definition))->collection($results, $sqlField, FilterValue::map($value)),
                'number' => (new Number($this->component, $definition))->collection($results, $sqlField, FilterValue::numberRange($value)),
                'input_text' => (new InputText($this->component, $definition))->collection($results, $sqlField, [
                    'selected' => $this->validateInputTextOptions($filters, $bagKey),
                    'value' => $value,
                ]),
                default => $results
            };
        }

        if ($filterBuilder->isActive()) {
            $results = $filterBuilder->applyCollection($results);
        }

        return $next($results);
    }
}
