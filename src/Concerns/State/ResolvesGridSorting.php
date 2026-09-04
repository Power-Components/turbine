<?php

namespace PowerComponents\Turbine\Concerns\State;

use PowerComponents\Turbine\Column;
use PowerComponents\Turbine\Contracts\Context;

/** @phpstan-require-implements Context */
trait ResolvesGridSorting
{
    public function resolveSortField(string $sortField): string
    {
        foreach ($this->declaredColumns() as $column) {
            if (data_get($column, 'field') === $sortField) {
                $dataField = data_get($column, 'dataField');

                if (is_string($dataField) && $dataField !== '' && $dataField !== $sortField) {
                    return $dataField;
                }

                break;
            }
        }

        if (str_contains($sortField, '.') || $this->state()->ignoreTablePrefix) {
            return $sortField;
        }

        return $this->getCurrentTable().'.'.$sortField;
    }

    public function isValidSortField(string $sortField): bool
    {
        if (! $this->hasResolvedColumns()) {
            if (array_key_exists($sortField, $this->fields()->fields)) {
                return true;
            }
        }

        // Accept either the friendly `field` (sent by the header) or the
        // `dataField` (backward compatible with direct sortField assignments).
        return collect($this->declaredColumns())
            ->flatMap(fn ($column) => [data_get($column, 'field'), data_get($column, 'dataField')])
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
            $matches = data_get($column, 'dataField') === $field
                || data_get($column, 'field') === $field;

            if ($matches && data_get($column, 'sortCallback') instanceof \Closure) {
                return $column instanceof Column ? $column->sortCallback : null;
            }
        }

        return null;
    }
}
