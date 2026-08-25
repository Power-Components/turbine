<?php

namespace PowerComponents\Turbine\Response;

final readonly class SortResponse implements \JsonSerializable
{
    /**
     * @param  array<string, mixed>|null  $sortArray
     */
    public function __construct(
        public ?string $field = null,
        public ?string $direction = null,
        public mixed $multiSort = null,
        public ?array $sortArray = null,
    ) {}

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'field' => $this->field,
            'direction' => $this->direction,
            'multiSort' => $this->multiSort,
            'sortArray' => $this->sortArray,
        ];
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        return $this->jsonSerialize();
    }
}
