<?php

namespace PowerComponents\Turbine\Components\Filters;

class FilterNumber extends FilterBase
{
    public string $key = 'number';

    public string $thousands = '';

    public string $decimal = '';

    /** @var array{min?: string, max?: string} */
    public array $placeholder = [];

    public function thousands(string $thousands): FilterNumber
    {
        $this->thousands = $thousands;

        return $this;
    }

    public function decimal(string $decimal): FilterNumber
    {
        $this->decimal = $decimal;

        return $this;
    }

    public function placeholder(string $min, string $max): FilterNumber
    {
        $this->placeholder = [
            'min' => $min,
            'max' => $max,
        ];

        return $this;
    }
}
