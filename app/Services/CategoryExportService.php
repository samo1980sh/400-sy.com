<?php

namespace App\Services;

use App\Models\Category;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CategoryExportService
{
    public function download(): StreamedResponse
    {
        $spreadsheet = $this->spreadsheet();
        $fileName = 'categories-export-'.now()->format('Y-m-d-His').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function spreadsheet(): Spreadsheet
    {
        $categories = Category::query()
            ->with(['parent'])
            ->withCount('children')
            ->orderByRaw('parent_id is not null')
            ->orderBy('parent_id')
            ->orderBy('sort_order')
            ->orderBy('title_ar')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('التصنيفات');

        $rows = [[
            'رقم التصنيف',
            'التصنيف الأب',
            'الاسم بالعربي',
            'الاسم بالانكليزي',
            'الرابط / Slug',
            'صورة بطاقة التصنيف',
            'بانر التصنيف',
            'عدد الأبناء',
            'الترتيب',
            'الحالة',
            'تاريخ الإنشاء',
        ]];

        foreach ($categories as $category) {
            $rows[] = [
                $category->id,
                $category->parent?->title_ar ?: '',
                $category->title_ar ?: '',
                $category->title_en ?: '',
                $category->slug ?: '',
                $category->image ?: '',
                $category->banner ?: '',
                $category->children_count ?? 0,
                $category->sort_order ?? '',
                '',
                $category->created_at?->format('Y-m-d H:i') ?: '',
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
