<?php

namespace App\Services;

use App\Models\Product;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductCatalogExportService
{
    public function download(): StreamedResponse
    {
        $spreadsheet = $this->spreadsheet();
        $fileName = 'products-catalog-export-'.now()->format('Y-m-d-His').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function spreadsheet(): Spreadsheet
    {
        $products = Product::query()
            ->with([
                'category.parent.parent.parent.parent',
                'productColors',
                'details',
                'complements.relatedProduct',
            ])
            ->orderBy('model_no')
            ->get();

        $spreadsheet = new Spreadsheet();

        $productsSheet = $spreadsheet->getActiveSheet();
        $productsSheet->setTitle('المنتجات');
        $this->buildProductsSheet($productsSheet, $products);

        $colorsSheet = $spreadsheet->createSheet();
        $colorsSheet->setTitle('ألوان المنتج');
        $this->buildProductColorsSheet($colorsSheet, $products);

        $detailsSheet = $spreadsheet->createSheet();
        $detailsSheet->setTitle('تفاصيل المنتج');
        $this->buildProductDetailsSheet($detailsSheet, $products);

        $complementsSheet = $spreadsheet->createSheet();
        $complementsSheet->setTitle('المنتجات المكملة');
        $this->buildProductComplementsSheet($complementsSheet, $products);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    /**
     * @param \Illuminate\Support\Collection<int, Product> $products
     */
    protected function buildProductsSheet(Worksheet $sheet, $products): void
    {
        $rows = [[
            'الكود',
            'الاسم بالعربي',
            'الاسم بالانكليزي',
            'التصنيف',
            'السعر',
            'السعر قبل الحسم',
            'التركيب',
            'التشكيلة',
            'Body Fit',
            'Drop',
            'زمرة القياس',
            'جديد',
            'عرض خاص',
            'الأكثر مبيعاً',
            'يظهر على الموقع',
            'يظهر على التطبيق',
            'يظهر للزبون',
            'يظهر للتاجر',
            'الحالة',
        ]];

        foreach ($products as $product) {
            $rows[] = [
                $product->model_no,
                $product->title_ar,
                $product->title_en,
                $this->categoryLabel($product),
                $product->price,
                $product->compare_price,
                $product->structure,
                $product->collection,
                $product->body_fit,
                $product->drop_type,
                $product->measurement_group,
                $this->yesNo($product->is_new),
                $this->yesNo($product->is_special_offer),
                $this->yesNo($product->is_best_seller),
                $this->yesNo($product->show_web),
                $this->yesNo($product->show_app),
                $this->yesNo($product->show_retail),
                $this->yesNo($product->show_wholesale),
                $product->is_active ? 'فعال' : 'معطل',
            ];
        }

        $this->fillSheet($sheet, $rows);
    }

    /**
     * @param \Illuminate\Support\Collection<int, Product> $products
     */
    protected function buildProductColorsSheet(Worksheet $sheet, $products): void
    {
        $rows = [[
            'رمز المنتج',
            'رمز اللون',
            'اسم اللون بالعربي',
            'اسم اللون بالانكليزي',
            'HEX',
            'الترتيب',
            'الحالة',
        ]];

        foreach ($products as $product) {
            foreach ($product->productColors as $color) {
                $rows[] = [
                    $product->model_no,
                    $color->color_code,
                    $color->color_name_ar,
                    $color->color_name_en,
                    $color->color_hex,
                    $color->sort_order,
                    $color->status === 'active' ? 'فعال' : 'غير فعال',
                ];
            }
        }

        $this->fillSheet($sheet, $rows);
    }

    /**
     * @param \Illuminate\Support\Collection<int, Product> $products
     */
    protected function buildProductDetailsSheet(Worksheet $sheet, $products): void
    {
        $rows = [[
            'رمز المنتج',
            'العنوان بالعربي',
            'القيمة بالعربي',
            'العنوان بالانكليزي',
            'القيمة بالانكليزي',
            'الترتيب',
            'فعال',
        ]];

        foreach ($products as $product) {
            foreach ($product->details as $detail) {
                $rows[] = [
                    $product->model_no,
                    $detail->label_ar,
                    $detail->value_ar,
                    $detail->label_en,
                    $detail->value_en,
                    $detail->sort_order,
                    $this->yesNo($detail->is_active),
                ];
            }
        }

        $this->fillSheet($sheet, $rows);
    }

    /**
     * @param \Illuminate\Support\Collection<int, Product> $products
     */
    protected function buildProductComplementsSheet(Worksheet $sheet, $products): void
    {
        $rows = [[
            'رمز المنتج',
            'رمز المنتج المكمل',
            'الترتيب',
        ]];

        foreach ($products as $product) {
            foreach ($product->complements as $complement) {
                $rows[] = [
                    $product->model_no,
                    $complement->relatedProduct?->model_no,
                    $complement->sort_order,
                ];
            }
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

    protected function yesNo(bool $value): string
    {
        return $value ? 'نعم' : 'لا';
    }

    protected function categoryLabel(Product $product): string
    {
        $category = $product->category;

        if (! $category) {
            return '';
        }

        $trail = $category->breadcrumbTrail()
            ->pluck('title_ar')
            ->filter()
            ->values()
            ->all();

        return $trail !== [] ? implode(' > ', $trail) : (string) $category->title_ar;
    }
}
