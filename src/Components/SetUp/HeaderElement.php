<?php

namespace PowerComponents\Turbine\Components\SetUp;

use PowerComponents\Turbine\Contracts\Definition;

/**
 * User configuration for a single header element (icon, label and view).
 *
 * Values left empty mean "not configured", so the grid falls back to the
 * theme token and then to the package default.
 */
final class HeaderElement implements Definition
{
    public string $icon = '';

    /** @var array<string, mixed> */
    public array $iconAttributes = [];

    public bool $iconDisabled = false;

    public string $title = '';

    public ?bool $showLabel = null;

    public string $view = '';

    public function __construct(public string $key = '') {}

    /** @param  array<string, mixed>  $iconAttributes */
    public function icon(string $icon, array $iconAttributes = []): self
    {
        $this->icon = $icon;
        $this->iconDisabled = false;
        $this->iconAttributes = $iconAttributes;

        return $this;
    }

    /** @param  array<string, mixed>  $iconAttributes */
    public function iconAttributes(array $iconAttributes): self
    {
        $this->iconAttributes = $iconAttributes;

        return $this;
    }

    public function withoutIcon(): self
    {
        $this->iconDisabled = true;

        return $this;
    }

    /**
     * Literal text or a translation key.
     */
    public function title(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function showLabel(bool $show = true): self
    {
        $this->showLabel = $show;

        return $this;
    }

    public function hideLabel(): self
    {
        return $this->showLabel(false);
    }

    public function view(string $view): self
    {
        $this->view = $view;

        return $this;
    }
}
