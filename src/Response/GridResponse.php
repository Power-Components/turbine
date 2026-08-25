<?php

namespace PowerComponents\Turbine\Response;

final readonly class GridResponse implements \JsonSerializable
{
    /**
     * @param  array<int, array<string, mixed>>  $data
     * @param  list<ColumnSchema>  $columns
     * @param  list<FilterSchema>|null  $filters
     * @param  array<string, list<ActionDescriptor>>|null  $actions
     */
    public function __construct(
        public array $data,
        public MetaResponse $meta,
        public array $columns,
        public ?array $filters = null,
        public ?array $actions = null,
    ) {}

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        $result = [
            'data' => $this->data,
            'meta' => $this->meta,
            'columns' => $this->columns,
        ];

        if ($this->filters !== null) {
            $result['filters'] = $this->filters;
        }

        if ($this->actions !== null) {
            $result['actions'] = $this->actions;
        }

        return $result;
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        return $this->jsonSerialize();
    }
}
