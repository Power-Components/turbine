<?php

namespace PowerComponents\Turbine\Concerns\State;

use PowerComponents\Turbine\Column;
use PowerComponents\Turbine\Contracts\Context;

/** @phpstan-require-implements Context */
trait ResolvesGridSorting
{
    public function resolveSortField(string $sortField): string
    {
        if (str_contains($sortField, '.') || $this->state()->ignoreTablePrefix) {
            return $sortField;
        }

        return $this->getCurrentTable().'.'.$sortField;
    }

    public function isValidSortField(string $sortField): bool
    {
        if (! $this->hasResolvedColumns()) {
            $fieldKey = str_contains($sortField, '.') ? explode('.', $sortField)[1] : $sortField;

            if ($this->fields !== null && array_key_exists($fieldKey, $this->fields->fields)) {
                return true;
            }
        }

        return collect($this->declaredColumns())
            ->map(fn ($column) => data_get($column, 'dataField') ?: data_get($column, 'field'))
            ->filter()
            ->contains($sortField);
    }

    public function getSortCallback(string $field): ?\Closure
    {
        if (! $this->hasResolvedColumns()) {
            return null;
        }

        $columns = $this->declaredColumns();

        foreach ($columns as $column) {
            $columnDataField = data_get($column, 'dataField');

            if ($columnDataField === $field && data_get($column, 'sortCallback') instanceof \Closure) {
                return $column instanceof Column ? $column->sortCallback : null;
            }
        }

        return null;
    }
}
