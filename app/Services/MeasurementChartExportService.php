<?php

namespace App\Services;

use App\Models\MeasurementChart;
use App\Models\MeasurementChartGroup;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MeasurementChartExportService
{
    public function download(): StreamedResponse
    {
        $spreadsheet = $this->spreadsheet();
        $fileName = 'measurement-charts-export-'.now()->format('Y-m-d-His').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function spreadsheet(): Spreadsheet
    {
        $groups = MeasurementChartGroup::query()
            ->withCount('charts')
            ->orderBy('name')
            ->get();

        $charts = MeasurementChart::query()
            ->with('group')
            ->orderBy('name')
            ->orderBy('size_code')
            ->get();

        $spreadsheet = new Spreadsheet();

        $groupsSheet = $spreadsheet->getActiveSheet();
        $groupsSheet->setTitle('مجموعات القياس');
        $this->buildGroupsSheet($groupsSheet, $groups);

        $chartsSheet = $spreadsheet->createSheet();
        $chartsSheet->setTitle('صفوف القياس');
        $this->buildChartsSheet($chartsSheet, $charts);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    protected function buildGroupsSheet(Worksheet $sheet, $groups): void
    {
        $rows = [[
            'اسم المجموعة',
            'صورة التوضيح',
            'عدد صفوف القياس',
        ]];

        foreach ($groups as $group) {
            $rows[] = [
                $group->name,
                $group->guide_image,
                $group->charts_count,
            ];
        }

        $this->fillSheet($sheet, $rows);
    }

    protected function buildChartsSheet(Worksheet $sheet, $charts): void
    {
        $rows = [[
            'اسم المجموعة',
            'رمز القياس',
            'الصدر',
            'الكتف',
            'الوسط',
            'الطول',
            'الكم',
            'الياقة',
            'طول الرجل الداخلي',
            'محيط الخصر',
            'عرض الفخذ',
            'عرض الرجل',
            'طول الرجل',
        ]];

        foreach ($charts as $chart) {
            $rows[] = [
                $chart->group?->name ?: $chart->name,
                $chart->size_code,
                $chart->chest,
                $chart->shoulder,
                $chart->waist,
                $chart->length,
                $chart->sleeve,
                $chart->collar,
                $chart->inside_leg,
                $chart->waistline,
                $chart->thigh_width,
                $chart->leg_width,
                $chart->leg_length,
            ];
        }

        $this->fillSheet($sheet, $rows);
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
