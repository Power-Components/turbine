<?php

namespace PowerComponents\Turbine\DataSource\Processors\Database\Handlers;

use Illuminate\Database\Eloquent\{Builder as EloquentBuilder, Model};
use Illuminate\Database\Query\Builder as QueryBuilder;
use PowerComponents\Turbine\Contracts\Context;
use PowerComponents\Turbine\DataSource\Builders\{Boolean, DatePicker, DateTimePicker, InputText, MultiSelect, Number, Select};
use PowerComponents\Turbine\DataSource\Support\InputOperators;
use PowerComponents\Turbine\Support\{FilterBag, FilterValue};

class FilterHandler
{
    use InputOperators;

    public function __construct(
        private readonly Context $component
    ) {}

    /** @param  EloquentBuilder<Model>|QueryBuilder  $query
     * @return EloquentBuilder<Model>|QueryBuilder */
    public function apply(EloquentBuilder|QueryBuilder $query): EloquentBuilder|QueryBuilder
    {
        $filterDefinitions = collect($this->component->declaredFilters());
        $filters = $this->component->state()->filters;

        if ($filterDefinitions->isEmpty() || empty($filters)) {
            return $query;
        }

        foreach ($filters as $bagKey => $record) {
            if (! FilterBag::isRecord($record) || ! FilterBag::isActive($record)) {
                continue;
            }

            $bagKey = (string) $bagKey;

            $filter = $filterDefinitions->first(
                fn ($definition) => FilterBag::matchesDefinition($definition, $bagKey)
            );

            if ($filter === null) {
                continue;
            }

            $sqlField = FilterBag::sqlField($filter, $bagKey);
            $filterType = $record['type'];
            $value = $record['value'] ?? null;

            $query->where(function ($query) use ($filterType, $sqlField, $value, $filter, $filters, $bagKey) {
                match ($filterType) {
                    'datetime' => (new DateTimePicker($this->component, $filter))->builder($query, $sqlField, FilterValue::dateRange($value)),
                    'date' => (new DatePicker($this->component, $filter))->builder($query, $sqlField, FilterValue::dateRange($value)),
                    'multi_select' => (new MultiSelect($this->component, $filter))->builder($query, $sqlField, FilterValue::items($value)),
                    'select' => (new Select($this->component, $filter))->builder($query, $sqlField, FilterValue::map($value)),
                    'boolean' => (new Boolean($this->component, $filter))->builder($query, $sqlField, FilterValue::map($value)),
                    'number' => (new Number($this->component, $filter))->builder($query, $sqlField, FilterValue::numberRange($value)),
                    'input_text' => (new InputText($this->component, $filter))->builder($query, $sqlField, [
                        'selected' => $this->validateInputTextOptions($filters, $bagKey, $this->resolveConfiguredOperators($filter)),
                        'value' => $value,
                        'searchMorphs' => $this->component->searchMorphs(),
                    ]),
                    default => null
                };
            });
        }

        return $query;
    }
}
