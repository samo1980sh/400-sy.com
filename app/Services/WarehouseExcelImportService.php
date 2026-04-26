<?php

namespace App\Services;

use App\Models\WarehouseHall;
use App\Models\WarehouseInventoryBalance;
use App\Models\WarehouseInventoryItem;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;

class WarehouseExcelImportService
{
    /**
     * @return array{halls_created:int,halls_updated:int,items_created:int,items_updated:int,balances_created:int,balances_updated:int,skipped:int}
     */
    public function import(string $path): array
    {
        if (! is_file($path)) {
            throw new RuntimeException('Warehouse source file not found.');
        }

        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, false, false, false);

        if ($rows === [] || ! is_array($rows[0] ?? null)) {
            throw new RuntimeException('Warehouse source file is empty.');
        }

        $headers = array_map(fn ($value) => $this->normalizeHeader((string) $value), array_values(array_shift($rows)));
        $hallHeaders = array_slice($headers, 12);

        $hallLookup = [];
        $hallsCreated = 0;
        $hallsUpdated = 0;

        foreach ($hallHeaders as $hallName) {
            if ($hallName === '') {
                continue;
            }

            $hall = WarehouseHall::query()->updateOrCreate(
                ['name' => $hallName],
                [
                    'code' => $this->slugCode($hallName),
                    'status' => 'active',
                ]
            );

            $hallLookup[$hallName] = $hall;

            if ($hall->wasRecentlyCreated) {
                $hallsCreated++;
            } else {
                $hallsUpdated++;
            }
        }

        $itemsCreated = 0;
        $itemsUpdated = 0;
        $balancesCreated = 0;
        $balancesUpdated = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $mapped = [];

            foreach ($row as $index => $value) {
                $header = $headers[$index] ?? '';

                if ($header === '') {
                    continue;
                }

                $mapped[$header] = $value;
            }

            $shortCode = $this->text($mapped['كودمختصر'] ?? null);

            if ($shortCode === '') {
                $skipped++;
                continue;
            }

            $item = WarehouseInventoryItem::query()->updateOrCreate(
                ['short_code' => $shortCode],
                [
                    'country' => $this->text($mapped['البلد'] ?? null) ?: null,
                    'model_code' => $this->text($mapped['رمزموديل'] ?? null) ?: null,
                    'barcode' => $this->text($mapped['باركود'] ?? null) ?: null,
                    'item_name' => $this->text($mapped['اسمالصنف'] ?? null) ?: null,
                    'size_code' => $this->text($mapped['قياس'] ?? null) ?: null,
                    'color_name' => $this->text($mapped['اللون'] ?? null) ?: null,
                    'color_code' => $this->text($mapped['رقماللون'] ?? null) ?: null,
                    'card_price' => $this->decimal($mapped['سعركرت'] ?? null) ?? 0,
                    'discount_rate' => $this->decimal($mapped['نسبةتنزيلات'] ?? null) ?? 0,
                    'sale_price' => $this->decimal($mapped['سعرالبيع'] ?? null) ?? 0,
                    'warehouse_stock' => $this->decimal($mapped['مخزن'] ?? null) ?? 0,
                ]
            );

            if ($item->wasRecentlyCreated) {
                $itemsCreated++;
            } else {
                $itemsUpdated++;
            }

            $seenHallIds = [];

            foreach ($hallHeaders as $hallHeader) {
                if ($hallHeader === '') {
                    continue;
                }

                $quantity = $this->decimal($mapped[$hallHeader] ?? null);

                if ($quantity === null || (float) $quantity === 0.0) {
                    continue;
                }

                $hall = $hallLookup[$hallHeader] ?? null;

                if (! $hall) {
                    continue;
                }

                $seenHallIds[] = $hall->id;

                $balance = WarehouseInventoryBalance::query()->updateOrCreate(
                    [
                        'warehouse_inventory_item_id' => $item->id,
                        'warehouse_hall_id' => $hall->id,
                    ],
                    [
                        'quantity' => $quantity,
                    ]
                );

                if ($balance->wasRecentlyCreated) {
                    $balancesCreated++;
                } else {
                    $balancesUpdated++;
                }
            }

            $balanceQuery = WarehouseInventoryBalance::query()
                ->where('warehouse_inventory_item_id', $item->id);

            if ($seenHallIds === []) {
                $balanceQuery->delete();
            } else {
                $balanceQuery
                    ->whereNotIn('warehouse_hall_id', array_values(array_unique($seenHallIds)))
                    ->delete();
            }
        }

        return [
            'halls_created' => $hallsCreated,
            'halls_updated' => $hallsUpdated,
            'items_created' => $itemsCreated,
            'items_updated' => $itemsUpdated,
            'balances_created' => $balancesCreated,
            'balances_updated' => $balancesUpdated,
            'skipped' => $skipped,
        ];
    }

    protected function text(mixed $value): string
    {
        return trim((string) $value);
    }

    protected function decimal(mixed $value): ?float
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return (float) str_replace(',', '.', $value);
    }

    protected function normalizeHeader(string $value): string
    {
        $value = trim(mb_strtolower($value));
        $value = preg_replace('/\s+/u', '', $value) ?? $value;
        $value = preg_replace('/[^\p{L}\p{N}_]/u', '', $value) ?? $value;

        return $value;
    }

    protected function slugCode(string $value): string
    {
        $value = preg_replace('/\s+/u', '-', trim($value)) ?? $value;
        $value = preg_replace('/[^\p{L}\p{N}\-]+/u', '', $value) ?? $value;

        return mb_strtolower($value);
    }
}
