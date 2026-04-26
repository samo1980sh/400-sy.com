<?php

namespace App\Services;

use App\Models\MeasurementChart;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;

class MeasurementChartImportService
{
    /**
     * @return array{created:int, updated:int, skipped:int}
     */
    public function import(string $path): array
    {
        if (! is_file($path)) {
            throw new RuntimeException('Measurement chart source file not found.');
        }

        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $this->sheetRows($sheet);

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            $sizeCode = trim((string) ($row['size_code'] ?? ''));

            if ($name === '' || $sizeCode === '') {
                $skipped++;
                continue;
            }

            $payload = [
                'chest' => $this->decimal($row['chest'] ?? null),
                'shoulder' => $this->decimal($row['shoulder'] ?? null),
                'waist' => $this->decimal($row['waist'] ?? null),
                'length' => $this->decimal($row['length'] ?? null),
                'sleeve' => $this->decimal($row['sleeve'] ?? null),
                'collar' => $this->decimal($row['collar'] ?? null),
                'inside_leg' => $this->decimal($row['inside_leg'] ?? null),
                'waistline' => $this->decimal($row['waistline'] ?? null),
                'thigh_width' => $this->decimal($row['thigh_width'] ?? null),
                'leg_width' => $this->decimal($row['leg_width'] ?? null),
                'leg_length' => $this->decimal($row['leg_length'] ?? null),
            ];

            $chart = MeasurementChart::query()->updateOrCreate(
                ['name' => $name, 'size_code' => $sizeCode],
                $payload,
            );

            if ($chart->wasRecentlyCreated) {
                $created++;
            } else {
                $updated++;
            }
        }

        return compact('created', 'updated', 'skipped');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function sheetRows(Worksheet $sheet): array
    {
        $rows = $sheet->toArray(null, false, false, false);
        $headerRow = array_shift($rows);

        if (! is_array($headerRow)) {
            throw new RuntimeException('Measurement chart file is missing headers.');
        }

        $headers = [];
        foreach ($headerRow as $index => $header) {
            $headers[$index] = $this->normalizeHeader((string) $header);
        }

        $mappedRows = [];

        foreach ($rows as $row) {
            $mapped = [];

            foreach ($row as $index => $value) {
                $header = $headers[$index] ?? null;

                if ($header === null || $header === '') {
                    continue;
                }

                $mapped[$header] = $value;
            }

            $mappedRows[] = [
                'name' => $this->value($mapped, ['name', 'الاسم', 'الإسم']),
                'size_code' => $this->value($mapped, ['size_code', 'القياس']),
                'chest' => $this->value($mapped, ['chest', 'الصدر']),
                'shoulder' => $this->value($mapped, ['shoulder', 'الكتف']),
                'waist' => $this->value($mapped, ['waist', 'الوسط']),
                'length' => $this->value($mapped, ['length', 'الطول']),
                'sleeve' => $this->value($mapped, ['sleeve', 'الكم']),
                'collar' => $this->value($mapped, ['collar', 'الياقة']),
                'inside_leg' => $this->value($mapped, ['inside_leg', 'وسطالرجل']),
                'waistline' => $this->value($mapped, ['waistline', 'الخاصرة']),
                'thigh_width' => $this->value($mapped, ['thigh_width', 'عرضالفخذ']),
                'leg_width' => $this->value($mapped, ['leg_width', 'عرضالرجل']),
                'leg_length' => $this->value($mapped, ['leg_length', 'طولالرجل']),
            ];
        }

        return $mappedRows;
    }

    protected function value(array $row, array $keys): mixed
    {
        foreach ($keys as $key) {
            $normalized = $this->normalizeHeader((string) $key);

            if (array_key_exists($normalized, $row)) {
                return $row[$normalized];
            }
        }

        return null;
    }

    protected function decimal(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return (float) str_replace(',', '.', $value);
    }

    protected function normalizeHeader(string $value): string
    {
        $value = trim(mb_strtolower($value));
        $value = str_replace(['إ', 'أ', 'آ', 'ا', 'ى', 'ؤ', 'ئ', 'ة', 'ـ'], ['ا', 'ا', 'ا', 'ا', 'ي', 'و', 'ي', 'ه', ''], $value);
        $value = preg_replace('/\s+/u', '', $value) ?? $value;
        $value = preg_replace('/[^\p{L}\p{N}_]/u', '', $value) ?? $value;

        return $value;
    }
}
