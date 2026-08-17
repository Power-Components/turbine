<?php

namespace PowerComponents\Turbine\Export\Drivers;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\CSV\{Options, Writer};
use PowerComponents\Turbine\Components\SetUp\Exportable;
use ReflectionClass;
use RuntimeException;

class CsvExporter implements ExportDriverInterface
{
    /**
     * @param  list<string>  $headers
     * @param  iterable<int, list<mixed>>  $rows
     * @param  Exportable|array<string, mixed>  $exportOptions
     */
    public function build(string $filePath, array $headers, iterable $rows, Exportable|array $exportOptions): void
    {
        $csvSeparator = strval(data_get($exportOptions, 'csvSeparator', ','));
        $csvDelimiter = strval(data_get($exportOptions, 'csvDelimiter', '"'));

        if (class_exists(Writer::class)) {
            $this->buildWithOpenSpout($filePath, $headers, $rows, $csvSeparator, $csvDelimiter);

            return;
        }

        $this->buildNativeCsv($filePath, $headers, $rows, $csvSeparator, $csvDelimiter);
    }

    /**
     * @param  list<string>  $headers
     * @param  iterable<int, list<mixed>>  $rows
     */
    private function buildWithOpenSpout(
        string $filePath,
        array $headers,
        iterable $rows,
        string $separator,
        string $delimiter
    ): void {
        if (class_exists(Options::class)) {
            $refClass = new ReflectionClass(Options::class);
            $constructor = $refClass->getConstructor();

            if ($constructor !== null && $constructor->getNumberOfParameters() > 0) {
                // OpenSpout v5
                $options = new Options($separator, $delimiter);
            } else {
                // OpenSpout v4
                $options = new Options();
                $options->FIELD_DELIMITER = $separator;
                $options->FIELD_ENCLOSURE = $delimiter;
            }

            /** @var Writer $writer */
            $writer = new Writer($options);
        } else {
            /** @var Writer $writer */
            $writer = new Writer();
        }

        $writer->openToFile($filePath);
        $writer->addRow(Row::fromValues($headers));

        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues($row));
        }

        $writer->close();
    }

    /**
     * @param  list<string>  $headers
     * @param  iterable<int, list<mixed>>  $rows
     */
    private function buildNativeCsv(
        string $filePath,
        array $headers,
        iterable $rows,
        string $separator,
        string $delimiter
    ): void {
        $file = fopen($filePath, 'w');

        if ($file === false) {
            throw new RuntimeException("Unable to open path [{$filePath}] for writing CSV.");
        }

        fputcsv($file, $headers, $separator, $delimiter);

        foreach ($rows as $row) {
            fputcsv($file, $row, $separator, $delimiter);
        }

        fclose($file);
    }
}
