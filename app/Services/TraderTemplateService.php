<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TraderTemplateService
{
    /**
     * Download a ready-to-fill Excel template for trader import.
     */
    public function download(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('traders_import');
        $sheet->setRightToLeft(true);

        $headers = [
            'رقم الحساب',
            'اسم التاجر',
            'رقم الموبايل',
            'رقم موبايل آخر',
            'البريد الإلكتروني',
            'فئة التاجر',
            'المدينة',
            'المنطقة',
            'العنوان',
            'الحالة',
            'كلمة المرور',
            'ملاحظات',
        ];

        foreach ($headers as $index => $header) {
            $column = $this->columnName($index + 1);
            $sheet->setCellValue($column . '1', $header);
            $sheet->getColumnDimension($column)->setWidth($this->columnWidth($header));
        }

        $sampleRows = [
            [
                'TR-001',
                'شركة الهدى للتجارة',
                '0991234567',
                '0111234567',
                'trader@example.com',
                'جملة',
                'دمشق',
                'المزة',
                'دمشق - المزة - الشارع الرئيسي',
                'active',
                '12345678',
                'سطر تجريبي يمكن حذفه قبل الاستيراد',
            ],
            [
                'TR-002',
                'مركز الشام للألبسة',
                '0987654321',
                '',
                '',
                'تاجر مميز',
                'حلب',
                'الجميلية',
                'حلب - الجميلية',
                'inactive',
                '',
                'الحالة يمكن أن تكون active أو inactive',
            ],
        ];

        foreach ($sampleRows as $rowIndex => $row) {
            $excelRow = $rowIndex + 2;
            foreach ($row as $columnIndex => $value) {
                $column = $this->columnName($columnIndex + 1);
                $sheet->setCellValueExplicit($column . $excelRow, (string) $value, DataType::TYPE_STRING);
            }
        }

        $highestColumn = $this->columnName(count($headers));
        $highestRow = count($sampleRows) + 1;

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

        $sheet->getStyle('A1:' . $highestColumn . $highestRow)->applyFromArray([
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'E5E7EB'],
                ],
            ],
        ]);

        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:' . $highestColumn . '1');

        $filename = 'traders-import-template-' . now()->format('Y-m-d') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    protected function columnName(int $number): string
    {
        $name = '';

        while ($number > 0) {
            $number--;
            $name = chr(65 + ($number % 26)) . $name;
            $number = intdiv($number, 26);
        }

        return $name;
    }

    protected function columnWidth(string $header): int
    {
        return match ($header) {
            'العنوان', 'ملاحظات' => 36,
            'البريد الإلكتروني' => 28,
            'اسم التاجر' => 26,
            'رقم موبايل آخر', 'رقم الموبايل' => 20,
            default => 18,
        };
    }
}
