<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ComplementaryProductsExportService
{
    public function download(): StreamedResponse
    {
        $spreadsheet = $this->spreadsheet();

        $filename = 'complementary-products-export-' . now()->format('Y-m-d-His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function spreadsheet(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('المنتجات المكملة');

        $products = Product::query()
            ->with([
                'complements' => fn ($query) => $query->with('relatedProduct')->orderBy('sort_order'),
            ])
            ->whereHas('complements')
            ->orderBy('model_no')
            ->get();

        $maxComplements = max(
            5,
            (int) $products->map(fn (Product $product): int => $product->complements->count())->max()
        );

        $headers = ['الكود'];

        for ($i = 1; $i <= $maxComplements; $i++) {
            $headers[] = 'Model Code Related ' . $i;
        }

        $rows = [$headers];

        foreach ($products as $product) {
            $row = [(string) ($product->model_no ?? '')];

            $relatedCodes = $product->complements
                ->map(fn ($complement): string => (string) ($complement->relatedProduct?->model_no ?? ''))
                ->filter()
                ->values();

            for ($i = 0; $i < $maxComplements; $i++) {
                $row[] = $relatedCodes[$i] ?? '';
            }

            $rows[] = $row;
        }

        $sheet->fromArray($rows, null, 'A1');

        $highestColumn = $sheet->getHighestColumn();

        $sheet->getStyle('A1:' . $highestColumn . '1')->getFont()->setBold(true);
        $sheet->freezePane('A2');

        foreach (range('A', $highestColumn) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        return $spreadsheet;
    }
}
