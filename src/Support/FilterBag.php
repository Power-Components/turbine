<?php

namespace PowerComponents\Turbine\Support;

/**
 * The filter bag: `[$bagKey => ['type' => string, 'value' => mixed, 'op'? => mixed, 'label'? => string]]`.
 *
 * The shape is an invariant, not a guess: hosts canonicalize the bag at their own
 * boundary (hydration, persisted state, user input) and everything downstream may
 * read it directly.
 */
final class FilterBag
{
    /** @var list<string> */
    public const array VALUELESS_OPERATORS = [
        'is_empty',
        'is_not_empty',
        'is_null',
        'is_not_null',
        'is_blank',
        'is_not_blank',
    ];

    /**
     * @return array{type: string, value: mixed, op?: mixed}
     */
    public static function record(string $type, mixed $value, mixed $op = null): array
    {
        $record = [
            'type' => self::normalizeType($type),
            'value' => $value,
        ];

        if ($op !== null && $op !== '') {
            $record['op'] = $op;
        }

        return $record;
    }

    /** @phpstan-assert-if-true array{type: string, value?: mixed, op?: mixed} $value */
    public static function isRecord(mixed $value): bool
    {
        return is_array($value)
            && isset($value['type'])
            && is_string($value['type'])
            && $value['type'] !== '';
    }

    /**
     * @param  array<string, mixed>  $record
     */
    public static function isActive(array $record): bool
    {
        if (! self::isRecord($record)) {
            return false;
        }

        $type = $record['type'];
        $value = $record['value'] ?? null;
        $op = $record['op'] ?? null;

        if (is_array($op)) {
            $op = collect($op)->values()->first();
        }

        if (self::isValuelessOperator($op)) {
            return true;
        }

        if ($type === 'boolean') {
            return $value !== null && $value !== '' && $value !== 'all';
        }

        if ($type === 'number' && is_array($value)) {
            return filled($value['start'] ?? null) || filled($value['end'] ?? null);
        }

        if (in_array($type, ['date', 'datetime'], true) && is_array($value)) {
            return filled($value['formatted'] ?? null)
                || filled($value['start'] ?? null)
                || filled($value['end'] ?? null);
        }

        if ($type === 'multi_select' && is_array($value)) {
            return filled(array_filter($value, fn ($item) => filled($item)));
        }

        return filled($value);
    }

    public static function isValuelessOperator(mixed $operator): bool
    {
        return in_array($operator, self::VALUELESS_OPERATORS, true);
    }

    public static function normalizeType(string $type): string
    {
        return match ($type) {
            'datepicker' => 'date',
            'datetimepicker' => 'datetime',
            default => $type,
        };
    }

    public static function bagKey(string $column, ?string $field = null): string
    {
        $field ??= $column;

        if ($field !== '' && ! str_contains($field, '.')) {
            return $field;
        }

        return $column !== '' ? $column : $field;
    }

    public static function matchesDefinition(mixed $definition, string $bagKey): bool
    {
        return data_get($definition, 'column') === $bagKey
            || data_get($definition, 'field') === $bagKey;
    }

    public static function sqlField(mixed $definition, string $bagKey): string
    {
        $field = data_get($definition, 'field');

        return is_string($field) && $field !== '' ? $field : $bagKey;
    }
}
