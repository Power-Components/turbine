<?php

namespace PowerComponents\Turbine\Response;

final readonly class MetaResponse implements \JsonSerializable
{
    /**
     * @param  array<string, mixed>|null  $filterBuilder
     * @param  array<string, mixed>|null  $filters
     * @param  array<string, mixed>|null  $setup
     */
    public function __construct(
        public PaginationResponse $pagination,
        public SortResponse $sort,
        public ?string $search = null,
        public ?array $filters = null,
        public ?array $filterBuilder = null,
        public ?array $setup = null,
    ) {}

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'pagination' => $this->pagination,
            'sort' => $this->sort,
            'search' => $this->search,
            'filters' => $this->filters,
            'filterBuilder' => $this->filterBuilder,
            'setup' => $this->setup,
        ];
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        return $this->jsonSerialize();
    }
}
