<?php

namespace PowerComponents\Turbine\DataSource\Support;

use PowerComponents\Turbine\Components\Filters\FilterInputText;

trait InputOperators
{
    /**
     * @param  array<string, mixed>  $filter
     * @param  array<int, string>|null  $configured
     */
    public function validateInputTextOptions(array $filter, string $field, ?array $configured = null): string
    {
        /** @var array<int, string>|string $selected */
        $selected = data_get($filter, "input_text_options.$field");

        if (is_array($selected)) {
            $selected = collect($selected)->values()[0];
        }

        $selected = strval($selected);

        $allowed = FilterInputText::getInputTextOperators();

        if ($configured !== null) {
            $allowed = array_values(array_intersect($allowed, $configured));
        }

        return in_array($selected, $allowed, true) ? $selected : 'contains';
    }

    /**
     * Developer-configured operator subset for a filter definition, intersected
     * later with the global operator list. Null means "use the global list".
     *
     * @return list<string>|null
     */
    public function resolveConfiguredOperators(mixed $definition): ?array
    {
        $configured = data_get($definition, 'operators');

        if (! is_array($configured)) {
            return null;
        }

        $values = [];

        foreach ($configured as $value) {
            if (is_string($value) && $value !== '') {
                $values[] = $value;
            }
        }

        return $values === [] ? null : $values;
    }
}
