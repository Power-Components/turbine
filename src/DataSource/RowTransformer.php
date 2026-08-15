<?php

namespace PowerComponents\Turbine\DataSource;

use PowerComponents\Turbine\Fields;
use stdClass;

final class RowTransformer
{
    /** @var array<string, \Closure> */
    private array $fieldClosures;

    public function __construct(protected Fields $fields)
    {
        $this->fieldClosures = $this->fields->fields;
    }

    public function transform(object $row): stdClass
    {
        $transformed = new stdClass();

        foreach ($this->fieldClosures as $key => $closure) {
            $value = $closure($row);

            $transformed->{$key} = $value;
        }

        return $transformed;
    }
}
