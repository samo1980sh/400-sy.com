<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductColor;
use App\Models\ProductVariant;
use App\Models\Size;

class ProductVariantImportService
{
    public function __construct(
        protected RetailExcelImportService $helper,
    ) {
    }

    /**
     * @return array{created:int, updated:int, skipped:int}
     */
    public function import(string $path): array
    {
        $rows = $this->helper->readRows($path);

        $products = Product::query()
            ->get(['id', 'model_no', 'price', 'compare_price', 'currency_ar'])
            ->keyBy(fn (Product $product): string => $this->helper->normalizeText((string) $product->model_no));

        $productColorIndex = ProductColor::query()
            ->get(['id', 'product_id', 'color_code', 'color_name_ar', 'color_name_en'])
            ->groupBy('product_id')
            ->map(function ($colors): array {
                $index = [];

                foreach ($colors as $color) {
                    $index[$this->helper->normalizeColorKey((string) $color->color_code)] = $color;
                    $index[$this->helper->normalizeColorKey((string) $color->color_name_ar)] = $color;
                    $index[$this->helper->normalizeColorKey((string) $color->color_name_en)] = $color;
                }

                return $index;
            })
            ->all();

        $sizeIndex = Size::query()
            ->get(['id', 'code', 'name_ar', 'name_en'])
            ->mapWithKeys(function (Size $size): array {
                return [
                    $this->helper->normalizeSizeKey($size->code) => $size->id,
                    $this->helper->normalizeSizeKey($size->name_ar) => $size->id,
                    $this->helper->normalizeSizeKey($size->name_en) => $size->id,
                ];
            })
            ->all();

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $productCode = $this->helper->normalizeText($this->value($row, 'الرمز', 'الكود', 'model_no'));
            $colorCode = $this->helper->normalizeColorKey($this->value($row, 'رمز اللون', 'color_code'));
            $colorName = $this->helper->normalizeColor($this->value($row, 'اللون', 'name_ar'));
            $sizeCode = $this->helper->normalizeSize($this->value($row, 'القياس', 'size_code'));

            if ($productCode === '' || ! isset($products[$productCode])) {
                $skipped++;
                continue;
            }

            $product = $products[$productCode];
            $productColor = $this->resolveProductColor($productColorIndex[$product->id] ?? [], $colorCode, $colorName);
            $sizeId = $sizeIndex[$this->helper->normalizeSizeKey($sizeCode)] ?? null;

            if (! $productColor || ! $sizeId) {
                $skipped++;
                continue;
            }

            $price = $this->numeric($this->value(
                $row,
                'بيع',
                'price',
                'سعر القياس',
                'سعر القياس ',
            ));
            $comparePrice = $this->numeric($this->value(
                $row,
                'كرت',
                'compare_price',
                'سعر القياس الذي يضاف على سعر ما قبل التخفيضات',
                'السعر قبل الحسم',
            ));
            $quantity = (int) $this->numeric($this->value($row, 'الكمية', 'quantity'));
            $status = $this->helper->normalizeText($this->value($row, 'إيقاف', 'status')) === 'نعم' ? 'inactive' : 'active';
            $payload = [
                'product_id' => $product->id,
                'product_color_id' => $productColor->id,
                'size_id' => $sizeId,
                'price' => $price,
                'compare_price' => $comparePrice,
                'quantity' => $quantity,
                'is_default' => false,
                'status' => $status,
            ];

            $variant = ProductVariant::query()->updateOrCreate(
                [
                    'product_id' => $product->id,
                    'product_color_id' => $productColor->id,
                    'size_id' => $sizeId,
                ],
                $payload,
            );

            if ($variant->wasRecentlyCreated) {
                $created++;
            } else {
                $updated++;
            }
        }

        return compact('created', 'updated', 'skipped');
    }

    protected function resolveProductColor(array $productColors, string $colorCode, string $colorName): ?ProductColor
    {
        if ($colorCode !== '') {
            $match = $productColors[$colorCode] ?? null;
            if ($match instanceof ProductColor) {
                return $match;
            }
        }

        if ($colorName === '') {
            return null;
        }

        $normalizedColorName = $this->helper->normalizeColorKey($colorName);
        $match = $productColors[$normalizedColorName] ?? null;

        if ($match instanceof ProductColor) {
            return $match;
        }

        return null;
    }

    protected function numeric(mixed $value): float
    {
        if ($value === null) {
            return 0.0;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return 0.0;
        }

        return (float) str_replace(',', '.', $value);
    }

    protected function value(array $row, string ...$keys): string
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row)) {
                return trim((string) $row[$key]);
            }
        }

        foreach ($row as $rowKey => $value) {
            $normalizedRowKey = $this->normalizeHeader($rowKey);
            foreach ($keys as $key) {
                if ($normalizedRowKey === $this->normalizeHeader($key)) {
                    return trim((string) $value);
                }
            }
        }

        return '';
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
