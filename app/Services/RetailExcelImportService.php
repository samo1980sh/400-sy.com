<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\ProductComplement;
use App\Models\ProductRetailGroupAssignment;
use App\Models\ProductWholesaleAvailability;
use App\Models\ProductWholesaleColor;
use App\Models\ProductWholesaleGroupAssignment;
use App\Models\ProductWholesaleQuantity;
use App\Models\ProductVariant;
use App\Models\RetailCustomerGroup;
use App\Models\Size;
use App\Models\WholesaleCustomerGroup;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class RetailExcelImportService
{
    protected ?array $cachedCategoryHierarchyIndex = null;

    protected ?array $cachedColorCodeMap = null;

    protected ?array $cachedColorNameMap = null;

    protected ?array $cachedSizeMap = null;

    protected ?array $cachedColorDictionary = null;

    public function readRows(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $worksheet = $spreadsheet->getSheet(0);
        $rows = $worksheet->toArray(null, true, true, true);
        $headers = [];
        $result = [];

        foreach ($rows as $index => $row) {
            if ($index === 1) {
                $headers = array_values($row);
                continue;
            }

            $item = [];
            foreach ($headers as $i => $header) {
                $column = $this->excelColumn($i + 1);
                $item[(string) $header] = isset($row[$column]) ? trim((string) $row[$column]) : '';
            }

            $result[] = $item;
        }

        return $result;
    }

    private function value(array $row, string ...$keys): string
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row)) {
                return trim((string) $row[$key]);
            }
        }

        return '';
    }

    public function normalizeText(?string $value): string
    {
        $value = trim((string) $value);
        $value = preg_replace('/\s+/u', ' ', $value);

        return trim($value);
    }

    public function normalizeCategory(?string $value): string
    {
        $value = $this->normalizeText($value);

        return match ($value) {
            'بليزر ' => 'بليزر',
            'ببيون و فولار ' => 'ببيون و فولار',
            'ببيون و فولار' => 'ببيون وفولار',
            'بيون و فولار' => 'ببيون وفولار',
            'بنطال جينز ' => 'بنطال جينز',
            'جوارب شتوي' => 'جوارب شتوية',
            default => $value,
        };
    }

    public function normalizeCategoryKey(?string $value): string
    {
        $value = $this->normalizeCategory($value);
        $value = preg_replace('/[\s\p{P}\p{S}]+/u', '', $value) ?? $value;
        $value = strtr($value, [
            'أ' => 'ا',
            'إ' => 'ا',
            'آ' => 'ا',
            'ى' => 'ي',
            'ؤ' => 'و',
            'ئ' => 'ي',
            'ة' => 'ه',
        ]);

        return $value;
    }

    public function normalizeColor(?string $value): string
    {
        $value = $this->normalizeText($value);

        return match ($value) {
            'ابيض' => 'أبيض',
            'اسود' => 'أسود',
            'ازرق' => 'أزرق',
            'ازرق فاتح' => 'أزرق فاتح',
            'ازرق غامق' => 'أزرق غامق',
            default => $value,
        };
    }

    public function normalizeColorKey(?string $value): string
    {
        $value = $this->normalizeText($value);
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

    public function normalizeSize(?string $value): string
    {
        $value = $this->normalizeText($value);

        return match ($value) {
            'XXL' => '2XL',
            'Small' => 'S',
            'STD' => 'Free Size',
            'One Size' => 'Free Size',
            default => $value,
        };
    }

    public function normalizeSizeKey(?string $value): string
    {
        return $this->normalizeColorKey($this->normalizeSize($value));
    }

    public function mapTopFlag(?string $value): array
    {
        $value = $this->normalizeText($value);

        return [
            'is_new' => in_array($value, ['NEW', 'New Arrivals'], true),
            'is_special_offer' => $value === 'Offer',
            'is_best_seller' => $value === 'Trending Now',
        ];
    }

    public function mapVisibilityFlags(?string $value): array
    {
        $value = $this->normalizeText($value);
        $tokens = preg_split('/\s*-\s*/u', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $tokens = array_map(fn ($item) => $this->normalizeText($item), $tokens);

        return [
            'show_web' => in_array('موقع', $tokens, true),
            'show_app' => in_array('تطبيق', $tokens, true),
            'show_retail' => in_array('زبون', $tokens, true),
            'show_wholesale' => in_array('تاجر', $tokens, true),
            'visibility_targets' => $value !== '' ? $value : null,
            'display_channels' => implode(',', array_values(array_filter([
                in_array('موقع', $tokens, true) ? 'web' : null,
                in_array('تطبيق', $tokens, true) ? 'app' : null,
            ]))) ?: null,
        ];
    }

    /**
     * @return array<int, string>
     */
    public function parseCustomerGroupTokens(?string $value): array
    {
        $value = $this->normalizeText($value);

        if ($value === '') {
            return [];
        }

        $tokens = preg_split('/\s*\|\s*/u', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $result = [];

        foreach ($tokens as $token) {
            $token = $this->normalizeText($token);

            if ($token !== '' && ! in_array($token, $result, true)) {
                $result[] = $token;
            }
        }

        return $result;
    }

    /**
     * @param array<int, array<string, string>> $rows
     */
    public function resolveWholesaleSeriesSource(array $rows): string
    {
        foreach ($rows as $row) {
            $value = $this->normalizeText($this->value($row, 'القياس'));

            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    /**
     * @return array<int, array{series_group:int, size_text:string, quantity:int, source_value:string}>
     */
    public function parseWholesaleSeries(?string $value): array
    {
        $value = $this->normalizeText($value);

        if ($value === '') {
            return [];
        }

        $result = [];
        $groups = preg_split('/\s*\/\s*/u', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        foreach ($groups as $groupIndex => $groupValue) {
            $items = preg_split('/\s*\|\s*/u', $groupValue, -1, PREG_SPLIT_NO_EMPTY) ?: [];

            foreach ($items as $item) {
                $parts = array_map('trim', explode(':', (string) $item, 2));
                $sizeText = $parts[0] ?? '';
                $quantity = $parts[1] ?? '';

                if ($sizeText === '' || $quantity === '' || ! is_numeric($quantity)) {
                    continue;
                }

                $result[] = [
                    'series_group' => $groupIndex + 1,
                    'size_text' => $this->normalizeText($sizeText),
                    'quantity' => (int) $quantity,
                    'source_value' => $value,
                ];
            }
        }

        return $result;
    }

    /**
     * @return array<int, string>
     */
    public function parseComplementaryProductCodes(array $row): array
    {
        $codes = [];

        for ($index = 1; $index <= 5; $index++) {
            $value = $this->normalizeText($this->value(
                $row,
                "Model Code Related {$index}",
                "Related Model Code {$index}",
                "Model Related Code {$index}",
                "الكود المرتبط {$index}",
                "الرمز المرتبط {$index}",
                "رمز المنتج المكمل {$index}"
            ));

            if ($value === '') {
                continue;
            }

            $codes[] = $value;
        }

        return array_values(array_unique($codes));
    }

    /**
     * @param array<string, array<int, array<string, string>>> $productGroups
     * @return array<string, Product>
     */
    protected function buildComplementaryProductLookup(array $productGroups): array
    {
        $codes = [];

        foreach ($productGroups as $rows) {
            $codes = array_merge($codes, $this->parseComplementaryProductCodes($rows[0] ?? []));
        }

        $codes = array_values(array_unique(array_filter(array_map([$this, 'normalizeText'], $codes))));

        if ($codes === []) {
            return [];
        }

        return Product::query()
            ->whereIn('model_no', $codes)
            ->get()
            ->mapWithKeys(fn (Product $product): array => [
                $this->normalizeText($product->model_no) => $product,
            ])
            ->all();
    }

    public function acceptedProductCodes(array $productRows, int $batchSize = 50): array
    {
        $codes = [];

        foreach ($productRows as $row) {
            $code = $this->normalizeText($this->value($row, 'الكود'));
            if ($code === '') {
                continue;
            }

            if (! in_array($code, $codes, true)) {
                $codes[] = $code;
            }

            if (count($codes) >= $batchSize) {
                break;
            }
        }

        return $codes;
    }

    public function categoryMap(): array
    {
        return Category::query()
            ->get(['id', 'title_ar'])
            ->mapWithKeys(function (Category $category): array {
                return [$this->normalizeCategory($category->title_ar) => $category->id];
            })
            ->all();
    }

    public function categoryHierarchyIndex(): array
    {
        if ($this->cachedCategoryHierarchyIndex !== null) {
            return $this->cachedCategoryHierarchyIndex;
        }

        $categories = Category::query()->get(['id', 'parent_id', 'title_ar']);
        $childrenByParent = [];

        foreach ($categories as $category) {
            $childrenByParent[$category->parent_id ?? 0][] = $category;
        }

        $index = [];
        $build = function (Category $category, array $trail = []) use (&$build, &$index, $childrenByParent): void {
            $trail[] = $this->normalizeCategory($category->title_ar);

            if ($trail !== []) {
                $index[] = [
                    'id' => $category->id,
                    'trail' => $trail,
                    'keys' => array_map(fn ($item) => $this->normalizeCategoryKey($item), $trail),
                ];
            }

            foreach ($childrenByParent[$category->id] ?? [] as $child) {
                $build($child, $trail);
            }
        };

        foreach ($childrenByParent[0] ?? [] as $root) {
            $build($root);
        }

        return $this->cachedCategoryHierarchyIndex = $index;
    }

    public function resolveProductCategoryId(array $rows, ?array $hierarchyIndex = null): ?int
    {
        $hierarchyIndex ??= $this->categoryHierarchyIndex();

        $bestCategory = null;
        $bestScore = null;

        foreach ($rows as $row) {
            $main = $this->normalizeCategory($this->value($row, 'المدخل الرئيسي'));
            $child = $this->normalizeCategory($this->value($row, 'المدخل الفرعي'));
            $leaf = $this->normalizeCategory($this->value($row, 'التصنيف'));

            if ($leaf === '') {
                continue;
            }

            foreach ($hierarchyIndex as $candidate) {
                $trail = $candidate['trail'];
                $candidateMain = $trail[0] ?? '';
                $candidateChild = $trail[1] ?? '';
                $candidateLeaf = $trail[array_key_last($trail)] ?? '';

                if (! $this->categoryKeyMatches($leaf, $candidateLeaf)) {
                    continue;
                }

                if ($main !== '' && ! $this->categoryKeyMatches($main, $candidateMain)) {
                    continue;
                }

                if ($child !== '' && count($trail) > 2 && ! $this->categoryKeyMatches($child, $candidateChild)) {
                    continue;
                }

                $score = $this->categoryTrailScore([$main, $child, $leaf], $trail);

                if ($bestScore === null || $score < $bestScore) {
                    $bestScore = $score;
                    $bestCategory = $candidate['id'];
                }
            }
        }

        return $bestCategory;
    }

    private function categoryKeyMatches(string $left, string $right): bool
    {
        $leftKey = $this->normalizeCategoryKey($left);
        $rightKey = $this->normalizeCategoryKey($right);

        if ($leftKey === $rightKey) {
            return true;
        }

        return $this->unicodeLevenshtein($leftKey, $rightKey) <= 1;
    }

    private function categoryTrailScore(array $excelTrail, array $candidateTrail): int
    {
        $excelTrail = array_values(array_filter(array_map(fn ($item) => (string) $item, $excelTrail)));
        $candidateTrail = array_values(array_filter(array_map(fn ($item) => (string) $item, $candidateTrail)));

        $score = abs(count($excelTrail) - count($candidateTrail)) * 2;
        $max = min(count($excelTrail), count($candidateTrail));

        for ($i = 0; $i < $max; $i++) {
            $score += $this->unicodeLevenshtein(
                $this->normalizeCategoryKey($excelTrail[$i]),
                $this->normalizeCategoryKey($candidateTrail[$i]),
            );
        }

        return $score;
    }

    private function unicodeLevenshtein(string $left, string $right): int
    {
        if ($left === $right) {
            return 0;
        }

        $a = preg_split('//u', $left, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $b = preg_split('//u', $right, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $m = count($a);
        $n = count($b);

        if ($m === 0) {
            return $n;
        }

        if ($n === 0) {
            return $m;
        }

        $prev = range(0, $n);

        for ($i = 1; $i <= $m; $i++) {
            $curr = [$i];

            for ($j = 1; $j <= $n; $j++) {
                $cost = $a[$i - 1] === $b[$j - 1] ? 0 : 1;
                $curr[$j] = min(
                    $curr[$j - 1] + 1,
                    $prev[$j] + 1,
                    $prev[$j - 1] + $cost,
                );
            }

            $prev = $curr;
        }

        return (int) $prev[$n];
    }

    public function colorCodeMap(): array
    {
        if ($this->cachedColorCodeMap !== null) {
            return $this->cachedColorCodeMap;
        }

        return $this->cachedColorCodeMap = Color::query()
            ->get(['id', 'code'])
            ->mapWithKeys(function (Color $color): array {
                return [$this->normalizeColorKey((string) $color->code) => $color->id];
            })
            ->all();
    }

    public function colorNameMap(): array
    {
        if ($this->cachedColorNameMap !== null) {
            return $this->cachedColorNameMap;
        }

        return $this->cachedColorNameMap = Color::query()
            ->get(['id', 'name_ar', 'name_en', 'code'])
            ->mapWithKeys(function (Color $color): array {
                return [
                    $this->normalizeColorKey($color->name_ar) => $color->id,
                    $this->normalizeColorKey($color->name_en) => $color->id,
                    $this->normalizeColorKey($color->code) => $color->id,
                ];
            })
            ->all();
    }

    public function productColorLookup(array $productRows): array
    {
        $lookup = [];

        foreach ($productRows as $row) {
            $code = $this->normalizeText($this->value($row, 'الكود'));
            $colorName = $this->normalizeColor($this->value($row, 'اللون بالعربي', 'اللون'));
            $colorCode = $this->normalizeColorKey($this->value($row, 'رمز اللون', 'رمز اللون '));

            if ($code === '' || $colorName === '' || $colorCode === '') {
                continue;
            }

            $lookup[$this->normalizeText($code) . '||' . $this->normalizeColorKey($colorName)] = $colorCode;
        }

        return $lookup;
    }

    public function productColorCatalog(array $productRows): array
    {
        $byCode = [];

        foreach ($productRows as $row) {
            $colorName = $this->normalizeColor($this->value($row, 'اللون بالعربي', 'اللون'));
            $colorCode = $this->normalizeColorKey($this->value($row, 'رمز اللون', 'رمز اللون '));

            if ($colorName === '' || $colorCode === '') {
                continue;
            }

            $byCode[$colorCode] = $colorName;
        }

        return $byCode;
    }

    public function normalizeProductColorStatus(?string $value): string
    {
        $value = $this->normalizeText($value);

        return match ($value) {
            'تفعيل', 'فعال', 'active', '1', 'نعم', 'yes' => 'active',
            default => 'inactive',
        };
    }

    public function syncColorsFromProducts(array $productRows): void
    {
        $catalog = $this->productColorCatalog($productRows);

        foreach ($catalog as $colorCode => $colorName) {
            Color::updateOrCreate(
                ['code' => $colorCode],
                [
                    'name_ar' => $colorName,
                    'name_en' => Str::slug($colorName, ' '),
                    'status' => 'active',
                ],
            );
        }

        $structures = [];
        foreach ($productRows as $row) {
            $structure = $this->normalizeColor($this->value($row, 'التركيب'));
            if ($structure !== '') {
                $structures[$this->normalizeColorKey($structure)] = $structure;
            }
        }

        foreach (array_values($structures) as $structure) {
            $this->resolveOrCreateCatalogColorId($productRows, $structure);
        }
    }

    public function sizeMap(): array
    {
        if ($this->cachedSizeMap !== null) {
            return $this->cachedSizeMap;
        }

        return $this->cachedSizeMap = Size::query()
            ->get(['id', 'code', 'name_ar', 'name_en'])
            ->mapWithKeys(function (Size $size): array {
                return [
                    $this->normalizeSizeKey($size->code) => $size->id,
                    $this->normalizeSizeKey($size->name_ar) => $size->id,
                    $this->normalizeSizeKey($size->name_en) => $size->id,
                ];
            })
            ->all();
    }

    public function preview(array $productRows, array $variantRows, int $batchSize = 50): array
    {
        $batchCodes = $this->acceptedProductCodes($productRows, $batchSize);
        $hierarchyIndex = $this->categoryHierarchyIndex();
        $colorCatalog = $this->productColorCatalog($productRows);
        $sizeMap = $this->sizeMap();
        $productColorLookup = $this->productColorLookup($productRows);

        $acceptedProducts = [];
        $rejectedProducts = [];
        $acceptedVariants = [];
        $rejectedVariants = [];

        foreach ($productRows as $row) {
            $code = $this->normalizeText($this->value($row, 'الكود'));
            if ($code === '' || ! in_array($code, $batchCodes, true)) {
                continue;
            }

            $titleAr = $this->normalizeText($this->value($row, 'الاسم بالعربي'));
            $titleEn = $this->normalizeText($this->value($row, 'الاسم بالانكليزي'));
            $category = $this->normalizeCategory($this->value($row, 'التصنيف'));

            $reasons = [];
            if ($titleAr === '') {
                $reasons[] = 'missing_title_ar';
            }
            if ($titleEn === '') {
                $reasons[] = 'missing_title_en';
            }
            if ($category === '') {
                $reasons[] = 'missing_category';
            } elseif ($this->resolveProductCategoryId([$row], $hierarchyIndex) === null) {
                $reasons[] = 'unknown_category';
            }

            if ($reasons === []) {
                $acceptedProducts[] = $code;
            } else {
                $rejectedProducts[] = [
                    'model_no' => $code,
                    'reason' => implode('|', $reasons),
                ];
            }
        }

        foreach ($variantRows as $row) {
            $code = $this->normalizeText($this->value($row, 'الرمز'));
            if ($code === '' || ! in_array($code, $batchCodes, true)) {
                continue;
            }

            $color = $this->normalizeColor($this->value($row, 'اللون'));
            $colorCode = $this->resolveColorCode($code, $row, $productColorLookup);
            $size = $this->normalizeSize($this->value($row, 'القياس'));
            $quantity = $this->normalizeText($this->value($row, 'الكمية'));
            $price = $this->normalizeText($this->value($row, 'بيع'));
            $compare = $this->normalizeText($this->value($row, 'كرت'));

            $reasons = [];
            if (! in_array($code, $acceptedProducts, true)) {
                $reasons[] = 'product_not_in_accepted_batch';
            }
            if ($colorCode === '' && $color === '') {
                $reasons[] = 'missing_color';
            } elseif ($colorCode !== '' && ! isset($colorCatalog[$colorCode])) {
                $reasons[] = 'unknown_color_code';
            } elseif ($colorCode === '' && $color !== '' && ! isset($productColorLookup[$code . '||' . $this->normalizeColorKey($color)])) {
                $reasons[] = 'unknown_color';
            }
            if ($size === '') {
                $reasons[] = 'missing_size';
            } elseif (! isset($sizeMap[$this->normalizeSizeKey($size)])) {
                $reasons[] = 'unknown_size';
            }
            if (! is_numeric($quantity)) {
                $reasons[] = 'invalid_quantity';
            }
            if (! is_numeric($price)) {
                $reasons[] = 'invalid_price';
            }
            if (! is_numeric($compare)) {
                $reasons[] = 'invalid_compare_price';
            }

            if ($reasons === []) {
                $acceptedVariants[] = $code;
            } else {
                $rejectedVariants[] = [
                    'model_no' => $code,
                    'reason' => implode('|', $reasons),
                ];
            }
        }

        return [
            'accepted_products' => count($acceptedProducts),
            'rejected_products' => count($rejectedProducts),
            'accepted_variants' => count($acceptedVariants),
            'rejected_variants' => count($rejectedVariants),
            'rejected_products_rows' => $rejectedProducts,
            'rejected_variants_rows' => $rejectedVariants,
        ];
    }

    public function importProductsFile(string $productsPath): array
    {
        $productRows = $this->readRows($productsPath);
        $hierarchyIndex = $this->categoryHierarchyIndex();
        $this->syncColorsFromProducts($productRows);
        $colorCodeMap = $this->colorCodeMap();

        $productGroups = [];

        foreach ($productRows as $row) {
            $code = $this->normalizeText($this->value($row, 'الكود'));
            if ($code === '') {
                continue;
            }

            $productGroups[$code][] = $row;
        }

        $summary = [
            'products_created' => 0,
            'products_updated' => 0,
        ];

        DB::transaction(function () use ($productGroups, $hierarchyIndex, $colorCodeMap, &$summary): void {
            $productsByCode = [];

            foreach ($productGroups as $code => $rows) {
                $first = $rows[0];
                $flags = $this->mapTopFlag($this->value($first, 'top'));
                $visibility = $this->mapVisibilityFlags($this->value($first, 'الظهور'));
                $customerGroups = $this->parseCustomerGroupTokens($this->value($first, 'تخصيص العرض'));
                $productPrice = (float) $this->value($first, 'السعر بعد الحسم', 'السعر بعد الحسم ');
                $productComparePrice = (float) $this->value($first, 'السعر قبل الحسم', 'السعر قبل الحسم ');

                $product = Product::updateOrCreate(
                    ['model_no' => $code],
                    [
                        'category_id' => $this->resolveProductCategoryId($rows, $hierarchyIndex),
                        'title_ar' => $this->normalizeText($this->value($first, 'الاسم بالعربي')) ?: $code,
                        'title_en' => $this->normalizeText($this->value($first, 'الاسم بالانكليزي')) ?: $code,
                        'price' => $productPrice > 0 ? $productPrice : null,
                        'compare_price' => $productComparePrice > 0 ? $productComparePrice : null,
                        'structure' => $this->normalizeText($this->value($first, 'التركيب')),
                        'structure_color_id' => $this->resolveOrCreateCatalogColorId($rows, $this->value($first, 'التركيب')),
                        'collection' => $this->normalizeText($this->value($first, 'التشكيلة')),
                        'measurement_group' => $this->normalizeText($this->value($first, 'زمر وحدة القياس')) ?: null,
                        'visibility_targets' => $visibility['visibility_targets'],
                        'display_channels' => $visibility['display_channels'],
                        'show_web' => $visibility['show_web'],
                        'show_app' => $visibility['show_app'],
                        'show_retail' => $visibility['show_retail'],
                        'show_wholesale' => $visibility['show_wholesale'],
                        'description_ar' => $this->joinDescription([
                            $this->value($first, 'شرح بالعربي 1'),
                            $this->value($first, 'شرح بالعربي 2'),
                        ]),
                        'description_en' => $this->joinDescription([
                            $this->value($first, 'شرح بالانكليزي 1'),
                            $this->value($first, 'شرح بالانكليزي 2'),
                        ]),
                          'is_best_seller' => $flags['is_best_seller'],
                          'is_new' => $flags['is_new'],
                          'is_special_offer' => $flags['is_special_offer'],
                      ],
                  );

                $this->syncProductColors($product, $rows, $colorCodeMap);
                $this->syncRetailCustomerGroups($product, $visibility['show_retail'] ? $customerGroups : []);
                $this->syncWholesaleCustomerGroups($product, $visibility['show_wholesale'] ? $customerGroups : []);
                $this->syncWholesaleColors($product, $visibility['show_wholesale'] ? $rows : []);
                $this->syncWholesaleSeries($product, $visibility['show_wholesale'] ? $rows : []);
                $productsByCode[$code] = $product;
                $summary[$product->wasRecentlyCreated ? 'products_created' : 'products_updated']++;
            }

        });

        return $summary;
    }

    public function importComplementaryProductsFile(string $path): array
    {
        $rows = $this->readRows($path);

        $productGroups = [];

        foreach ($rows as $row) {
            $code = $this->normalizeText($this->value($row, 'الكود', 'الرمز', 'model_no'));

            if ($code === '') {
                continue;
            }

            $productGroups[$code][] = $row;
        }

        $complementaryLookup = $this->buildComplementaryProductLookup($productGroups);
        $synced = 0;
        $skipped = 0;

        DB::transaction(function () use ($productGroups, $complementaryLookup, &$synced, &$skipped): void {
            foreach ($productGroups as $code => $rows) {
                $product = Product::query()
                    ->whereRaw('TRIM(model_no) = ?', [$code])
                    ->first();

                if (! $product) {
                    $skipped++;
                    continue;
                }

                $this->syncComplementaryProducts($product, $rows, $complementaryLookup);
                $synced++;
            }
        });

        return compact('synced', 'skipped');
    }

    public function importRetailFiles(string $productsPath, string $variantsPath): array
    {
        $productRows = $this->readRows($productsPath);
        $variantRows = $this->readRows($variantsPath);
        $hierarchyIndex = $this->categoryHierarchyIndex();
        $this->syncColorsFromProducts($productRows);
        $colorCodeMap = $this->colorCodeMap();
        $sizeMap = $this->sizeMap();

        $productGroups = [];
        foreach ($productRows as $row) {
            $code = $this->normalizeText($this->value($row, 'الكود'));
            if ($code === '') {
                continue;
            }

            $productGroups[$code][] = $row;
        }

        $summary = [
            'products_created' => 0,
            'products_updated' => 0,
            'variants_created' => 0,
            'variants_updated' => 0,
            'variants_skipped' => 0,
        ];

        DB::transaction(function () use ($productGroups, $variantRows, $hierarchyIndex, $colorCodeMap, $sizeMap, &$summary): void {
            $productIdsByCode = [];
            $productsByCode = [];

            foreach ($productGroups as $code => $rows) {
                $first = $rows[0];
                $flags = $this->mapTopFlag($this->value($first, 'top'));
                $visibility = $this->mapVisibilityFlags($this->value($first, 'الظهور'));
                $productPrice = (float) $this->value($first, 'السعر بعد الحسم', 'السعر بعد الحسم ');
                $productComparePrice = (float) $this->value($first, 'السعر قبل الحسم', 'السعر قبل الحسم ');
                $product = Product::updateOrCreate(
                    ['model_no' => $code],
                    [
                        'category_id' => $this->resolveProductCategoryId($rows, $hierarchyIndex),
                        'title_ar' => $this->normalizeText($this->value($first, 'الاسم بالعربي')) ?: $code,
                        'title_en' => $this->normalizeText($this->value($first, 'الاسم بالانكليزي')) ?: $code,
                        'price' => $productPrice > 0 ? $productPrice : null,
                        'compare_price' => $productComparePrice > 0 ? $productComparePrice : null,
                        'structure' => $this->normalizeText($this->value($first, 'التركيب')),
                        'structure_color_id' => $this->resolveOrCreateCatalogColorId($rows, $this->value($first, 'التركيب')),
                        'collection' => $this->normalizeText($this->value($first, 'التشكيلة')),
                        'measurement_group' => $this->normalizeText($this->value($first, 'زمر وحدة القياس')) ?: null,
                        'visibility_targets' => $visibility['visibility_targets'],
                        'display_channels' => $visibility['display_channels'],
                        'show_web' => $visibility['show_web'],
                        'show_app' => $visibility['show_app'],
                        'show_retail' => $visibility['show_retail'],
                        'show_wholesale' => $visibility['show_wholesale'],
                        'description_ar' => $this->joinDescription([
                            $this->value($first, 'شرح بالعربي 1'),
                            $this->value($first, 'شرح بالعربي 2'),
                        ]),
                        'description_en' => $this->joinDescription([
                            $this->value($first, 'شرح بالانكليزي 1'),
                            $this->value($first, 'شرح بالانكليزي 2'),
                        ]),
                          'is_best_seller' => $flags['is_best_seller'],
                          'is_new' => $flags['is_new'],
                          'is_special_offer' => $flags['is_special_offer'],
                      ]
                  );

                $productIdsByCode[$code] = $product->id;
                $productsByCode[$code] = $product;
                $this->syncProductColors($product, $rows, $colorCodeMap);
                $this->syncRetailCustomerGroups($product, $visibility['show_retail'] ? $customerGroups : []);
                $this->syncWholesaleCustomerGroups($product, $visibility['show_wholesale'] ? $customerGroups : []);
                $this->syncWholesaleColors($product, $visibility['show_wholesale'] ? $rows : []);
                $this->syncWholesaleSeries($product, $visibility['show_wholesale'] ? $rows : []);
                $summary[$product->wasRecentlyCreated ? 'products_created' : 'products_updated']++;
            }

            foreach ($productGroups as $code => $rows) {
                if (! isset($productsByCode[$code])) {
                    continue;
                }
            }

            foreach ($variantRows as $row) {
                $code = $this->normalizeText($this->value($row, 'الرمز'));
                if ($code === '' || ! isset($productIdsByCode[$code])) {
                    $summary['variants_skipped']++;
                    continue;
                }

                $colorName = $this->normalizeColor($this->value($row, 'اللون'));
                $sizeCode = $this->normalizeSize($this->value($row, 'القياس'));
                $rawPrice = (float) $this->value($row, 'بيع');
                $rawCompare = (float) $this->value($row, 'كرت');
                $product = $productsByCode[$code] ?? null;
                $variantPrice = $rawPrice > 0 ? $rawPrice : (float) ($product?->price ?? 0);
                $variantCompare = $rawCompare > 0 ? $rawCompare : (float) ($product?->compare_price ?? 0);
                $normalizedSizeCode = $this->normalizeSizeKey($sizeCode);
                $productColor = $this->resolveProductColor((int) $productIdsByCode[$code], $row);
                $sizeId = $sizeMap[$normalizedSizeCode] ?? null;
                if (! $productColor || ! $sizeId) {
                    $summary['variants_skipped']++;
                    continue;
                }

                $payload = [
                    'product_id' => $productIdsByCode[$code],
                    'product_color_id' => $productColor->id,
                    'size_id' => $sizeId,
                    'price' => $variantPrice,
                    'compare_price' => $variantCompare,
                    'quantity' => (int) $this->value($row, 'الكمية'),
                    'is_default' => false,
                    'status' => $this->normalizeText($this->value($row, 'إيقاف')) === 'نعم' ? 'inactive' : 'active',
                ];

                $existing = ProductVariant::query()
                    ->where('product_id', $payload['product_id'])
                    ->where('product_color_id', $payload['product_color_id'])
                    ->where('size_id', $payload['size_id'])
                    ->first();

                if ($existing) {
                    $existing->fill($payload)->save();
                    $summary['variants_updated']++;
                } else {
                    ProductVariant::create($payload);
                    $summary['variants_created']++;
                }
            }
        });

        return $summary;
    }

    /**
     * @param array<int, array{series_group:int, size_text:string, quantity:int, source_value:string}> $items
     */
    protected function syncWholesaleSeries(Product $product, array $rows): void
    {
        $colors = ProductWholesaleColor::query()
            ->where('product_id', $product->id)
            ->get();

        ProductWholesaleQuantity::query()
            ->where('product_id', $product->id)
            ->delete();

        foreach ($rows as $row) {
            $seriesSource = $this->normalizeText($this->value($row, 'القياس'));

            if ($seriesSource === '') {
                continue;
            }

            $productColor = $this->resolveOrCreateWholesaleColor($product->id, $colors, $row);

            if (! $productColor) {
                continue;
            }

            if (! $colors->contains('id', $productColor->id)) {
                $colors->push($productColor);
            }

            foreach ($this->parseWholesaleSeries($seriesSource) as $item) {
                $seriesGroup = max(1, (int) ($item['series_group'] ?? 1));
                $seriesKey = $this->normalizeText($item['size_text'] ?? '');
                $quantity = (int) ($item['quantity'] ?? 0);
                $sourceValue = $this->normalizeText($item['source_value'] ?? '');

                if ($seriesKey === '') {
                    continue;
                }

                ProductWholesaleQuantity::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'product_wholesale_color_id' => $productColor->id,
                        'series_group' => $seriesGroup,
                        'size_text' => $seriesKey,
                    ],
                    [
                        'quantity' => $quantity,
                        'source_value' => $sourceValue !== '' ? $sourceValue : null,
                    ],
                );
            }
        }
    }

    /**
     * @param array<string, Product> $productLookup
     */
    protected function syncComplementaryProducts(Product $product, array $rows, array $productLookup): void
    {
        $sourceCodes = $this->parseComplementaryProductCodes($rows[0] ?? []);
        $seenRelatedProductIds = [];

        foreach ($sourceCodes as $index => $code) {
            $normalizedCode = $this->normalizeText($code);

            if ($normalizedCode === '' || $normalizedCode === $this->normalizeText($product->model_no)) {
                continue;
            }

            $relatedProduct = $productLookup[$normalizedCode] ?? Product::query()
                ->whereRaw('TRIM(model_no) = ?', [$normalizedCode])
                ->first();

            if (! $relatedProduct) {
                continue;
            }

            if (! $relatedProduct->is_active || ! $this->productHasKnownStock($relatedProduct)) {
                continue;
            }

            ProductComplement::query()->updateOrCreate(
                [
                    'product_id' => $product->id,
                    'related_product_id' => $relatedProduct->id,
                ],
                [
                    'sort_order' => $index + 1,
                ],
            );

            $seenRelatedProductIds[] = $relatedProduct->id;
        }

        $seenRelatedProductIds = array_values(array_unique($seenRelatedProductIds));

        if ($seenRelatedProductIds === []) {
            ProductComplement::query()
                ->where('product_id', $product->id)
                ->delete();

            return;
        }

        ProductComplement::query()
            ->where('product_id', $product->id)
            ->whereNotIn('related_product_id', $seenRelatedProductIds)
            ->delete();
    }

    protected function productHasKnownStock(Product $product): bool
    {
        $variants = $product->variants();

        if (! $variants->exists()) {
            return true;
        }

        return $variants->where('quantity', '>', 0)->exists();
    }

    /**
     * @param array<int, string> $names
     */
    protected function syncRetailCustomerGroups(Product $product, array $names): void
    {
        $groupIds = [];

        foreach ($names as $name) {
            $name = $this->normalizeText($name);

            if ($name === '') {
                continue;
            }

            $groupIds[] = RetailCustomerGroup::query()->firstOrCreate([
                'name' => $name,
            ])->id;
        }

        ProductRetailGroupAssignment::query()->where('product_id', $product->id)->delete();

        foreach (array_values(array_unique($groupIds)) as $groupId) {
            ProductRetailGroupAssignment::create([
                'product_id' => $product->id,
                'retail_customer_group_id' => $groupId,
            ]);
        }
    }

    /**
     * @param array<int, string> $names
     */
    protected function syncWholesaleCustomerGroups(Product $product, array $names): void
    {
        $groupIds = [];

        foreach ($names as $name) {
            $name = $this->normalizeText($name);

            if ($name === '') {
                continue;
            }

            $groupIds[] = WholesaleCustomerGroup::query()->firstOrCreate(
                ['name_ar' => $name],
                ['status' => 'active', 'sort_order' => 0]
            )->id;
        }

        ProductWholesaleGroupAssignment::query()->where('product_id', $product->id)->delete();

        foreach (array_values(array_unique($groupIds)) as $groupId) {
            ProductWholesaleGroupAssignment::create([
                'product_id' => $product->id,
                'wholesale_customer_group_id' => $groupId,
            ]);
        }
    }

    /**
     * @param array<int, array<string, string>> $rows
     */
    protected function syncWholesaleColors(Product $product, array $rows): void
    {
        $seen = [];

        foreach ($rows as $row) {
            $colorCode = $this->normalizeText($this->value($row, 'رمز اللون'));
            $colorNameAr = $this->normalizeColor($this->value($row, 'اللون بالعربي'));
            $colorNameEn = $this->normalizeText($this->value($row, 'اللون بالانكليزي', 'اللون بالإنكليزي'));

            if ($colorCode === '' && $colorNameAr === '' && $colorNameEn === '') {
                continue;
            }

            $lookupCode = $colorCode !== '' ? $colorCode : md5($colorNameAr . '|' . $colorNameEn);
            $seen[] = $lookupCode;

            ProductWholesaleColor::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'color_code' => $lookupCode,
                ],
                [
                    'color_name_ar' => $colorNameAr !== '' ? $colorNameAr : null,
                    'color_name_en' => $colorNameEn !== '' ? $colorNameEn : null,
                ],
            );
        }

        if ($seen !== []) {
            ProductWholesaleColor::query()
                ->where('product_id', $product->id)
                ->whereNotIn('color_code', array_values(array_unique($seen)))
                ->delete();
        } else {
            ProductWholesaleColor::query()
                ->where('product_id', $product->id)
                ->delete();
        }
    }

    public function resolveProductColor(int $productId, array $row): ?ProductColor
    {
        $explicitCode = $this->normalizeColorKey($this->value($row, 'رمز اللون', 'رمز اللون '));
        $colorName = $this->normalizeColor($this->value($row, 'اللون', 'اللون بالعربي'));
        $normalizedName = $this->normalizeColorKey($colorName);

        $query = ProductColor::query()->where('product_id', $productId);

        if ($explicitCode !== '') {
            $match = (clone $query)
                ->whereRaw('UPPER(REPLACE(color_code, " ", "")) = ?', [$explicitCode])
                ->first();

            if ($match) {
                return $match;
            }
        }

        if ($normalizedName !== '') {
            $productColors = (clone $query)->get();

            $match = $productColors->first(function (ProductColor $productColor) use ($normalizedName): bool {
                return $this->normalizeColorKey((string) $productColor->color_name_ar) === $normalizedName
                    || $this->normalizeColorKey((string) $productColor->color_name_en) === $normalizedName
                    || $this->normalizeColorKey((string) $productColor->color_code) === $normalizedName;
            });

            if ($match) {
                return $match;
            }
        }

        return null;
    }

    public function resolveOrCreateCatalogColorId(array $rows, ?string $value): ?int
    {
        $normalized = $this->normalizeColorKey($this->normalizeColor($value));

        if ($normalized === '') {
            return null;
        }

        $colors = $this->cachedColorDictionary();

        $color = $colors[$normalized] ?? null;

        if ($color) {
            return $color->id;
        }

        $colorName = $this->normalizeColor($value);
        $englishName = '';

        foreach ($rows as $row) {
            $rowColorName = $this->normalizeColor($this->value($row, 'اللون بالعربي', 'اللون'));

            if ($this->normalizeColorKey($rowColorName) !== $normalized) {
                continue;
            }

            $englishName = $this->normalizeText($this->value($row, 'اللون بالانكليزي'));
            if ($englishName !== '') {
                break;
            }
        }

        $codeSource = $englishName !== '' ? $englishName : $colorName;
        $code = Str::slug($codeSource, '-');
        if ($code === '') {
            $code = 'color-' . substr(sha1($normalized), 0, 8);
        }

        $created = Color::create([
            'code' => $code,
            'name_ar' => $colorName,
            'name_en' => $englishName !== '' ? Str::slug($englishName, ' ') : (Str::slug($colorName, ' ') ?: $code),
            'status' => 'active',
        ]);

        return $created->id;
    }

    protected function cachedColorDictionary(): array
    {
        if ($this->cachedColorDictionary !== null) {
            return $this->cachedColorDictionary;
        }

        $this->cachedColorDictionary = [];

        foreach (Color::query()->get(['id', 'code', 'name_ar', 'name_en']) as $item) {
            $this->cachedColorDictionary[$this->normalizeColorKey((string) $item->code)] = $item;
            $this->cachedColorDictionary[$this->normalizeColorKey((string) $item->name_ar)] = $item;
            $this->cachedColorDictionary[$this->normalizeColorKey((string) $item->name_en)] = $item;
        }

        return $this->cachedColorDictionary;
    }

    protected function resolveOrCreateWholesaleColor(int $productId, $colors, array $row): ?ProductWholesaleColor
    {
        $colorCode = $this->normalizeText($this->value($row, 'رمز اللون'));
        $colorNameAr = $this->normalizeColor($this->value($row, 'اللون بالعربي', 'اللون'));
        $colorNameEn = $this->normalizeText($this->value($row, 'اللون بالانكليزي', 'اللون بالإنكليزي'));

        if ($colorCode !== '') {
            $match = $colors->first(fn (ProductWholesaleColor $color): bool => $this->normalizeColorKey((string) $color->color_code) === $this->normalizeColorKey($colorCode));

            if ($match) {
                return $match;
            }
        }

        if ($colorNameAr !== '' || $colorNameEn !== '') {
            $normalizedAr = $this->normalizeColorKey($colorNameAr);
            $normalizedEn = $this->normalizeColorKey($colorNameEn);

            $match = $colors->first(function (ProductWholesaleColor $color) use ($normalizedAr, $normalizedEn): bool {
                return ($normalizedAr !== '' && $this->normalizeColorKey((string) $color->color_name_ar) === $normalizedAr)
                    || ($normalizedEn !== '' && $this->normalizeColorKey((string) $color->color_name_en) === $normalizedEn);
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

    protected function syncProductColors(Product $product, array $rows, array $colorCodeMap): void
    {
        $colors = [];
        $sortOrder = 0;

        foreach ($rows as $row) {
            $colorCode = $this->normalizeColorKey($this->value($row, 'رمز اللون', 'رمز اللون '));
            $colorNameAr = $this->normalizeColor($this->value($row, 'اللون بالعربي', 'اللون'));
            $colorNameEn = $this->normalizeText($this->value($row, 'اللون بالانكليزي'));
            $status = $this->normalizeProductColorStatus($this->value($row, 'خاص'));

            if ($colorCode === '' || $colorNameAr === '') {
                continue;
            }

            if (! isset($colorCodeMap[$colorCode])) {
                continue;
            }

            if (! isset($colors[$colorCode])) {
                $sortOrder++;
                $colors[$colorCode] = [
                    'color_name_ar' => $colorNameAr,
                    'color_name_en' => $colorNameEn !== '' ? $colorNameEn : null,
                    'status' => $status,
                    'sort_order' => $sortOrder,
                ];
            } else {
                if (blank($colors[$colorCode]['color_name_en']) && $colorNameEn !== '') {
                    $colors[$colorCode]['color_name_en'] = $colorNameEn;
                }

                if ($status === 'active') {
                    $colors[$colorCode]['status'] = 'active';
                }
            }
        }

        foreach ($colors as $colorCode => $payload) {
            ProductColor::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'color_code' => $colorCode,
                ],
                $payload,
            );
        }

        if ($colors !== []) {
            ProductColor::query()
                ->where('product_id', $product->id)
                ->whereNotIn('color_code', array_keys($colors))
                ->delete();
        }
    }

    public function joinDescription(array $parts): ?string
    {
        $parts = array_map(fn ($part) => $this->normalizeText((string) $part), $parts);
        $parts = array_values(array_filter($parts));

        return $parts === [] ? null : implode("\n", $parts);
    }

    private function excelColumn(int $index): string
    {
        $result = '';

        while ($index > 0) {
            $index--;
            $result = chr(65 + ($index % 26)) . $result;
            $index = intdiv($index, 26);
        }

        return $result;
    }
}
