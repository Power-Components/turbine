<?php

namespace PowerComponents\Turbine\Components\Rules;

class RuleToggleable extends BaseRule
{
    public string $forAction = 'turbine-toggleable';

    public function __construct(public string $column)
    {
        $this->forAction = $column;
    }

    /**
     * Hides the Toggleable.
     */
    public function hide(): self
    {
        $this->setModifier('fieldHideToggleable', true);

        return $this;
    }

    /**
     * Shows the Toggleable.
     */
    public function show(): self
    {
        $this->setModifier('fieldHideToggleable', false);

        return $this;
    }
}
