<?php

namespace PowerComponents\Turbine\Components\Rules\Support;

class HideRule
{
    /** @return array{hide?: bool} */
    public function apply(bool $ruleData = false): array
    {
        $output = [];

        if ($ruleData) {
            $output['hide'] = true;
        }

        return $output;
    }
}
