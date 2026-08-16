<?php

use PowerComponents\Turbine\DataSource\Processors\{CollectionProcessor, ModelProcessor, ScoutBuilderProcessor};

return [

    /*
    |--------------------------------------------------------------------------
    | Max results per page
    |--------------------------------------------------------------------------
    |
    | Upper bound applied to the requested per-page size. A request for more
    | than this many rows is clamped to this value (0 disables the clamp).
    |
    */
    'max_per_page' => 1000,

    /*
    |--------------------------------------------------------------------------
    | DataSource Processors
    |--------------------------------------------------------------------------
    |
    | Registered DataSource processor classes evaluated in sequence.
    |
    */
    'datasources' => [
        CollectionProcessor::class,
        ScoutBuilderProcessor::class,
        ModelProcessor::class,
    ],
];
