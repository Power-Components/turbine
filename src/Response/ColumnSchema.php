<?php

namespace PowerComponents\Turbine\Response;

final readonly class ColumnSchema implements \JsonSerializable
{
    public function __construct(
        public string $field,
        public string $title,
        public bool $sortable,
        public bool $searchable,
        public bool $hidden,
    ) {}

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'field' => $this->field,
            'title' => $this->title,
            'sortable' => $this->sortable,
            'searchable' => $this->searchable,
            'hidden' => $this->hidden,
        ];
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        return $this->jsonSerialize();
    }
}
