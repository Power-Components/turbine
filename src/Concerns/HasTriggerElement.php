<?php

namespace PowerComponents\Turbine\Concerns;

use Closure;
use PowerComponents\Turbine\Components\SetUp\HeaderElement;

trait HasTriggerElement
{
    public HeaderElement $trigger;

    /** @param  array<string, mixed>  $iconAttributes */
    public function icon(string $icon, array $iconAttributes = []): static
    {
        $this->triggerElement()->icon($icon, $iconAttributes);

        return $this;
    }

    public function title(string $title): static
    {
        $this->triggerElement()->title($title);

        return $this;
    }

    public function trigger(Closure $config): static
    {
        $config($this->triggerElement());

        return $this;
    }

    protected function triggerElement(): HeaderElement
    {
        return $this->trigger ??= new HeaderElement($this->triggerKey());
    }

    protected function triggerKey(): string
    {
        return 'trigger';
    }
}
