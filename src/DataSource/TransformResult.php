<?php

namespace PowerComponents\Turbine\DataSource;

use Illuminate\Support\Collection as BaseCollection;

final readonly class TransformResult
{
    /**
     * @param  BaseCollection<int, mixed>  $collection
     * @param  array<int|string, list<array<string, mixed>>>  $actionsByRow
     */
    public function __construct(
        public BaseCollection $collection,
        public array $actionsByRow = [],
    ) {}

    /** @return array<int|string, list<array<string, mixed>>> */
    public function getActionsByRow(): array
    {
        return $this->actionsByRow;
    }

    /** @return BaseCollection<int, mixed> */
    public function getCollection(): BaseCollection
    {
        return $this->collection;
    }
}
