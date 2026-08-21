<?php

namespace PowerComponents\Turbine\Components\SetUp;

use Closure;
use PowerComponents\Turbine\Contracts\Definition;

class Header implements Definition
{
    public string $name = 'header';

    public bool $searchInput = false;

    public bool $toggleColumns = false;

    public bool $softDeletes = false;

    public bool $showMessageSoftDeletes = false;

    public string $includeViewOnTop = '';

    public string $includeViewOnBottom = '';

    public bool $wireLoading = true;

    /** @var array<string, HeaderElement> */
    public array $elements = [];

    /**
     * @return $this
     *               Show search input into component
     */
    public function showSearchInput(?Closure $config = null): Header
    {
        $this->searchInput = true;

        $this->element('search', $config);

        return $this;
    }

    public function showSoftDeletes(bool $showMessage = true, ?Closure $config = null): Header
    {
        $this->softDeletes = true;
        $this->showMessageSoftDeletes = $showMessage;

        $this->element('softDeletes', $config);

        return $this;
    }

    /**
     * default false
     */
    public function showToggleColumns(?Closure $config = null): Header
    {
        $this->toggleColumns = true;

        $this->element('toggleColumns', $config);

        return $this;
    }

    public function filtersToggle(Closure $config): Header
    {
        $this->element('filters', $config);

        return $this;
    }

    public function clearFiltersPill(Closure $config): Header
    {
        $this->element('clearFilters', $config);

        return $this;
    }

    public function searchClearIcon(Closure $config): Header
    {
        $this->element('searchClear', $config);

        return $this;
    }

    /**
     * Include custom view on top
     */
    public function includeViewOnTop(string $viewPath): Header
    {
        $this->includeViewOnTop = $viewPath;

        return $this;
    }

    /**
     * Include custom view on bottom
     */
    public function includeViewOnBottom(string $viewPath): Header
    {
        $this->includeViewOnBottom = $viewPath;

        return $this;
    }

    /**
     * Hides the default loading state
     */
    public function withoutLoading(): Header
    {
        $this->wireLoading = false;

        return $this;
    }

    private function element(string $key, ?Closure $config): HeaderElement
    {
        $element = $this->elements[$key] ??= new HeaderElement($key);

        if ($config instanceof Closure) {
            $config($element);
        }

        return $element;
    }
}
