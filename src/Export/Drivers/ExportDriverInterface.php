<?php

namespace PowerComponents\Turbine\Export\Drivers;

use PowerComponents\Turbine\Components\SetUp\Exportable;

interface ExportDriverInterface
{
    /**
     * @param  list<string>  $headers
     * @param  iterable<int, list<mixed>>  $rows
     * @param  Exportable|array<string, mixed>  $exportOptions
     */
    public function build(string $filePath, array $headers, iterable $rows, Exportable|array $exportOptions): void;
}
