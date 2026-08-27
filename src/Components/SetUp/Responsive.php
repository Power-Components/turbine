<?php

namespace PowerComponents\Turbine\Components\SetUp;

use PowerComponents\Turbine\Contracts\Definition;

/** @codeCoverageIgnore */
final class Responsive implements Definition
{
    public string $name = 'responsive';

    public const ACTIONS_COLUMN_NAME = 'actions';

    public const CHECKBOX_COLUMN_NAME = 'checkbox';

    /** @var list<string> */
    public array $fixedColumns = ['id', self::CHECKBOX_COLUMN_NAME, self::ACTIONS_COLUMN_NAME];

    /** @var array<string, int> */
    public array $sortOrder = [];

    public function fixedColumns(string ...$columnNames): static
    {
        $this->fixedColumns = array_values($columnNames);

        return $this;
    }

    /** @param  string|list<string>  ...$columnNames */
    public function sortOrder(string|array ...$columnNames): static
    {
        if (is_array(data_get($columnNames, '0'))) {
            $columnNames = $columnNames[0];
        }

        foreach ((array) $columnNames as $key => $column) {
            if (is_int($key)) {
                /** @var string $column */
                $this->sortOrder[$column] = $key + 1;

                continue;
            }

            $this->sortOrder[$key] = intval($column);
        }

        return $this;
    }

    /**
     * True when the column should stay visible. Matches `field`, `dataField`,
     * the actions sentinel, and `fixedOnResponsive()`.
     *
     * @param  array<int, string>  $fixedColumns
     */
    public static function isColumnFixed(mixed $column, array $fixedColumns): bool
    {
        $field = data_get($column, 'field');
        $dataField = data_get($column, 'dataField', $field);

        if (is_string($field) && $field !== '' && in_array($field, $fixedColumns, true)) {
            return true;
        }

        if (is_string($dataField) && $dataField !== '' && in_array($dataField, $fixedColumns, true)) {
            return true;
        }

        if ((bool) data_get($column, 'isAction') && in_array(self::ACTIONS_COLUMN_NAME, $fixedColumns, true)) {
            return true;
        }

        return (bool) data_get($column, 'fixedOnResponsive');
    }

    /**
     * @param  array<string, int>  $sortOrder
     */
    public static function columnSortOrder(mixed $column, array $sortOrder): ?int
    {
        $field = data_get($column, 'field');
        $dataField = data_get($column, 'dataField', $field);

        foreach ([$field, $dataField] as $key) {
            if (is_string($key) && $key !== '' && array_key_exists($key, $sortOrder)) {
                return (int) $sortOrder[$key];
            }
        }

        return null;
    }
}
