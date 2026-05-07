<?php

namespace App\Services;

use App\Models\WarehouseHall;
use App\Models\WarehouseInventoryBalance;
use App\Models\WarehouseInventoryItem;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WarehouseExportService
{
    public function download(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $this->fillImportCompatibleInventorySheet($sheet);

        $spreadsheet->setActiveSheetIndex(0);

        $filename = 'warehouse-import-format-export-' . now()->format('Y-m-d-His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function fillImportCompatibleInventorySheet(Worksheet $sheet): void
    {
        $sheet->setTitle('مخزون المستودع');

        $halls = WarehouseHall::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);

        $headers = array_merge($this->baseImportHeaders(), $halls->pluck('name')->all());

        $this->writeHeaders($sheet, $headers);

        $row = 2;

        WarehouseInventoryItem::query()
            ->orderBy('model_code')
            ->orderBy('short_code')
            ->chunk(500, function (EloquentCollection $items) use ($sheet, $halls, &$row): void {
                $balanceLookup = $this->balanceLookup($items->pluck('id')->all());

                foreach ($items as $item) {
                    $values = [
                        $item->country,
                        $item->model_code,
                        $item->barcode,
                        $item->short_code,
                        $item->item_name,
                        $item->size_code,
                        $item->color_name,
                        $item->color_code,
                        $this->numberValue($item->card_price),
                        $this->numberValue($item->discount_rate),
                        $this->numberValue($item->sale_price),
                        $this->numberValue($item->warehouse_stock),
                    ];

                    foreach ($halls as $hall) {
                        $quantity = $balanceLookup[$item->id][$hall->id] ?? null;
                        $values[] = $this->numberValue($quantity);
                    }

                    $sheet->fromArray($values, null, 'A' . $row++);
                }
            });

        $this->finishSheet($sheet, count($headers));
    }

    /**
     * هذه الأعمدة مطابقة لصيغة الاستيراد الحالية في WarehouseExcelImportService.
     * أول 12 عموداً هي بيانات الصنف، وما بعدها أسماء الصالات وتُقرأ ككميات.
     *
     * @return array<int, string>
     */
    private function baseImportHeaders(): array
    {
        return [
            'البلد',
            'رمز موديل',
            'باركود',
            'كود مختصر',
            'اسم الصنف',
            'قياس',
            'اللون',
            'رقم اللون',
            'سعر كرت',
            'نسبة تنزيلات',
            'سعر البيع',
            'مخزن',
        ];
    }

    /**
     * @param array<int, int|string> $itemIds
     * @return array<int|string, array<int|string, float|int|string|null>>
     */
    private function balanceLookup(array $itemIds): array
    {
        if ($itemIds === []) {
            return [];
        }

        $lookup = [];

        WarehouseInventoryBalance::query()
            ->whereIn('warehouse_inventory_item_id', $itemIds)
            ->get(['warehouse_inventory_item_id', 'warehouse_hall_id', 'quantity'])
            ->each(function (WarehouseInventoryBalance $balance) use (&$lookup): void {
                $lookup[$balance->warehouse_inventory_item_id][$balance->warehouse_hall_id] = $balance->quantity;
            });

        return $lookup;
    }

    /**
     * @param array<int, string> $headers
     */
    private function writeHeaders(Worksheet $sheet, array $headers): void
    {
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:' . $this->columnLetter(count($headers)) . '1')->getFont()->setBold(true);
        $sheet->freezePane('A2');
    }

    private function finishSheet(Worksheet $sheet, int $columnCount): void
    {
        for ($column = 1; $column <= $columnCount; $column++) {
            $sheet->getColumnDimension($this->columnLetter($column))->setAutoSize(true);
        }
    }

    private function columnLetter(int $columnNumber): string
    {
        $letter = '';

        while ($columnNumber > 0) {
            $modulo = ($columnNumber - 1) % 26;
            $letter = chr(65 + $modulo) . $letter;
            $columnNumber = intdiv($columnNumber - $modulo, 26);
        }

        return $letter;
    }

    private function numberValue(mixed $value): string|float|int
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        return (string) $value;
    }
}
