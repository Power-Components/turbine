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

        $style = new Style();
        if (method_exists($style, 'setFontBold')) {
            $headerStyle = (new Style())
                ->setFontBold()
                ->setFontSize(12)
                ->setShouldWrapText(false)
                ->setBackgroundColor('d0d3d8');
            $defaultStyle = (new Style())->setFontSize(12);
            $grayStyle = (new Style())->setFontSize(12)->setBackgroundColor($striped);
        } else {
            /** @var dynamic $style */
            $headerStyle = (new Style())
                ->withFontBold(true)
                ->withFontSize(12)
                ->withShouldWrapText(false)
                ->withBackgroundColor('d0d3d8');
            /** @var dynamic $style */
            $defaultStyle = (new Style())->withFontSize(12);
            /** @var dynamic $style */
            $grayStyle = (new Style())->withFontSize(12)->withBackgroundColor($striped);
        }

        $createRow = function (array $values, Style $style): Row {
            if (method_exists(Row::class, 'fromValuesWithStyle')) {
                /** @var dynamic $rowClass */
                $rowClass = Row::class;

                return $rowClass::fromValuesWithStyle($values, $style);
            }

            /** @var dynamic $rowClass */
            $rowClass = Row::class;

            return $rowClass::fromValues($values, $style);
        };

        /** @var list<bool|\DateInterval|\DateTimeInterface|float|int|string|null> $headerRow */
        $headerRow = (array) $headers;
        $writer->addRow($createRow($headerRow, $headerStyle));

        foreach ($columnWidth as $column => $width) {
            $colIndex = intval($column) + 1;
            if ($colIndex >= 1) {
                $options->setColumnWidth((int) $width, $colIndex);
            }
        }

        foreach ($rows as $key => $row) {
            /** @var list<bool|\DateInterval|\DateTimeInterface|float|int|string|null> $rowValues */
            $rowValues = (array) $row;
            if (count($rowValues) > 0) {
                $style = ($key % 2 && $striped !== '') ? $grayStyle : $defaultStyle;
                $writer->addRow($createRow($rowValues, $style));
            }
        }

        $writer->close();
    }
}
