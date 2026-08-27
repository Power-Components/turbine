<?php

namespace PowerComponents\Turbine\Components\Filters;

use Closure;
use Illuminate\Support\Collection;

class FilterMultiSelect extends FilterBase
{
    public string $key = 'multi_select';

    /** @var array<int, mixed>|Collection<int, mixed>|Closure */
    public array|Collection|Closure $dataSource;

    public string $optionValue = '';

    public string $optionLabel = '';

    /** @var list<string> */
    public array $depends = [];

    /** @var array<string, mixed> */
    public array $params = [];

    /** @param  list<string>  $fields */
    public function depends(array $fields): FilterMultiSelect
    {
        $this->depends = $fields;

        return $this;
    }

    /** @param  Collection<int, mixed>|array<int, mixed>|Closure  $collection */
    public function dataSource(Collection|array|Closure $collection): FilterMultiSelect
    {
        $this->dataSource = $collection;

        return $this;
    }

    public function optionValue(string $value): FilterMultiSelect
    {
        $this->optionValue = $value;

        return $this;
    }

    public function optionLabel(string $value): FilterMultiSelect
    {
        $this->optionLabel = $value;

        return $this;
    }

    /** @param  array<string, mixed>  $params */
    public function params(array $params): FilterMultiSelect
    {
        $this->params = $params;

        return $this;
    }
}
