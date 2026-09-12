<?php

namespace PowerComponents\Turbine\Components\Filters;

use PowerComponents\Turbine\Support\FilterBag;

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
     * @param  array<string, mixed>  $filters
     */
    public function applyDefaults(
        array $declaredFilters,
        array &$filters
    ): bool {
        $applied = false;

        foreach ($declaredFilters as $filter) {
            if (blank($filter->defaultValue) || blank($filter->field)) {
                continue;
            }

            $field = (string) $filter->field;
            $bagKey = FilterBag::bagKey($filter->column, $field);
            $key = is_string(data_get($filter, 'key')) ? (string) data_get($filter, 'key') : '';
            $defaultValue = $filter->defaultValue;

            switch ($key) {
                case 'select':
                    $filters[$bagKey] = FilterBag::record('select', $defaultValue);
                    $applied = true;
                    break;

                case 'multi_select':
                    $values = is_array($defaultValue) ? $defaultValue : [$defaultValue];
                    $filters[$bagKey] = FilterBag::record('multi_select', $values);
                    $applied = true;
                    break;

                case 'boolean':
                    $filters[$bagKey] = FilterBag::record('boolean', $defaultValue);
                    $applied = true;
                    break;

                case 'input_text':
                    if (is_array($defaultValue)) {
                        $filters[$bagKey] = FilterBag::record(
                            'input_text',
                            $defaultValue['value'] ?? '',
                            $defaultValue['operator'] ?? null,
                        );
                    } else {
                        $filters[$bagKey] = FilterBag::record('input_text', $defaultValue);
                    }
                    $applied = true;
                    break;

                case 'number':
                    /** @var array<string, mixed> $range */
                    $range = is_array($filters[$bagKey]['value'] ?? null) ? $filters[$bagKey]['value'] : [];
                    if (is_array($defaultValue)) {
                        if (isset($defaultValue['start'])) {
                            $range['start'] = $defaultValue['start'];
                        }
                        if (isset($defaultValue['end'])) {
                            $range['end'] = $defaultValue['end'];
                        }
                    } else {
                        $range['start'] = $defaultValue;
                    }
                    $filters[$bagKey] = FilterBag::record('number', $range);
                    $applied = true;
                    break;

                case 'date':
                case 'datetime':
                case 'datepicker':
                case 'datetimepicker':
                    $type = FilterBag::normalizeType($key);
                    if (is_array($defaultValue)) {
                        $filters[$bagKey] = FilterBag::record($type, [
                            'start' => $defaultValue['start'] ?? '',
                            'end' => $defaultValue['end'] ?? '',
                            'formatted' => $defaultValue['formatted'] ?? '',
                        ]);
                    } else {
                        $filters[$bagKey] = FilterBag::record($type, $defaultValue);
                    }
                    $applied = true;
                    break;
            }
        }

        return $applied;
    }
}
