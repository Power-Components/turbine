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
     * @param  array<string, mixed>  $filters
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
            $fieldStr = is_string($field) || is_numeric($field) ? (string) $field : '';
            $dataFieldStr = is_string($dataField) || is_numeric($dataField) ? (string) $dataField : '';
            $key = filled($fieldStr) ? $fieldStr : $dataFieldStr;

            return [$key => $column];
        });

        foreach ($declaredFilters as $filter) {
            if (blank($filter->defaultValue) || blank($filter->field)) {
                continue;
            }

            $field = (string) $filter->field;
            $columnData = $columnsByField->get($filter->column);
            $labelRaw = data_get($columnData, 'title', $field);
            $label = is_string($labelRaw) ? $labelRaw : $field;
            $key = data_get($filter, 'key');
            $defaultValue = $filter->defaultValue;

            switch ($key) {
                case 'select':
                    $filters['select'] = (array) ($filters['select'] ?? []);
                    $filters['select'][$field] = $defaultValue;
                    $this->addEnabledFilter($field, $label, $enabledFilters);
                    $applied = true;
                    break;

                case 'multi_select':
                    $values = is_array($defaultValue) ? $defaultValue : [$defaultValue];
                    $filters['multi_select'] = (array) ($filters['multi_select'] ?? []);
                    $filters['multi_select'][$field] = $values;
                    $this->addEnabledFilter($field, $label, $enabledFilters);
                    $applied = true;
                    break;

                case 'boolean':
                    $filters['boolean'] = (array) ($filters['boolean'] ?? []);
                    $filters['boolean'][$field] = $defaultValue;
                    $this->addEnabledFilter($field, $label, $enabledFilters);
                    $applied = true;
                    break;

                case 'input_text':
                    $filters['input_text'] = (array) ($filters['input_text'] ?? []);
                    if (is_array($defaultValue)) {
                        $filters['input_text'][$field] = $defaultValue['value'] ?? '';
                        if (isset($defaultValue['operator'])) {
                            $filters['input_text_options'] = (array) ($filters['input_text_options'] ?? []);
                            $filters['input_text_options'][$field] = $defaultValue['operator'];
                        }
                    } else {
                        $filters['input_text'][$field] = $defaultValue;
                    }
                    $this->addEnabledFilter($field, $label, $enabledFilters);
                    $applied = true;
                    break;

                case 'number':
                    $filters['number'] = (array) ($filters['number'] ?? []);
                    /** @var array<string, mixed> $numberFieldFilter */
                    $numberFieldFilter = (array) ($filters['number'][$field] ?? []);
                    if (is_array($defaultValue)) {
                        if (isset($defaultValue['start'])) {
                            $numberFieldFilter['start'] = $defaultValue['start'];
                        }
                        if (isset($defaultValue['end'])) {
                            $numberFieldFilter['end'] = $defaultValue['end'];
                        }
                    } else {
                        $numberFieldFilter['start'] = $defaultValue;
                    }
                    $filters['number'][$field] = $numberFieldFilter;
                    $this->addEnabledFilter($field, $label, $enabledFilters);
                    $applied = true;
                    break;

                case 'date':
                case 'datetime':
                case 'datepicker':
                case 'datetimepicker':
                    $filterKey = in_array($key, ['date', 'datepicker'], true) ? 'date' : 'datetime';
                    $filters[$filterKey] = (array) ($filters[$filterKey] ?? []);
                    if (is_array($defaultValue)) {
                        $filters[$filterKey][$field] = [
                            'start' => $defaultValue['start'] ?? '',
                            'end' => $defaultValue['end'] ?? '',
                            'formatted' => $defaultValue['formatted'] ?? '',
                        ];
                    } else {
                        $filters[$filterKey][$field] = $defaultValue;
                    }
                    $this->addEnabledFilter($field, $label, $enabledFilters);
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
