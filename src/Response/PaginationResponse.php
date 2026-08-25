<?php

namespace PowerComponents\Turbine\Response;

final readonly class PaginationResponse implements \JsonSerializable
{
    public function __construct(
        public int $currentPage,
        public int $perPage,
        public ?int $from = null,
        public ?int $to = null,
        public ?int $total = null,
        public ?int $lastPage = null,
    ) {}

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'current_page' => $this->currentPage,
            'per_page' => $this->perPage,
            'from' => $this->from,
            'to' => $this->to,
            'total' => $this->total,
            'last_page' => $this->lastPage,
        ];
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        return $this->jsonSerialize();
    }
}
