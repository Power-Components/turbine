<?php

namespace PowerComponents\Turbine\Support\State;

use Illuminate\Http\Request;

final readonly class State
{
    /**
     * @param  array<string, string>  $sortArray
     * @param  array<string, mixed>  $filters
     * @param  array<string, mixed>  $filterBuilder
     * @param  array<string, mixed>  $setUp
     * @param  list<mixed>  $columns
     */
    public function __construct(
        public string $search = '',
        public string $sortField = 'id',
        public string $sortDirection = 'asc',
        public bool $multiSort = false,
        public array $sortArray = [],
        public array $filters = [],
        public array $filterBuilder = [],
        public string $softDeletes = '',
        public array $setUp = [],
        public array $columns = [],
        public string $primaryKey = 'id',
        public ?string $primaryKeyAlias = null,
        public bool $ignoreTablePrefix = true,
        public bool $pruneHiddenColumns = true,
        public bool $paginateRaw = false,
        public bool $isExporting = false,
        public string $tableName = '',
        public bool $supportModel = true,
    ) {}

    public function realPrimaryKey(): string
    {
        return $this->primaryKeyAlias ?? $this->primaryKey;
    }

    /** @param  array<string, mixed>  $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            search: self::asString($payload['search'] ?? ''),
            sortField: self::asString($payload['sortField'] ?? 'id', 'id'),
            sortDirection: self::asString($payload['sortDirection'] ?? 'asc', 'asc'),
            multiSort: (bool) ($payload['multiSort'] ?? false),
            sortArray: self::asStringMap($payload['sortArray'] ?? []),
            filters: self::asArray($payload['filters'] ?? []),
            filterBuilder: self::asArray($payload['filterBuilder'] ?? []),
            softDeletes: self::asString($payload['softDeletes'] ?? ''),
            setUp: self::asArray($payload['setUp'] ?? []),
            columns: array_values(self::asArray($payload['columns'] ?? [])),
            primaryKey: self::asString($payload['primaryKey'] ?? 'id', 'id'),
            primaryKeyAlias: isset($payload['primaryKeyAlias']) ? self::asString($payload['primaryKeyAlias']) : null,
            ignoreTablePrefix: (bool) ($payload['ignoreTablePrefix'] ?? true),
            pruneHiddenColumns: (bool) ($payload['pruneHiddenColumns'] ?? true),
            paginateRaw: (bool) ($payload['paginateRaw'] ?? false),
            isExporting: (bool) ($payload['isExporting'] ?? false),
            tableName: self::asString($payload['tableName'] ?? ''),
            supportModel: (bool) ($payload['supportModel'] ?? true),
        );
    }

    private static function asString(mixed $value, string $default = ''): string
    {
        return is_scalar($value) ? (string) $value : $default;
    }

    /** @return array<string, mixed> */
    private static function asArray(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $out = [];

        /** @var mixed $item */
        foreach ($value as $key => $item) {
            $out[(string) $key] = $item;
        }

        return $out;
    }

    /** @return array<string, string> */
    private static function asStringMap(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $out = [];

        /** @var mixed $item */
        foreach ($value as $key => $item) {
            $out[(string) $key] = is_scalar($item) ? (string) $item : '';
        }

        return $out;
    }

    public static function fromRequest(Request $request, string $key = 'powergrid'): self
    {
        $flatKeys = ['search', 'sortField', 'sortDirection', 'filters', 'sortArray', 'softDeletes', 'filterBuilder'];
        $flat = [];
        foreach ($flatKeys as $flatKey) {
            if ($request->has($flatKey)) {
                $flat[$flatKey] = $request->input($flatKey);
            }
        }

        /** @var array<string, mixed> $nested */
        $nested = (array) ($request->input($key) ?? $request->input('powergrid') ?? $request->input('turbine') ?? []);

        return self::fromArray(array_merge($flat, $nested));
    }
}
