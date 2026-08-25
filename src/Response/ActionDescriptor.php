<?php

namespace PowerComponents\Turbine\Response;

readonly class ActionDescriptor implements \JsonSerializable
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

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'icon' => $this->icon,
            'tag' => $this->tag,
            'visible' => $this->visible,
            'disabled' => $this->disabled,
            'attributes' => $this->attributes,
        ];
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        return $this->jsonSerialize();
    }
}
