<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ProductVariant;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductVariantExportService
{
    /**
     * Download Product Variants using the same column structure accepted by
     * ProductVariantImportService. This makes the file suitable for:
     * Export -> edit -> Import.
     */
    public function download(): StreamedResponse
    {
        $spreadsheet = $this->spreadsheet();
        $fileName = 'product-variants-import-format-'.now()->format('Y-m-d-His').'.xlsx';

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
            ->get()
            ->sortBy([
                fn (ProductVariant $a, ProductVariant $b): int => strcmp((string) $a->product?->model_no, (string) $b->product?->model_no),
                fn (ProductVariant $a, ProductVariant $b): int => strcmp((string) $a->productColor?->color_code, (string) $b->productColor?->color_code),
                fn (ProductVariant $a, ProductVariant $b): int => strcmp((string) $a->size?->code, (string) $b->size?->code),
            ])
            ->values();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('توافر القياسات');

        // These headers intentionally match ProductVariantImportService:
        // الرمز / رمز اللون / اللون / القياس / بيع / كرت / الكمية / إيقاف
        $rows = [[
            'الرمز',
            'رمز اللون',
            'اللون',
            'القياس',
            'بيع',
            'كرت',
            'الكمية',
            'إيقاف',
        ]];

        foreach ($variants as $variant) {
            $rows[] = [
                $variant->product?->model_no ?: '',
                $variant->productColor?->color_code ?: '',
                $variant->productColor?->color_name_ar ?: '',
                $variant->size?->code ?: ($variant->size?->name_ar ?: ''),
                $this->decimalValue($variant->price),
                $this->decimalValue($variant->compare_price),
                $variant->quantity ?? 0,
                $variant->status === 'inactive' ? 'نعم' : 'لا',
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

    protected function decimalValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $value = (float) $value;

        return rtrim(rtrim(number_format($value, 4, '.', ''), '0'), '.');
    }
}
