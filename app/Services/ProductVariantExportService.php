<?php

namespace App\Services;

use App\Models\ProductVariant;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductVariantExportService
{
    public function download(): StreamedResponse
    {
        $spreadsheet = $this->spreadsheet();
        $fileName = 'product-variants-export-'.now()->format('Y-m-d-His').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function spreadsheet(): Spreadsheet
    {
        $variants = ProductVariant::query()
            ->with(['product', 'productColor', 'size'])
            ->latest('id')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('توافر القياسات');

        $rows = [[
            'رمز المنتج',
            'وصف المنتج',
            'رمز اللون',
            'اسم اللون بالعربي',
            'اسم اللون بالانكليزي',
            'HEX',
            'رمز القياس',
            'اسم القياس بالعربي',
            'اسم القياس بالانكليزي',
            'سعر البيع',
            'السعر قبل الحسم',
            'الكمية',
            'الحالة',
        ]];

        foreach ($variants as $variant) {
            $rows[] = [
                $variant->product?->model_no ?: '',
                $variant->product?->title_ar ?: '',
                $variant->productColor?->color_code ?: '',
                $variant->productColor?->color_name_ar ?: '',
                $variant->productColor?->color_name_en ?: '',
                $variant->productColor?->color_hex ?: '',
                $variant->size?->code ?: '',
                $variant->size?->name_ar ?: '',
                $variant->size?->name_en ?: '',
                $variant->price ?? '',
                $variant->compare_price ?? '',
                $variant->quantity ?? '',
                $variant->status === 'active' ? 'فعال' : 'غير فعال',
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
