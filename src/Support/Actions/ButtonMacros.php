<?php

namespace PowerComponents\Turbine\Support\Actions;

use PowerComponents\Turbine\Button;

/**
 * Registers the framework-agnostic action DSL on {@see Button}.
 *
 * These macros describe *what* an action does through a neutral `eventMeta`
 * descriptor and neutral HTML attributes (class / id / title / disabled /
 * href / target) — never any framework binding such as `wire:click`. Any
 * front-end (Inertia, React, Vue, a JSON API) consumes `eventMeta` from the
 * response envelope and decides how to bind it.
 *
 * A companion package can register richer, framework-specific versions of
 * these macros (which additionally emit `wire:*` bindings for server-side
 * Blade rendering). Registration here is therefore skipped entirely when that
 * set is already present, so the framework-specific layer always wins in an
 * app that ships it, and these agnostic versions are the fallback for
 * headless installs that ship the core alone.
 */
final class ButtonMacros
{
    public static function register(): void
    {
        // `link` is core-only (a framework package may use `route()` instead), so
        // register it independently of the shared set below.
        if (! Button::hasMacro('link')) {
            Button::macro('link', function (string $href, string $target = '_self'): Button {
                $this->tag('a')->attributes(['href' => $href, 'target' => $target]);
                $this->eventMeta = ['type' => 'link', 'href' => $href, 'target' => $target];

                return $this;
            });
        }

        // A companion consumer already registered its richer, framework-specific
        // set — leave it untouched.
        if (Button::hasMacro('dispatch')) {
            return;
        }

        Button::macro('class', function (string $classes): Button {
            return $this->attributes(['class' => $classes]);
        });

        Button::macro('id', function (?string $id = null): Button {
            return $this->attributes(['id' => $id]);
        });

        Button::macro('tooltip', function (string $value): Button {
            return $this->attributes(['title' => $value]);
        });

        Button::macro('can', function (bool|\Closure $closure = true): Button {
            $this->can = $closure;

            return $this;
        });

        Button::macro('disable', function (bool $disable = true): Button {
            if ($disable) {
                $this->attributes(['disabled' => 'disabled']);
            }

            return $this;
        });

        Button::macro('call', function (string $method, array $params = []): Button {
            $this->eventMeta = ['type' => 'call', 'method' => $method, 'params' => $params];

            return $this;
        });

        Button::macro('dispatch', function (string $event, array $params = []): Button {
            $this->eventMeta = ['type' => 'dispatch', 'event' => $event, 'params' => $params];

            return $this;
        });

        Button::macro('dispatchTo', function (string $component, string $event, array $params = []): Button {
            $this->eventMeta = ['type' => 'dispatchTo', 'to' => $component, 'event' => $event, 'params' => $params];

            return $this;
        });

        Button::macro('dispatchSelf', function (string $event, array $params = []): Button {
            $this->eventMeta = ['type' => 'dispatchSelf', 'event' => $event, 'params' => $params];

            return $this;
        });

        Button::macro('parent', function (string $method, array $params = []): Button {
            $this->eventMeta = ['type' => 'parent', 'method' => $method, 'params' => $params];

            return $this;
        });

        Button::macro('openModal', function (string $component, array $params = []): Button {
            $this->eventMeta = ['type' => 'modal', 'component' => $component, 'params' => $params];

            return $this;
        });

        Button::macro('toggleDetail', function (int|string $rowId): Button {
            $this->eventMeta = ['type' => 'toggleDetail', 'rowId' => $rowId];

            return $this;
        });

        Button::macro('route', function (string $route, array $params = [], string $target = '_self'): Button {
            $href = route($route, $params);

            $this->tag('a')->attributes(['href' => $href, 'target' => $target]);
            $this->eventMeta = ['type' => 'link', 'href' => $href, 'target' => $target];

            return $this;
        });

        Button::macro('confirm', function (?string $message = null): Button {
            $this->confirm = $message ?? 'Are you sure you want to perform this action?';
            $this->confirmIsPrompt = false;

            return $this;
        });

        Button::macro('confirmPrompt', function (?string $message = null, string $confirmValue = 'Confirm'): Button {
            $confirmValue = trim($confirmValue);
            $this->confirm = $message ?? "Are you sure you want to perform this action?\nType {$confirmValue} to confirm";
            $this->confirmIsPrompt = true;

            return $this;
        });
    }
}
