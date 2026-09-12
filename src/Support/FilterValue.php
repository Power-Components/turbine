<?php

namespace PowerComponents\Turbine\Support;

/**
 * Narrows a filter record's `value` — which arrives from the client and is
 * therefore mixed — to the shape the builder for that filter type accepts.
 * Anything that does not fit is dropped rather than coerced into a match.
 */
final class FilterValue
{
    /** @return array{start?: string, end?: string}|int|string|null */
    public static function dateRange(mixed $value): array|int|string|null
    {
        if (! is_array($value)) {
            return self::scalar($value);
        }

        $range = [];

        foreach (['start', 'end'] as $bound) {
            $date = $value[$bound] ?? null;

            if (is_scalar($date) && (string) $date !== '') {
                $range[$bound] = (string) $date;
            }
        }

        return $range;
    }

    /** @return array{start?: float|int|string, end?: float|int|string}|int|string|null */
    public static function numberRange(mixed $value): array|int|string|null
    {
        if (! is_array($value)) {
            return self::scalar($value);
        }

        $range = [];

        foreach (['start', 'end'] as $bound) {
            $bound_ = $value[$bound] ?? null;

            if (is_float($bound_) || is_int($bound_) || is_string($bound_)) {
                $range[$bound] = $bound_;
            }
        }

        return $range;
    }

    /** @return int|list<string>|string|null */
    public static function items(mixed $value): int|array|string|null
    {
        if (! is_array($value)) {
            return self::scalar($value);
        }

        $items = [];

        foreach ($value as $item) {
            if (is_scalar($item)) {
                $items[] = (string) $item;
            }
        }

        return $items;
    }

    /** @return array<string, mixed>|int|string|null */
    public static function map(mixed $value): array|int|string|null
    {
        if (! is_array($value)) {
            return self::scalar($value);
        }

        $map = [];

        foreach ($value as $key => $item) {
            $map[(string) $key] = $item;
        }

        return $map;
    }

    public static function text(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }

    private static function scalar(mixed $value): int|string|null
    {
        return match (true) {
            is_int($value), is_string($value) => $value,
            is_bool($value) => $value ? 'true' : 'false',
            is_float($value) => (string) $value,
            default => null,
        };
    }
}
