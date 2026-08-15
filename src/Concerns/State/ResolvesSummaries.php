<?php

namespace PowerComponents\Turbine\Concerns\State;

trait ResolvesSummaries
{
    public function hasSummarizeInColumns(): bool
    {
        if (! $this->hasResolvedColumns()) {
            return false;
        }

        foreach ($this->declaredColumns() as $column) {
            if (data_get($column, 'properties.summarize')) {
                return true;
            }
        }

        return false;
    }
}
