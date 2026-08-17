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
        $csvSeparatorRaw = data_get($exportOptions, 'csvSeparator', ',');
        $csvDelimiterRaw = data_get($exportOptions, 'csvDelimiter', '"');
        $csvSeparator = is_scalar($csvSeparatorRaw) ? (string) $csvSeparatorRaw : ',';
        $csvDelimiter = is_scalar($csvDelimiterRaw) ? (string) $csvDelimiterRaw : '"';

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
                $options = $refClass->newInstance($separator, $delimiter);
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

        /** @var list<bool|\DateInterval|\DateTimeInterface|float|int|string|null> $headerRow */
        $headerRow = (array) $headers;
        $writer->openToFile($filePath);
        $writer->addRow(Row::fromValues($headerRow));

        foreach ($rows as $row) {
            /** @var list<bool|\DateInterval|\DateTimeInterface|float|int|string|null> $dataRow */
            $dataRow = (array) $row;
            $writer->addRow(Row::fromValues($dataRow));
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

        /** @var array<int|string, bool|float|int|string|null> $headerRow */
        $headerRow = (array) $headers;
        fputcsv($file, $headerRow, $separator, $delimiter);

        foreach ($rows as $row) {
            /** @var array<int|string, bool|float|int|string|null> $dataRow */
            $dataRow = (array) $row;
            fputcsv($file, $dataRow, $separator, $delimiter);
        }

        fclose($file);
    }
}
