<?php

namespace PowerComponents\Turbine;

use Closure;
use Illuminate\Support\Traits\Macroable;

final class Fields
{
    use Macroable;

    /** @var array<string, Closure> */
    public array $fields = [];

    /**
     * @return $this
     */
    public function add(string $fieldName, ?Closure $closure = null): Fields
    {
        // Escaping is NOT applied here: it happens at the render sink
        // (CellRenderer / row.blade.php), which guarantees a single, consistent
        // escape for every cell regardless of whether the value came from this
        // default closure or a custom one.
        $this->fields[$fieldName] = $closure ?? fn ($model) => data_get($model, $fieldName);

        return $this;
    }
}
