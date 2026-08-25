<?php

namespace PowerComponents\Turbine\Response;

final readonly class FilterSchema implements \JsonSerializable
{
    public function __construct(
        public string $key,
        public string $field,
        public string $column,
        public ?string $title = null,
    ) {}

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'key' => $this->key,
            'field' => $this->field,
            'column' => $this->column,
            'title' => $this->title,
        ];
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        return $this->jsonSerialize();
    }
}
