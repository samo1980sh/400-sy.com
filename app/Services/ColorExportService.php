<?php

namespace App\Services;

use App\Models\Color;
use App\Models\ProductColor;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ColorExportService
{
    public function download(): StreamedResponse
    {
        $spreadsheet = $this->spreadsheet();
        $fileName = 'colors-export-'.now()->format('Y-m-d-His').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function spreadsheet(): Spreadsheet
    {
        $colors = Color::query()
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get();

        $productColors = ProductColor::query()
            ->with('product')
            ->latest('id')
            ->get();

        $spreadsheet = new Spreadsheet();

        $generalColorsSheet = $spreadsheet->getActiveSheet();
        $generalColorsSheet->setTitle('الألوان العامة');
        $this->buildGeneralColorsSheet($generalColorsSheet, $colors);

        $productColorsSheet = $spreadsheet->createSheet();
        $productColorsSheet->setTitle('ألوان المنتجات');
        $this->buildProductColorsSheet($productColorsSheet, $productColors);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    protected function buildGeneralColorsSheet(Worksheet $sheet, $colors): void
    {
        $rows = [[
            'الرمز العام',
            'الاسم بالعربي',
            'الاسم بالانكليزي',
            'HEX',
            'الصورة',
            'الترتيب',
            'الحالة',
        ]];

        foreach ($colors as $color) {
            $rows[] = [
                $color->code,
                $color->name_ar,
                $color->name_en,
                $color->hex,
                $color->image,
                $color->sort_order,
                $this->formatStatus($color->status),
            ];
        }

        $this->fillSheet($sheet, $rows);
    }

    protected function buildProductColorsSheet(Worksheet $sheet, $productColors): void
    {
        $rows = [[
            'رمز المنتج',
            'اسم المنتج بالعربي',
            'رمز اللون داخل المنتج',
            'اسم اللون بالعربي',
            'اسم اللون بالانكليزي',
            'HEX',
            'الترتيب',
            'الحالة',
        ]];

        foreach ($productColors as $productColor) {
            $rows[] = [
                $productColor->product?->model_no ?: '',
                $productColor->product?->title_ar ?: '',
                $productColor->color_code,
                $productColor->color_name_ar,
                $productColor->color_name_en,
                $productColor->color_hex,
                $productColor->sort_order,
                $this->formatStatus($productColor->status),
            ];
        }

        $this->fillSheet($sheet, $rows);
    }

    protected function formatStatus(?string $status): string
    {
        return match ($status) {
            'active' => 'فعال',
            'inactive' => 'غير فعال',
            null, '' => '',
            default => (string) $status,
        };
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
