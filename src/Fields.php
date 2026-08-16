<?php

namespace PowerComponents\Turbine;

use Closure;
use Illuminate\Support\Traits\Macroable;

class Fields
{
    use Macroable;

    /** @var array<string, Closure> */
    public array $fields = [];

    /**
     * @return $this
     */
    public function add(string $fieldName, ?Closure $closure = null): static
    {
        $this->fields[$fieldName] = $closure ?? fn ($model) => data_get($model, $fieldName);

        return $this;
    }
}
