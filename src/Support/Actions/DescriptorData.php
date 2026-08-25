<?php

namespace PowerComponents\Turbine\Support\Actions;

/**
 * @internal Typed intermediate result produced by ActionsResolver::describe().
 */
final class DescriptorData
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(
        public string $id,
        public ?string $label,
        public ?string $icon,
        public ?string $tag,
        public bool $visible,
        public bool $disabled,
        public ?array $attributes,
    ) {}
}
