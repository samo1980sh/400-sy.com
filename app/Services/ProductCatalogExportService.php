<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductColor;
use App\Models\ProductWholesaleQuantity;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductCatalogExportService
{
    public function download(): StreamedResponse
    {
        $spreadsheet = $this->spreadsheet();
        $fileName = 'products-import-format-export-' . now()->format('Y-m-d-His') . '.xlsx';

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
                'productColors.filterColor',
                'retailGroupAssignments.retailCustomerGroup',
                'wholesaleGroupAssignments.wholesaleCustomerGroup',
                'wholesaleQuantities.wholesaleColor',
            ])
            ->orderBy('model_no')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('المنتجات');

        $this->buildProductsImportSheet($sheet, $products);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    /**
     * @param Collection<int, Product> $products
     */
    protected function buildProductsImportSheet(Worksheet $sheet, Collection $products): void
    {
        $rows = [[
            'الكود',
            'المدخل الرئيسي',
            'المدخل الفرعي',
            'التصنيف',
            'الاسم بالعربي',
            'الاسم بالانكليزي',
            'السعر بعد الحسم',
            'السعر قبل الحسم',
            'اللون',
            'التشكيلة',
            'Body Fit',
            'Drop',
            'زمر وحدة القياس',
            'top',
            'الظهور',
            'تخصيص العرض',
            'شرح بالعربي 1',
            'شرح بالعربي 2',
            'شرح بالانكليزي 1',
            'شرح بالانكليزي 2',
            'اللون بالعربي',
            'اللون بالانكليزي',
            'رمز اللون',
            'Color HEX',
            'خاص',
            'القياس',
        ]];

        foreach ($products as $product) {
            $categoryTrail = $this->categoryTrail($product);
            $descriptionAr = $this->splitDescription($product->description_ar);
            $descriptionEn = $this->splitDescription($product->description_en);
            $visibility = $this->visibilityText($product);
            $customerGroups = $this->customerGroupsText($product);
            $top = $this->topText($product);
            $colors = $product->productColors;

            if ($colors->isEmpty()) {
                $rows[] = $this->productRow(
                    product: $product,
                    categoryTrail: $categoryTrail,
                    descriptionAr: $descriptionAr,
                    descriptionEn: $descriptionEn,
                    visibility: $visibility,
                    customerGroups: $customerGroups,
                    top: $top,
                    color: null,
                );

                continue;
            }

            foreach ($colors as $color) {
                $rows[] = $this->productRow(
                    product: $product,
                    categoryTrail: $categoryTrail,
                    descriptionAr: $descriptionAr,
                    descriptionEn: $descriptionEn,
                    visibility: $visibility,
                    customerGroups: $customerGroups,
                    top: $top,
                    color: $color,
                );
            }
        }

        $this->fillSheet($sheet, $rows);
    }

    /**
     * @param array<int, string> $categoryTrail
     * @param array<int, string> $descriptionAr
     * @param array<int, string> $descriptionEn
     */
    protected function productRow(
        Product $product,
        array $categoryTrail,
        array $descriptionAr,
        array $descriptionEn,
        string $visibility,
        string $customerGroups,
        string $top,
        ?ProductColor $color,
    ): array {
        return [
            $product->model_no,
            $categoryTrail[0] ?? '',
            count($categoryTrail) > 2 ? ($categoryTrail[1] ?? '') : '',
            $categoryTrail !== [] ? $categoryTrail[array_key_last($categoryTrail)] : '',
            $product->title_ar,
            $product->title_en,
            $this->decimalValue($product->price),
            $this->decimalValue($product->compare_price),
            $this->structureValueForExport($product, $color),
            $product->collection,
            $product->body_fit,
            $product->drop_type,
            $product->measurement_group,
            $top,
            $visibility,
            $customerGroups,
            $descriptionAr[0] ?? '',
            $descriptionAr[1] ?? '',
            $descriptionEn[0] ?? '',
            $descriptionEn[1] ?? '',
            $color?->color_name_ar,
            $color?->color_name_en,
            $color?->color_code,
            $color?->color_hex,
            $color ? ($color->status === 'active' ? 'تفعيل' : 'إيقاف') : '',
            $color ? $this->wholesaleSeriesSourceForColor($product, (string) $color->color_code) : '',
        ];
    }

    protected function structureValueForExport(Product $product, ?ProductColor $color): string
    {
        $filterColor = $color?->filterColor;

        if ($filterColor !== null) {
            foreach (['name_ar', 'name_en', 'code'] as $attribute) {
                $value = trim((string) ($filterColor->{$attribute} ?? ''));

                if ($value !== '') {
                    return $value;
                }
            }
        }

        return trim((string) ($product->structure ?? ''));
    }

    /**
     * @return array<int, string>
     */
    protected function categoryTrail(Product $product): array
    {
        $category = $product->category;

        if (! $category) {
            return [];
        }

        if (method_exists($category, 'breadcrumbTrail')) {
            return $category->breadcrumbTrail()
                ->pluck('title_ar')
                ->filter()
                ->values()
                ->all();
        }

        $trail = [];
        $current = $category;

        while ($current) {
            array_unshift($trail, (string) $current->title_ar);
            $current = $current->parent;
        }

        return array_values(array_filter($trail));
    }

    /**
     * @return array<int, string>
     */
    protected function splitDescription(?string $value): array
    {
        $parts = preg_split('/\r\n|\r|\n/u', (string) $value) ?: [];
        $parts = array_values(array_filter(array_map('trim', $parts)));

        return [
            $parts[0] ?? '',
            $parts[1] ?? '',
        ];
    }

    protected function visibilityText(Product $product): string
    {
        $items = [];

        if ($product->show_web) {
            $items[] = 'موقع';
        }

        if ($product->show_app) {
            $items[] = 'تطبيق';
        }

        if ($product->show_retail) {
            $items[] = 'زبون';
        }

        if ($product->show_wholesale) {
            $items[] = 'تاجر';
        }

        return implode(' - ', $items);
    }

    protected function customerGroupsText(Product $product): string
    {
        if ($product->show_wholesale && $product->relationLoaded('wholesaleGroupAssignments')) {
            $names = $product->wholesaleGroupAssignments
                ->map(fn ($assignment): ?string => $assignment->wholesaleCustomerGroup?->name_ar)
                ->filter()
                ->unique()
                ->values()
                ->all();

            if ($names !== []) {
                return implode('|', $names);
            }
        }

        if ($product->show_retail && $product->relationLoaded('retailGroupAssignments')) {
            $names = $product->retailGroupAssignments
                ->map(fn ($assignment): ?string => $assignment->retailCustomerGroup?->name)
                ->filter()
                ->unique()
                ->values()
                ->all();

            if ($names !== []) {
                return implode('|', $names);
            }
        }

        return '';
    }

    protected function topText(Product $product): string
    {
        if ($product->is_new) {
            return 'NEW';
        }

        if ($product->is_special_offer) {
            return 'Offer';
        }

        if ($product->is_best_seller) {
            return 'Trending Now';
        }

        return '';
    }

    protected function wholesaleSeriesSourceForColor(Product $product, string $colorCode): string
    {
        if (! $product->relationLoaded('wholesaleQuantities')) {
            return '';
        }

        $items = $product->wholesaleQuantities
            ->filter(function (ProductWholesaleQuantity $quantity) use ($colorCode): bool {
                $quantityColorCode = (string) ($quantity->wholesaleColor?->color_code ?? '');

                return $this->normalizedKey($quantityColorCode) === $this->normalizedKey($colorCode);
            })
            ->sortBy([
                ['series_group', 'asc'],
                ['id', 'asc'],
            ]);

        if ($items->isEmpty()) {
            return '';
        }

        $existingSource = (string) ($items->first(fn ($quantity): bool => filled($quantity->source_value))?->source_value ?? '');

        if ($existingSource !== '') {
            return $existingSource;
        }

        return $items
            ->groupBy('series_group')
            ->map(function (Collection $groupItems): string {
                return $groupItems
                    ->map(fn (ProductWholesaleQuantity $quantity): string => trim((string) $quantity->size_text) . ':' . (int) $quantity->quantity)
                    ->filter(fn (string $item): bool => $item !== ':0')
                    ->implode('|');
            })
            ->filter()
            ->implode('/');
    }

    protected function normalizedKey(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('/[\s\p{P}\p{S}]+/u', '', $value) ?? $value;

        return mb_strtoupper(strtr($value, [
            'أ' => 'ا',
            'إ' => 'ا',
            'آ' => 'ا',
            'ى' => 'ي',
            'ؤ' => 'و',
            'ئ' => 'ي',
            'ة' => 'ه',
        ]));
    }

    protected function decimalValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
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
