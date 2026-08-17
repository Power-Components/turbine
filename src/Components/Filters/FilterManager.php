<?php

namespace PowerComponents\Turbine\Components\Filters;

class FilterManager
{
    public function multiSelect(string $column, ?string $field = null): FilterMultiSelect
    {
        return new FilterMultiSelect($column, $field);
    }

    public function multiSelectAsync(string $column, ?string $field = null): FilterMultiSelectAsync
    {
        return new FilterMultiSelectAsync($column, $field);
    }

    public function inputText(string $column, ?string $field = null): FilterInputText
    {
        return new FilterInputText($column, $field);
    }

    public function select(string $column, ?string $field = null): FilterSelect
    {
        return new FilterSelect($column, $field);
    }

    public function enumSelect(string $column, ?string $field = null): FilterEnumSelect
    {
        return new FilterEnumSelect($column, $field);
    }

    public function number(string $column, ?string $field = null): FilterNumber
    {
        return new FilterNumber($column, $field);
    }

    public function dynamic(string $column, ?string $field = null): FilterDynamic
    {
        return new FilterDynamic($column, $field);
    }

    public function datepicker(string $column, ?string $field = null): FilterDatePicker
    {
        return new FilterDatePicker($column, $field);
    }

    public function datetimepicker(string $column, ?string $field = null): FilterDateTimePicker
    {
        return new FilterDateTimePicker($column, $field);
    }

    public function boolean(string $column, ?string $field = null): FilterBoolean
    {
        return new FilterBoolean($column, $field);
    }

    /**
     * @param  list<FilterBase>  $declaredFilters
     * @param  array<int, mixed>  $columns
     * @param  array<string, array<string, mixed>>  $filters
     * @param  list<array<string, mixed>>  $enabledFilters
     */
    public function applyDefaults(
        array $declaredFilters,
        array $columns,
        array &$filters,
        array &$enabledFilters
    ): bool {
        $applied = false;
        $columnsByField = collect($columns)->mapWithKeys(function ($column) {
            $field = data_get($column, 'field');
            $dataField = data_get($column, 'dataField');
            $key = filled($field) ? strval($field) : strval($dataField);

            return [$key => $column];
        });

        foreach ($declaredFilters as $filter) {
            if (blank($filter->defaultValue)) {
                continue;
            }

            $field = $filter->field;
            $columnData = $columnsByField->get($filter->column);
            $label = data_get($columnData, 'title', $field);
            $key = data_get($filter, 'key');
            $defaultValue = $filter->defaultValue;

            switch ($key) {
                case 'select':
                    data_set($filters, "select.{$field}", $defaultValue);
                    $this->addEnabledFilter($field, is_string($label) ? $label : $field, $enabledFilters);
                    $applied = true;
                    break;

                case 'multi_select':
                    $values = is_array($defaultValue) ? $defaultValue : [$defaultValue];
                    data_set($filters, "multi_select.{$field}", $values);
                    $this->addEnabledFilter($field, is_string($label) ? $label : $field, $enabledFilters);
                    $applied = true;
                    break;

                case 'boolean':
                    data_set($filters, "boolean.{$field}", $defaultValue);
                    $this->addEnabledFilter($field, is_string($label) ? $label : $field, $enabledFilters);
                    $applied = true;
                    break;

                case 'input_text':
                    if (is_array($defaultValue)) {
                        data_set($filters, "input_text.{$field}", $defaultValue['value'] ?? '');
                        if (isset($defaultValue['operator'])) {
                            data_set($filters, "input_text_options.{$field}", $defaultValue['operator']);
                        }
                    } else {
                        data_set($filters, "input_text.{$field}", $defaultValue);
                    }
                    $this->addEnabledFilter($field, is_string($label) ? $label : $field, $enabledFilters);
                    $applied = true;
                    break;

                case 'number':
                    if (is_array($defaultValue)) {
                        if (isset($defaultValue['start'])) {
                            data_set($filters, "number.{$field}.start", $defaultValue['start']);
                        }
                        if (isset($defaultValue['end'])) {
                            data_set($filters, "number.{$field}.end", $defaultValue['end']);
                        }
                    } else {
                        data_set($filters, "number.{$field}.start", $defaultValue);
                    }
                    $this->addEnabledFilter($field, is_string($label) ? $label : $field, $enabledFilters);
                    $applied = true;
                    break;

                case 'date':
                case 'datetime':
                case 'datepicker':
                case 'datetimepicker':
                    $filterKey = in_array($key, ['date', 'datepicker'], true) ? 'date' : 'datetime';
                    if (is_array($defaultValue)) {
                        data_set($filters, "{$filterKey}.{$field}", [
                            'start' => $defaultValue['start'] ?? '',
                            'end' => $defaultValue['end'] ?? '',
                            'formatted' => $defaultValue['formatted'] ?? '',
                        ]);
                    } else {
                        data_set($filters, "{$filterKey}.{$field}", $defaultValue);
                    }
                    $this->addEnabledFilter($field, is_string($label) ? $label : $field, $enabledFilters);
                    $applied = true;
                    break;
            }
        }

        return $applied;
    }

    /**
     * @param  list<array<string, mixed>>  $enabledFilters
     */
    public function addEnabledFilter(string $field, string $label, array &$enabledFilters): void
    {
        $exists = collect($enabledFilters)->contains(fn ($item) => data_get($item, 'field') === $field);

        if (! $exists) {
            $enabledFilters[] = [
                'field' => $field,
                'label' => $label,
            ];
        }
    }

    /**
     * @param  list<array<string, mixed>>  $enabledFilters
     */
    public function removeEnabledFilter(string $field, array &$enabledFilters): void
    {
        $enabledFilters = array_values(array_filter(
            $enabledFilters,
            fn ($item) => data_get($item, 'field') !== $field
        ));
    }
}
