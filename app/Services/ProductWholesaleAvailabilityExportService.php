<?php

namespace App\Services;

use App\Models\ProductWholesaleAvailability;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductWholesaleAvailabilityExportService
{
    public function download(): StreamedResponse
    {
        $spreadsheet = $this->spreadsheet();
        $fileName = 'product-wholesale-availabilities-export-'.now()->format('Y-m-d-His').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function spreadsheet(): Spreadsheet
    {
        $availabilities = ProductWholesaleAvailability::query()
            ->with(['product', 'wholesaleColor', 'wholesaleCustomerGroup'])
            ->latest('id')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('توافر الجملة');

        $rows = [[
            'رمز المنتج',
            'اسم المنتج بالعربي',
            'رمز لون الجملة',
            'اسم لون الجملة بالعربي',
            'اسم لون الجملة بالانكليزي',
            'فئة التاجر',
            'فئة التاجر بالانكليزي',
            'الحد الأقصى للكمية',
        ]];

        foreach ($availabilities as $availability) {
            $rows[] = [
                $availability->product?->model_no ?: '',
                $availability->product?->title_ar ?: '',
                $availability->wholesaleColor?->color_code ?: '',
                $availability->wholesaleColor?->color_name_ar ?: '',
                $availability->wholesaleColor?->color_name_en ?: '',
                $availability->wholesaleCustomerGroup?->name_ar ?: '',
                $availability->wholesaleCustomerGroup?->name_en ?: '',
                $availability->max_quantity ?? '',
            ];
        }

        $this->fillSheet($sheet, $rows);
        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    protected function fillSheet(Worksheet $sheet, array $rows): void
    {
        $sheet->fromArray($rows, null, 'A1');
        $sheet->freezePane('A2');
        $sheet->getStyle('1:1')->getFont()->setBold(true);

        $highestColumnIndex = Coordinate::columnIndexFromString($sheet->getHighestColumn());

        foreach (range(1, $highestColumnIndex) as $columnIndex) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($columnIndex))->setAutoSize(true);
        }
    }
}
