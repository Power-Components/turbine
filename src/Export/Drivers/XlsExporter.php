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

        $stripedRaw = data_get($exportOptions, 'striped');
        $striped = is_scalar($stripedRaw) ? (string) $stripedRaw : '';
        /** @var array<int|string, float|int> $columnWidth */
        $columnWidth = (array) data_get($exportOptions, 'columnWidth', []);

        /** @var Options $options */
        $options = new Options();
        /** @var Writer $writer */
        $writer = new Writer($options);

        $writer->openToFile($filePath);

        $headerStyle = (new Style())
            ->setFontBold()
            ->setFontSize(12)
            ->setShouldWrapText(false)
            ->setBackgroundColor('d0d3d8');

        /** @var list<bool|\DateInterval|\DateTimeInterface|float|int|string|null> $headerRow */
        $headerRow = (array) $headers;
        $writer->addRow(Row::fromValues($headerRow, $headerStyle));

        foreach ($columnWidth as $column => $width) {
            $colIndex = intval($column) + 1;
            if ($colIndex >= 1) {
                $options->setColumnWidth((int) $width, $colIndex);
            }
        }

        $defaultStyle = (new Style())->setFontSize(12);
        $grayStyle = (new Style())->setFontSize(12)->setBackgroundColor($striped);

        foreach ($rows as $key => $row) {
            /** @var list<bool|\DateInterval|\DateTimeInterface|float|int|string|null> $rowValues */
            $rowValues = (array) $row;
            if (count($rowValues) > 0) {
                $style = ($key % 2 && $striped !== '') ? $grayStyle : $defaultStyle;
                $writer->addRow(Row::fromValues($rowValues, $style));
            }
        }

        $writer->close();
    }
}
