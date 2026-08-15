<?php

namespace PowerComponents\Turbine\Support;

use Illuminate\Support\Facades\Schema;

final class SchemaInspector
{
    /** @return array<string, string> */
    public static function columnTypes(string $table, ?string $connection = null): array
    {
        /** @var array<string, string> $types */
        $types = TableCache::getOrCreate(
            $table,
            fn (): array => collect(Schema::connection($connection)->getColumns($table))
                ->pluck('type', 'name')
                ->toArray()
        );

        return $types;
    }

    /** @return list<string> */
    public static function columnListing(string $table, ?string $connection = null): array
    {
        return Schema::connection($connection)->getColumnListing($table);
    }
}
