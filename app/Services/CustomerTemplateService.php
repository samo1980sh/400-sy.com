<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerTemplateService
{
    /**
     * Download a ready-to-fill Excel template for customer import.
     */
    public function download(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('customers_import');
        $sheet->setRightToLeft(true);

        $headers = [
            'رقم الحساب',
            'الاسم الكامل',
            'رقم الموبايل',
            'الموبايل الثانوي',
            'البريد الإلكتروني',
            'الجنس',
            'تاريخ الميلاد',
            'الجنسية',
            'المدينة',
            'المنطقة',
            'المهنة',
            'الحالة الاجتماعية',
            'الحالة',
            'ملاحظات',
        ];

        foreach ($headers as $index => $header) {
            $column = Coordinate::stringFromColumnIndex($index + 1);
            $sheet->setCellValue($column . '1', $header);
            $sheet->getColumnDimension($column)->setWidth($this->columnWidth($header));
        }

        $highestColumn = Coordinate::stringFromColumnIndex(count($headers));

        $sheet->getStyle('A1:' . $highestColumn . '1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F3F4F6'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D1D5DB'],
                ],
            ],
        ]);

        $sheet->getStyle('A:' . $highestColumn)->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setWrapText(true);

        $sheet->getStyle('A:A')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
        $sheet->getStyle('C:D')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
        $sheet->getStyle('E:E')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
        $sheet->getStyle('G:G')->getNumberFormat()->setFormatCode('yyyy-mm-dd');

        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:' . $highestColumn . '1');

        $filename = 'customers-import-template-' . now()->format('Y-m-d') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    protected function columnWidth(string $header): int
    {
        return match ($header) {
            'الاسم الكامل' => 28,
            'البريد الإلكتروني' => 30,
            'ملاحظات' => 38,
            'رقم الموبايل', 'الموبايل الثانوي' => 20,
            'الحالة الاجتماعية', 'تاريخ الميلاد' => 20,
            default => 18,
        };
    }
}
