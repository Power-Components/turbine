<?php

namespace PowerComponents\Turbine;

use Closure;
use Illuminate\Support\Traits\Macroable;
use PowerComponents\Turbine\Contracts\Definition;

/**
 * Macros
 *
 * @method static withSummary(string $key, string $label, \Closure $using, bool $header = false, bool $footer = true)
 * @method static naturalSort()
 * @method static searchableRaw(string $sql)
 * @method static searchableJson(string $tableName) // sqlite, mysql
 *
 * Deprecated summary helpers — prefer withSummary() with a closure.
 * @method static withSum(string $label, bool $header = false, bool $footer = true) @deprecated since 7.x, use withSummary() instead
 * @method static withCount(string $label, bool $header = false, bool $footer = true) @deprecated since 7.x, use withSummary() instead
 * @method static withAvg(string $label, bool $header = false, bool $footer = true) @deprecated since 7.x, use withSummary() instead
 * @method static withMin(string $label, bool $header = false, bool $footer = true) @deprecated since 7.x, use withSummary() instead
 * @method static withMax(string $label, bool $header = false, bool $footer = true) @deprecated since 7.x, use withSummary() instead
 */
class Column implements Definition
{
    use Macroable;

    public string $title = '';

    public string $field = '';

    public string $dataField = '';

    public string $placeholder = '';

    public bool $searchable = false;

    public bool $enableSort = false;

    public bool $hidden = false;

    public bool $forceHidden = false;

    public ?bool $visibleInExport = null;

    public bool $sortable = false;

    public ?Closure $sortCallback = null;

    public bool $index = false;

    /** @var array<string, mixed> */
    public array $properties = [];

    /** @var list<array<string, mixed>> */
    public array $rawQueries = [];

    public bool $isAction = false;

    public bool $fixedOnResponsive = false;

    public bool $template = false;

    public string $contentClassField = '';

    /** @var string|list<string> */
    public string|array $contentClasses = [];

    public string $headerClass = '';

    public string $headerStyle = '';

    public string $bodyClass = '';

    public string $bodyStyle = '';

    /** @var array<string, mixed> */
    public array $pluginData = [];

    /** @var array<string, Closure> */
    public array $summaryCallbacks = [];

    public mixed $filters = null;

    /** @var array<string, mixed> */
    public array $customContent = [];

    /**
     * Adds a new Column
     */
    public static function add(): static
    {
        return new static();
    }

    /**
     * Make a new Column
     */
    public static function make(string $title, string $field, string $dataField = ''): static
    {
        return (new static())
            ->title($title)
            ->field($field, $dataField);
    }

    /**
     * Make a new action
     */
    public static function action(string $title): static
    {
        return (new static())
            ->title($title)
            ->isAction()
            ->visibleInExport(false);
    }

    public function isAction(): static
    {
        $this->isAction = true;

        return $this;
    }

    /**
     * Adds title
     */
    public function title(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function fixedOnResponsive(): static
    {
        $this->fixedOnResponsive = true;

        return $this;
    }

    /**
     * Adds index ($loop->index)
     */
    public function index(): static
    {
        $this->index = true;

        return $this;
    }

    /**
     * Adds placeholder
     */
    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    /**
     * Makes the column searchable
     */
    public function searchable(): static
    {
        $this->searchable = true;

        return $this;
    }

    public function enableSort(): static
    {
        $this->enableSort = true;

        return $this;
    }

    /**
     * Adds sort to the column header
     */
    public function sortable(): static
    {
        $this->enableSort();

        $this->sortable = true;

        return $this;
    }

    /**
     * Sets a custom sorting callback for this column.
     * The callback receives the query builder and sort direction.
     */
    public function sortUsing(Closure $callback): static
    {
        $this->enableSort();

        $this->sortable = true;

        $this->sortCallback = $callback;

        return $this;
    }

    /**
     * Field in the database
     */
    public function field(string $field, string $dataField = ''): static
    {
        $this->field = $field;

        $this->dataField = filled($dataField) ? $dataField : $field;

        return $this;
    }

    /**
     * Class html tag header table
     */
    public function headerAttribute(string $classAttr = '', string $styleAttr = ''): static
    {
        $this->headerClass = $classAttr;
        $this->headerStyle = $styleAttr;

        return $this;
    }

    /**
     * Class html tag body table
     */
    public function bodyAttribute(string $classAttr = '', string $styleAttr = ''): static
    {
        $this->bodyClass = $classAttr;
        $this->bodyStyle = $styleAttr;

        return $this;
    }

    /**
     * Hide the column
     */
    public function hidden(bool $isHidden = true, bool $isForceHidden = true): static
    {
        $this->hidden = $isHidden;
        $this->forceHidden = $isForceHidden;

        return $this;
    }

    public function visibleInExport(?bool $visible): static
    {
        $this->visibleInExport = $visible;

        return $this;
    }

    public function contentClassField(string $dataField = ''): static
    {
        $this->contentClassField = $dataField;

        return $this;
    }

    /** @param  string|list<string>  $contentClasses */
    public function contentClasses(string|array $contentClasses): static
    {
        $this->contentClasses = $contentClasses;

        return $this;
    }

    public function template(): static
    {
        $this->template = true;

        return $this;
    }
}
