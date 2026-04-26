<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductWholesaleAvailability;
use App\Models\ProductWholesaleColor;
use App\Models\ProductWholesaleGroupAssignment;
use App\Models\WholesaleCustomerGroup;

class ProductWholesaleAvailabilityImportService
{
    public function __construct(
        protected RetailExcelImportService $helper,
    ) {
    }

    /**
     * @return array{created:int, updated:int, skipped:int, skipped_non_wholesale:int, skipped_missing_data:int}
     */
    public function import(string $path): array
    {
        $rows = $this->helper->readRows($path);

        $products = Product::query()
            ->where('show_wholesale', true)
            ->get(['id', 'model_no'])
            ->keyBy(fn (Product $product): string => $this->helper->normalizeText((string) $product->model_no));

        $wholesaleColors = ProductWholesaleColor::query()
            ->get(['id', 'product_id', 'color_code', 'color_name_ar', 'color_name_en'])
            ->groupBy('product_id');
        $groupCache = [];

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $skippedNonWholesale = 0;
        $skippedMissingData = 0;

        foreach ($rows as $row) {
            $productCode = $this->helper->normalizeText($this->value($row, 'كود المنتح', 'الكود', 'الرمز'));
            $groupName = $this->helper->normalizeText($this->value($row, 'اسم الفئة'));

            if ($productCode === '' || $groupName === '') {
                $skipped++;
                $skippedMissingData++;
                continue;
            }

            if (! isset($products[$productCode])) {
                $skipped++;
                $skippedNonWholesale++;
                continue;
            }

            $product = $products[$productCode];
            $group = $groupCache[$groupName] ??= WholesaleCustomerGroup::query()->firstOrCreate(
                ['name_ar' => $groupName],
                ['status' => 'active', 'sort_order' => 0]
            );

            ProductWholesaleGroupAssignment::query()->firstOrCreate([
                'product_id' => $product->id,
                'wholesale_customer_group_id' => $group->id,
            ]);

            $productColorsForProduct = $wholesaleColors->get($product->id, collect());
            $productColor = $this->resolveOrCreateWholesaleColor($product->id, $productColorsForProduct, $row);

            if (! $productColor) {
                $skipped++;
                continue;
            }

            $maxQuantity = $this->toInteger($this->value($row, 'الكمية العظمى'));

            $availability = ProductWholesaleAvailability::query()->updateOrCreate(
                [
                    'product_id' => $product->id,
                    'product_wholesale_color_id' => $productColor->id,
                    'wholesale_customer_group_id' => $group->id,
                ],
                [
                    'max_quantity' => $maxQuantity,
                ],
            );

            $wholesaleColors[$product->id] = $productColorsForProduct->contains('id', $productColor->id)
                ? $productColorsForProduct
                : $productColorsForProduct->concat(collect([$productColor]))->values();

            if ($availability->wasRecentlyCreated) {
                $created++;
            } else {
                $updated++;
            }
        }

        return compact('created', 'updated', 'skipped', 'skippedNonWholesale', 'skippedMissingData');
    }

    protected function resolveOrCreateWholesaleColor(int $productId, $colors, array $row): ?ProductWholesaleColor
    {
        $colorCode = $this->helper->normalizeText($this->value($row, 'رمز اللون'));
        $colorNameAr = $this->helper->normalizeColor($this->value($row, 'اللون بالعربي', 'اللون'));
        $colorNameEn = $this->helper->normalizeText($this->value($row, 'اللون بالإنكليزي', 'اللون بالانكليزي'));

        if ($colorCode !== '') {
            $match = $colors->first(fn (ProductWholesaleColor $color): bool => $this->helper->normalizeColorKey((string) $color->color_code) === $this->helper->normalizeColorKey($colorCode));

            if ($match) {
                return $match;
            }
        }

        if ($colorNameAr !== '' || $colorNameEn !== '') {
            $normalizedAr = $this->helper->normalizeColorKey($colorNameAr);
            $normalizedEn = $this->helper->normalizeColorKey($colorNameEn);

            $match = $colors->first(function (ProductWholesaleColor $color) use ($normalizedAr, $normalizedEn): bool {
                return ($normalizedAr !== '' && $this->helper->normalizeColorKey((string) $color->color_name_ar) === $normalizedAr)
                    || ($normalizedEn !== '' && $this->helper->normalizeColorKey((string) $color->color_name_en) === $normalizedEn);
            });

            if ($match) {
                return $match;
            }
        }

        if ($colorCode === '' && $colorNameAr === '' && $colorNameEn === '') {
            return null;
        }

        $lookupCode = $colorCode !== '' ? $colorCode : md5($colorNameAr . '|' . $colorNameEn);

        return ProductWholesaleColor::query()->create([
            'product_id' => $productId,
            'color_code' => $lookupCode,
            'color_name_ar' => $colorNameAr !== '' ? $colorNameAr : null,
            'color_name_en' => $colorNameEn !== '' ? $colorNameEn : null,
        ]);
    }

    protected function toInteger(?string $value): int
    {
        $value = trim((string) $value);

        if ($value === '' || ! is_numeric($value)) {
            return 0;
        }

        return (int) $value;
    }

    protected function value(array $row, string ...$keys): string
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row)) {
                return trim((string) $row[$key]);
            }
        }

        return '';
    }
}
