<?php

namespace PowerComponents\Turbine\Components\SetUp;

use PowerComponents\Turbine\Contracts\Definition;

final class FilterBuilder implements Definition
{
    public const MATCH_AND = 'and';

    public const MATCH_OR = 'or';

    public string $name = 'filterBuilder';

    public string $match = self::MATCH_AND;

    public int $maxConditions = 30;

    public bool $hideDefaultFilters = false;

    public bool $persist = false;

    /** @var list<string> */
    public array $only = [];

    /** @var list<string> */
    public array $except = [];

    public function match(string $match): self
    {
        $this->match = $match === self::MATCH_OR ? self::MATCH_OR : self::MATCH_AND;

        return $this;
    }

    public function maxConditions(int $max): self
    {
        $this->maxConditions = max(1, $max);

        return $this;
    }

    public function hideDefaultFilters(bool $hide = true): self
    {
        $this->hideDefaultFilters = $hide;

        return $this;
    }

    public function persist(bool $persist = true): self
    {
        $this->persist = $persist;

        return $this;
    }

    /** @param  list<string>  $fields */
    public function only(array $fields): self
    {
        $this->only = array_values($fields);

        return $this;
    }

    /** @param  list<string>  $fields */
    public function except(array $fields): self
    {
        $this->except = array_values($fields);

        return $this;
    }
}
