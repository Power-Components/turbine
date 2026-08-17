<?php

namespace PowerComponents\Turbine;

use Closure;
use Illuminate\Support\Traits\Macroable;
use PowerComponents\Turbine\Contracts\Definition;

/**
 * @phpstan-consistent-constructor
 *
 * @method static dispatch(string $event, array<string, mixed> $params)
 * @method static dispatchTo(string $component, string $event, array<string, mixed> $params)
 * @method static dispatchSelf(string $event, array<string, mixed> $params)
 * @method static openModal(string $component, array<string, mixed> $params)
 * @method static parent(string $method, array<string, mixed> $params)
 * @method static call(string $method, array<string, mixed> $params)
 * @method static toggleDetail(int|string $rowId)
 * @method static tooltip(string $value)
 * @method static route(string $route, array<string, mixed> $params, string $target = '_self')
 * @method static method(string $method)
 * @method static target(string $target) _blank, _self, _top, _parent, null
 * @method static can(bool|Closure $closure = true)
 * @method static id(string $id = null)
 * @method static confirm(string $message = 'Are you sure you want to perform this action?')
 * @method static confirmPrompt(string $message = 'Are you sure you want to perform this action?', string $confirmValue = 'Confirm')
 * @method static class(string $classes)
 * @method static disable(bool $disable = true)
 */
class Button implements Definition
{
    use Macroable;

    /** @var view-string|null */
    public ?string $view = null;

    /** @var array<string, mixed> */
    public array $attributes = [];

    public ?string $slot = '';

    public ?string $tag = 'button';

    public ?string $icon = '';

    /** @var array<string, mixed> */
    public array $iconAttributes = [];

    public bool|Closure $can = true;

    /** @var array<string, mixed> */
    public array $eventMeta = [];

    /**
     * Framework-neutral confirmation prompt, rendered in the JSON envelope for
     * any front-end to interpret.
     */
    public ?string $confirm = null;

    public bool $confirmIsPrompt = false;

    public function __construct(public string $action) {}

    public static function add(string $action = ''): static
    {
        return new static($action);
    }

    /** @param view-string $view */
    public function view(string $view): static
    {
        $this->view = $view;

        return $this;
    }

    public static function make(string $action, ?string $slot = null): static
    {
        return (new static($action))
            ->slot($slot);
    }

    public function tag(?string $tag = null): static
    {
        $this->tag = $tag;

        return $this;
    }

    public function slot(?string $slot = null): static
    {
        $this->slot = $slot;

        return $this;
    }

    /** @param  array<string, mixed>  $attributes */
    public function attributes(array $attributes): static
    {
        $this->attributes = array_merge($attributes, $this->attributes);

        return $this;
    }

    /** @param  array<string, mixed>  $iconAttributes */
    public function icon(string $icon, array $iconAttributes = []): static
    {
        $this->icon = $icon;
        $this->iconAttributes = $iconAttributes;

        return $this;
    }
}
