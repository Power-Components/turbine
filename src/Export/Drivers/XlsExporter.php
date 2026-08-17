<?php

namespace PowerComponents\Turbine\Export\Drivers;

use Exception;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\{Options, Writer};
use PowerComponents\Turbine\Components\SetUp\Exportable;

class XlsExporter implements ExportDriverInterface
{
    /**
     * @param  list<string>  $headers
     * @param  iterable<int, list<mixed>>  $rows
     * @param  Exportable|array<string, mixed>  $exportOptions
     */
    public function build(string $filePath, array $headers, iterable $rows, Exportable|array $exportOptions): void
    {
        if (! class_exists(Writer::class)) {
            throw new Exception(
                'OpenSpout XLSX writer not found. Please install openspout/openspout to export to Excel (XLSX).'
            );
        }

        $striped = strval(data_get($exportOptions, 'striped'));
        /** @var array<int, float> $columnWidth */
        $columnWidth = (array) data_get($exportOptions, 'columnWidth', []);

        /** @var Options $options */
        $options = new Options();
        /** @var Writer $writer */
        $writer = new Writer($options);

        $writer->openToFile($filePath);

        $headerStyle = (new Style())
            ->withFontBold(true)
            ->withFontSize(12)
            ->withShouldWrapText(false)
            ->withBackgroundColor('d0d3d8');

        $writer->addRow(Row::fromValuesWithStyle($headers, $headerStyle));

        foreach ($columnWidth as $column => $width) {
            $options->setColumnWidth($width, $column);
        }

        $defaultStyle = (new Style())
            ->withFontSize(12);

        $grayStyle = (new Style())
            ->withFontSize(12)
            ->withBackgroundColor($striped);

        foreach ($rows as $key => $row) {
            if (count($row) > 0) {
                if ($key % 2 && $striped !== '') {
                    $spoutRow = Row::fromValuesWithStyle($row, $grayStyle);
                } else {
                    $spoutRow = Row::fromValuesWithStyle($row, $defaultStyle);
                }
                $writer->addRow($spoutRow);
            }
        }

        $writer->close();
    }
}
