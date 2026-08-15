<?php

namespace PowerComponents\Turbine\Components\Filters;

use Closure;
use Illuminate\Support\Collection;

class FilterSelect extends FilterBase
{
    public string $key = 'select';

    /** @var array<int, mixed>|Collection<int, mixed>|Closure */
    public array|Collection|Closure $dataSource;

    public string $optionValue = '';

    public string $optionLabel = '';

    /** @var list<string> */
    public array $depends = [];

    /** @var array<string, mixed> */
    public array $params = [];

    public string $computedDatasource = '';

    /** @param  list<string>  $fields */
    public function depends(array $fields): FilterSelect
    {
        $this->depends = $fields;

        return $this;
    }

    /** @param  Collection<int, mixed>|array<int, mixed>|Closure  $collection */
    public function dataSource(Collection|array|Closure $collection): FilterSelect
    {
        $this->dataSource = $collection;

        return $this;
    }

    public function computedDatasource(string $computedDatasource): FilterSelect
    {
        $this->computedDatasource = $computedDatasource;

        return $this;
    }

    public function optionValue(string $value): FilterSelect
    {
        $this->optionValue = $value;

        return $this;
    }

    public function optionLabel(string $value): FilterSelect
    {
        $this->optionLabel = $value;

        return $this;
    }

    /** @param  array<string, mixed>  $params */
    public function params(array $params): FilterSelect
    {
        $this->params = $params;

        return $this;
    }
}
