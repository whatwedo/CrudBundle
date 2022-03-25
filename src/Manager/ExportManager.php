<?php

declare(strict_types=1);

namespace whatwedo\CrudBundle\Manager;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Contracts\Translation\TranslatorInterface;
use whatwedo\CoreBundle\Manager\FormatterManager;
use whatwedo\TableBundle\Table\Column;
use whatwedo\TableBundle\Table\Table;

class ExportManager
{
    private array $reports = [];

    public function __construct(
        protected FormatterManager $formatterManager,
        protected TranslatorInterface $translator

    ) {
    }

    public function prepareData(Table $table): array
    {
        $result = [];

        $result[] = $this->getHeader($table);

        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        foreach ($table->getRows() as $item) {
            $dataItem = [];

            foreach ($table->getColumns() as $column) {
                $data = $propertyAccessor->getValue($item, $column->getOption(Column::OPTION_ACCESSOR_PATH));

                if ($column->getOption(Column::OPTION_FORMATTER)) {
                    $formatter = $this->formatterManager->getFormatter($column->getOption(Column::OPTION_FORMATTER));
                    $formatter->processOptions($column->getOption(Column::OPTION_FORMATTER_OPTIONS));
                    $data = $formatter->getString($data);
                }

                $dataItem[] = $data;
            }

            $result[] = $dataItem;
        }

        return $result;
    }

    public function createSpreadsheet(Table $table, array $data): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // header
        foreach ($data as $rowIndex => $row) {
            if ($rowIndex === 0) {
                foreach ($row as $colIndex => $value) {
                    $columnIndex = $sheet->getColumnDimensionByColumn($colIndex + 1)->getColumnIndex();
                    $cell = $sheet->getCell($columnIndex . ($rowIndex + 1));
                    $cell->setValueExplicit($value, DataType::TYPE_STRING2);
                    $cell->getStyle()->getFont()->setBold(true);
                }
            } else {
                foreach ($row as $colIndex => $value) {
                    $columnIndex = $sheet->getColumnDimensionByColumn($colIndex + 1)->getColumnIndex();
                    $cell = $sheet->getCell($columnIndex . ($rowIndex + 1));

                    if ($value instanceof \DateTimeInterface) {
                        $value = Date::PHPToExcel($value);
                        $cell->setValue($value);
                    } else {
                        $cell->setValueExplicit($value, DataType::TYPE_STRING2);
                    }
                }
            }
        }

        for ($index = 1, $indexMax = count($table->getColumns()); $index < $indexMax; ++$index) {
            $sheet->getColumnDimensionByColumn($index)->setAutoSize(true);
            $sheet->getColumnDimensionByColumn($index)->setAutoSize(true);
        }

        return $spreadsheet;
    }

    /**
     * @return ReportInterface[]
     */
    public function getReports(): array
    {
        return $this->reports;
    }

    protected function getHeader(Table $table): array
    {
        $headerItem = [];

        foreach ($table->getColumns() as $column) {
            if ($column->getOption(Column::OPTION_LABEL)) {
                $headerItem[] = $this->translator->trans(
                    $column->getOption(Column::OPTION_LABEL)
                );
            } else {
                $headerItem[] = '';
            }
        }

        return $headerItem;
    }
}
