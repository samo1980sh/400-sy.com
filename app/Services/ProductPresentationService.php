<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductColor;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class ProductPresentationService
{
    public function __construct(
        protected ProductImageCatalogService $imageCatalog,
    ) {
    }

    public function presentProduct(Product $product, ?string $locale = null, array $preferredFilterColorIds = [], ?int $colorLimit = 4): array
    {
        $locale ??= app()->getLocale();

        $imagePath = $this->imageCatalog->mainImagePath($product);
        $fallbackImageUrl = filled($imagePath)
            ? Storage::disk('public')->url($imagePath)
            : asset('images/products/4brouwn1.jpg');

        $title = $this->localizedValue($product->title_ar ?? null, $product->title_en ?? null, $locale)
            ?: ($product->model_no ?: __('front.brand'));
        $currency = $locale === 'ar' ? ($product->currency_ar ?: 'SYP') : ($product->currency_en ?: 'SYP');
        $badge = $this->productBadge($product, $locale);
        $productCode = $this->productCodeLabel($product);
        $pricing = $this->productPricing($product, $currency);
        $sizePricing = $this->buildSizePricing($product, $locale, $currency);
        $sizeOptions = array_values($sizePricing);
        $sizes = $this->productSizes($sizeOptions, $product, $locale);
        $colors = $this->productColors($product, $locale, $currency, $pricing, $preferredFilterColorIds, $colorLimit);
        $defaultColor = $colors[0] ?? [];
        $defaultSize = ($defaultColor['default_size'] ?? null)
            ?: ($this->selectDefaultSize($sizeOptions) ?: (array_key_first($sizePricing) ?: ($sizes[0] ?? null)));
        $displayPricing = filled($defaultColor['price_current_label'] ?? null) ? [
            'price_current' => $defaultColor['price_current'] ?? $pricing['current'],
            'compare_price' => $defaultColor['compare_price'] ?? $pricing['compare'],
            'price_current_label' => $defaultColor['price_current_label'] ?? $pricing['current_label'],
            'compare_price_label' => $defaultColor['compare_price_label'] ?? $pricing['compare_label'],
        ] : ($defaultSize && isset($sizePricing[$defaultSize]) ? $sizePricing[$defaultSize] : [
            'price_current' => $pricing['current'],
            'compare_price' => $pricing['compare'],
            'price_current_label' => $pricing['current_label'],
            'compare_price_label' => $pricing['compare_label'],
        ]);
        $imageUrl = filled($defaultColor['image'] ?? null) ? (string) $defaultColor['image'] : $fallbackImageUrl;
        $gallery = collect(array_merge([$imageUrl], array_map(fn (array $color): string => $color['image'] ?? '', $colors)))
            ->filter()
            ->unique()
            ->values()
            ->all();
        $sizeChart = $this->buildSizeChart($product, $locale);
        $hasSizeChart = ! empty($sizeChart['rows']) && ! empty($sizeChart['columns']);
        $specifications = $this->productSpecifications($product, $locale, $defaultColor);
        $hasAvailableSizes = collect($defaultColor['size_options'] ?? $sizeOptions)
            ->contains(fn (array $option): bool => ($option['available'] ?? true) === true && ! ($option['is_sold_out'] ?? false));
        $categoryName = $product->relationLoaded('category')
            ? $this->localizedValue($product->category?->title_ar ?? null, $product->category?->title_en ?? null, $locale)
            : $this->localizedValue($product->category()?->value('title_ar'), $product->category()?->value('title_en'), $locale);
        $filterColorName = $product->relationLoaded('structureColor')
            ? $this->localizedValue($product->structureColor?->name_ar ?? null, $product->structureColor?->name_en ?? null, $locale)
            : $this->localizedValue($product->structureColor()?->value('name_ar'), $product->structureColor()?->value('name_en'), $locale);

        return [
            'id' => $product->getKey(),
            'slug' => $product->slug ?? null,
            'title' => $title,
            'description' => $this->localizedValue($product->description_ar ?? null, $product->description_en ?? null, $locale) ?: '',
            'badge' => $badge['label'] ?? null,
            'badge_class' => $badge['class'] ?? null,
            'product_code' => $productCode,
            'url' => filled($product->slug) ? route('front.products.show', $product->slug) : route('front.home') . '#featured-products',
            'list_url' => filled($product->slug) ? route('front.products.show', $product->slug) : route('front.home') . '#featured-products',
            'detail_url' => filled($product->slug) ? route('front.products.show', $product->slug) : route('front.home') . '#featured-products',
            'cart_add_url' => filled($product->slug) ? route('front.cart.add', $product->slug) : null,
            'image' => $imageUrl,
            'gallery' => $gallery,
            'price_current' => $displayPricing['price_current'] ?? $pricing['current'],
            'compare_price' => $displayPricing['compare_price'] ?? $pricing['compare'],
            'price_current_label' => $displayPricing['price_current_label'] ?? $pricing['current_label'],
            'compare_price_label' => $displayPricing['compare_price_label'] ?? $pricing['compare_label'],
            'price_label' => $displayPricing['price_current_label'] ?? $pricing['current_label'],
            'base_price' => $pricing['current'],
            'base_price_label' => $pricing['current_label'],
            'base_currency' => $currency,
            'sizes' => $sizes,
            'size_options' => $sizeOptions,
            'size_pricing' => $sizePricing,
            'colors' => $colors,
            'default_size' => $defaultSize,
            'default_color' => $defaultColor['name'] ?? null,
            'default_color_class' => $defaultColor['class_name'] ?? null,
            'default_color_code' => $defaultColor['color_code'] ?? null,
            'display_color_description' => trim((string) ($product->structure ?? '')) ?: null,
            'filter_color_name' => $filterColorName ?: null,
            'body_fit' => trim((string) ($product->body_fit ?? '')) ?: null,
            'drop_type' => trim((string) ($product->drop_type ?? '')) ?: null,
            'collection' => trim((string) ($product->collection ?? '')) ?: null,
            'country' => trim((string) ($product->country ?? '')) ?: null,
            'measurement_group' => trim((string) ($product->measurement_group ?? '')) ?: null,
            'category_name' => $categoryName ?: null,
            'specifications' => $specifications,
            'has_available_sizes' => $hasAvailableSizes,
            'size_chart' => $sizeChart,
            'has_size_chart' => $hasSizeChart,
        ];
    }

    protected function productBadge(Product $product, string $locale): array
    {
        if ($product->is_special_offer) {
            return [
                'label' => $locale === 'ar' ? __('front.products.badge_offer') : __('front.products.badge_offer'),
                'class' => 'badge-offer',
            ];
        }

        if ($product->is_best_seller) {
            return [
                'label' => $locale === 'ar' ? __('front.products.badge_best_seller') : __('front.products.badge_best_seller'),
                'class' => 'badge-best-seller',
            ];
        }

        if ($product->is_new) {
            return [
                'label' => $locale === 'ar' ? __('front.products.badge_new') : __('front.products.badge_new'),
                'class' => 'badge-new',
            ];
        }

        return [
            'label' => null,
            'class' => null,
        ];
    }

    protected function productCodeLabel(Product $product): ?string
    {
        $code = trim((string) ($product->model_no ?? ''));

        if ($code === '') {
            return null;
        }

        return mb_strlen($code) > 3 ? trim(mb_substr($code, 3)) : $code;
    }

    protected function productPricing(Product $product, string $currency): array
    {
        $current = $this->resolvePriceNumber($product->price);
        $compare = $this->resolvePriceNumber($product->compare_price);

        return [
            'current' => $current,
            'compare' => $compare,
            'current_label' => $this->formatPriceLabel($current, $currency),
            'compare_label' => $compare !== null ? $this->formatPriceLabel($compare, $currency) : null,
        ];
    }

    protected function productColors(Product $product, string $locale, string $currency, array $pricing, array $preferredFilterColorIds = [], ?int $limit = 4): array
    {
        $variantsByColor = $this->variantsByColor($product);
        $productColorsById = $product->relationLoaded('productColors')
            ? $product->productColors->keyBy('id')
            : collect();
        $activeColorIds = $productColorsById
            ->where('status', 'active')
            ->keys()
            ->map(fn ($id): int => (int) $id)
            ->all();
        $preferredFilterColorIds = collect($preferredFilterColorIds)
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        $rankColor = function (array $color) use ($productColorsById, $preferredFilterColorIds): array {
            $id = (int) ($color['id'] ?? 0);
            $productColor = $productColorsById->get($id);
            $filterColorId = (int) ($productColor?->filter_color_id ?? 0);
            $preferredIndex = $filterColorId > 0
                ? array_search($filterColorId, $preferredFilterColorIds, true)
                : false;

            return [
                $preferredIndex === false ? 1 : 0,
                $preferredIndex === false ? 9999 : (int) $preferredIndex,
                (int) ($productColor?->sort_order ?? $color['sort_order'] ?? 9999),
                $id,
            ];
        };

        return collect($this->imageCatalog->availableColors($product))
            ->filter(function (array $color) use ($activeColorIds, $variantsByColor): bool {
                $id = (int) ($color['id'] ?? 0);

                return $id > 0
                    && in_array($id, $activeColorIds, true)
                    && $variantsByColor->has($id);
            })
            ->sort(function (array $first, array $second) use ($rankColor): int {
                $firstRank = $rankColor($first);
                $secondRank = $rankColor($second);

                foreach ([0, 1, 2, 3] as $index) {
                    if ($firstRank[$index] === $secondRank[$index]) {
                        continue;
                    }

                    return $firstRank[$index] <=> $secondRank[$index];
                }

                return 0;
            })
            ->when($limit !== null, fn (Collection $colors): Collection => $colors->take($limit))
            ->values()
            ->map(function (array $color, int $index) use ($product, $locale, $currency, $pricing, $variantsByColor, $productColorsById): array {
                $fallbackImage = asset('images/products/4brouwn1.jpg');
                $gallery = $color['detail_urls'] ?? $color['thumb_urls'] ?? [];
                $productColorId = (int) ($color['id'] ?? 0);
                $productColor = $productColorsById->get($productColorId);
                $filterColor = $productColor instanceof ProductColor ? $productColor->filterColor : null;
                $swatchImage = $this->swatchImageUrl($productColor?->swatch_image ?? null);
                $hex = $this->normalizeHexColor($productColor?->color_hex ?? null)
                    ?? $this->normalizeHexColor($filterColor?->hex ?? null);
                $swatchStyle = $this->swatchStyle($swatchImage, $hex);
                $colorVariants = $variantsByColor->get($productColorId, collect());
                $defaultVariant = $this->selectDefaultVariant($colorVariants);
                $colorPricing = $this->resolveVariantPricing($product, $defaultVariant, $currency, $pricing);
                $sizeOptions = $this->buildSizeOptions($product, $colorVariants, $locale, $currency);
                $sizes = $this->sizeLabelsForVariants($sizeOptions, $locale);
                $defaultSize = $this->selectDefaultSize($sizeOptions) ?: ($this->variantSizeLabel($defaultVariant, $locale) ?: ($sizes[0] ?? null));

                return [
                    'id' => $productColorId,
                    'name' => $this->localizedValue($color['name_ar'] ?? null, $color['name_en'] ?? null, $locale) ?: ($color['name'] ?? '-'),
                    'class_name' => $color['class_name'] ?? 'four-Black',
                    'color_code' => $color['color_code'] ?? null,
                    'filter_color_id' => (int) ($productColor?->filter_color_id ?? 0),
                    'hex' => $hex,
                    'swatch_image' => $swatchImage,
                    'swatch_style' => $swatchStyle,
                    'image' => $color['primary_thumb_url'] ?? $fallbackImage,
                    'gallery' => is_array($gallery) && $gallery !== [] ? array_values($gallery) : [$color['primary_thumb_url'] ?? $fallbackImage],
                    'active' => $index === 0,
                    'sizes' => $sizes,
                    'size_options' => $sizeOptions,
                    'default_size' => $defaultSize,
                    'variants' => $this->mapVariantPricing($product, $colorVariants, $locale, $currency, $pricing),
                    'price_current' => $colorPricing['current'],
                    'compare_price' => $colorPricing['compare'],
                    'price_current_label' => $colorPricing['current_label'],
                    'compare_price_label' => $colorPricing['compare_label'],
                ];
            })
            ->all();
    }

    protected function productSpecifications(Product $product, string $locale, array $defaultColor = []): array
    {
        $items = collect([
            [
                'label' => $locale === 'ar' ? 'كود المنتج' : 'Product code',
                'value' => $this->productCodeLabel($product),
            ],
            [
                'label' => $locale === 'ar' ? 'التصنيف' : 'Category',
                'value' => $product->relationLoaded('category')
                    ? $this->localizedValue($product->category?->title_ar ?? null, $product->category?->title_en ?? null, $locale)
                    : null,
            ],
            [
                'label' => $locale === 'ar' ? 'لون المنتج المعروض' : 'Displayed color',
                'value' => trim((string) ($product->structure ?? '')) ?: null,
            ],
            [
                'label' => $locale === 'ar' ? 'لون الفلترة' : 'Filter color',
                'value' => $product->relationLoaded('structureColor')
                    ? $this->localizedValue($product->structureColor?->name_ar ?? null, $product->structureColor?->name_en ?? null, $locale)
                    : null,
            ],
            [
                'label' => $locale === 'ar' ? 'اللون الافتراضي' : 'Default color',
                'value' => trim((string) ($defaultColor['name'] ?? '')) ?: null,
            ],
            [
                'label' => $locale === 'ar' ? 'Body Fit' : 'Body Fit',
                'value' => trim((string) ($product->body_fit ?? '')) ?: null,
            ],
            [
                'label' => $locale === 'ar' ? 'Drop' : 'Drop',
                'value' => trim((string) ($product->drop_type ?? '')) ?: null,
            ],
            [
                'label' => $locale === 'ar' ? 'المنشأ' : 'Country',
                'value' => trim((string) ($product->country ?? '')) ?: null,
            ],
            [
                'label' => $locale === 'ar' ? 'المجموعة' : 'Collection',
                'value' => trim((string) ($product->collection ?? '')) ?: null,
            ],
            [
                'label' => $locale === 'ar' ? 'زمرة القياس' : 'Measurement group',
                'value' => trim((string) ($product->measurement_group ?? '')) ?: null,
            ],
        ])->filter(fn (array $item): bool => filled($item['value'] ?? null));

        $details = $product->relationLoaded('details')
            ? $product->getRelation('details')
            : $product->details()->where('is_active', true)->orderBy('sort_order')->orderBy('id')->get();

        $extraDetails = $details
            ->filter(fn ($detail): bool => (bool) ($detail->is_active ?? true))
            ->map(function ($detail) use ($locale): array {
                return [
                    'label' => $this->localizedValue($detail->label_ar ?? null, $detail->label_en ?? null, $locale),
                    'value' => $this->localizedValue($detail->value_ar ?? null, $detail->value_en ?? null, $locale),
                ];
            })
            ->filter(fn (array $item): bool => filled($item['label'] ?? null) && filled($item['value'] ?? null));

        return $items
            ->merge($extraDetails)
            ->unique(fn (array $item): string => mb_strtolower(trim((string) ($item['label'] ?? ''))))
            ->values()
            ->all();
    }

    protected function normalizeHexColor(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (! str_starts_with($value, '#')) {
            $value = '#'.$value;
        }

        return preg_match('/^#[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/', $value) === 1
            ? strtoupper($value)
            : null;
    }

    protected function swatchImageUrl(?string $path): ?string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return null;
        }

        if (preg_match('~^(https?:)?//~i', $path) || str_starts_with($path, 'data:')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }

    protected function swatchStyle(?string $swatchImage, ?string $hex): string
    {
        if (filled($swatchImage)) {
            return sprintf(
                "background-image: url('%s'); background-size: cover; background-position: center; background-color: transparent;",
                e((string) $swatchImage)
            );
        }

        if (filled($hex)) {
            return 'background-color: '.e((string) $hex).';';
        }

        return '';
    }

    protected function buildSizePricing(Product $product, string $locale, string $currency): array
    {
        return collect($this->buildSizeOptions($product, $this->variantsCollection($product), $locale, $currency))
            ->mapWithKeys(function (array $option): array {
                $sizeLabel = $option['size'] ?? null;

                if (! filled($sizeLabel)) {
                    return [];
                }

                return [$sizeLabel => $option];
            })
            ->all();
    }

    protected function buildSizeOptions(Product $product, Collection $variants, string $locale, string $currency): array
    {
        $baseCurrent = $this->resolvePriceNumber($product->price);
        $baseCompare = $this->resolvePriceNumber($product->compare_price);

        return $variants
            ->filter(fn (ProductVariant $variant): bool => $variant->relationLoaded('size') && $variant->size)
            ->map(function (ProductVariant $variant) use ($product, $locale, $currency, $baseCurrent, $baseCompare): ?array {
                $sizeLabel = $this->variantSizeLabel($variant, $locale);

                if (! filled($sizeLabel)) {
                    return null;
                }

                $quantity = max(0, (int) ($variant->quantity ?? 0));
                $current = $this->resolveVariantNumber($variant->price, $baseCurrent ?? $product->price);
                $compare = $this->resolveVariantNumber($variant->compare_price, $baseCompare ?? $product->compare_price);

                return [
                    'size' => $sizeLabel,
                    'size_id' => $variant->size_id,
                    'size_code' => $variant->size?->code,
                    'barcode' => $variant->barcode ?? null,
                    'sku' => $variant->sku ?? null,
                    'variant_id' => $variant->id,
                    'product_color_id' => $variant->product_color_id,
                    'quantity' => $quantity,
                    'available' => $quantity > 0,
                    'is_sold_out' => $quantity <= 0,
                    'price_current' => $current ?? $baseCurrent,
                    'compare_price' => $compare,
                    'price_current_label' => $this->formatPriceLabel($current ?? $baseCurrent, $currency),
                    'compare_price_label' => $compare !== null ? $this->formatPriceLabel($compare, $currency) : null,
                    'is_default' => (bool) ($variant->is_default ?? false),
                    'status_label' => $quantity > 0 ? null : __('front.products.sold_out'),
                ];
            })
            ->filter(fn (?array $option): bool => is_array($option) && filled($option['size'] ?? null))
            ->values()
            ->all();
    }

    protected function productSizes(array $sizeOptions, Product $product, string $locale): array
    {
        $sizes = collect($sizeOptions)
            ->pluck('size')
            ->filter()
            ->unique()
            ->values()
            ->take(6)
            ->all();

        return $sizes !== [] ? $sizes : ['S', 'M', 'L'];
    }

    protected function selectDefaultSize(array $sizeOptions): ?string
    {
        $available = collect($sizeOptions)->first(fn (array $option): bool => (bool) ($option['available'] ?? false));

        if (is_array($available) && filled($available['size'] ?? null)) {
            return $available['size'];
        }

        $first = $sizeOptions[0] ?? null;

        return is_array($first) && filled($first['size'] ?? null) ? $first['size'] : null;
    }

    protected function variantsCollection(Product $product): Collection
    {
        $activeColorIds = $product->relationLoaded('productColors')
            ? $product->productColors
                ->where('status', 'active')
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all()
            : [];

        if ($product->relationLoaded('variants')) {
            $variants = $product->getRelation('variants');

            if ($variants instanceof Collection) {
                return $variants
                    ->filter(fn (ProductVariant $variant): bool => $activeColorIds === []
                        || in_array((int) ($variant->product_color_id ?? 0), $activeColorIds, true))
                    ->values();
            }
        }

        return $product->variants()
            ->whereHas('productColor', fn ($query) => $query->where('status', 'active'))
            ->with('size')
            ->get()
            ->values();
    }

    protected function variantsByColor(Product $product): Collection
    {
        return $this->variantsCollection($product)->groupBy(function (ProductVariant $variant): int {
            return (int) ($variant->product_color_id ?? 0);
        });
    }

    protected function selectDefaultVariant(Collection $variants): ?ProductVariant
    {
        $defaultVariant = $variants->first(fn (ProductVariant $variant): bool => (bool) ($variant->is_default ?? false));

        return $defaultVariant instanceof ProductVariant ? $defaultVariant : ($variants->first() instanceof ProductVariant ? $variants->first() : null);
    }

    protected function resolveVariantPricing(Product $product, ?ProductVariant $variant, string $currency, array $fallbackPricing): array
    {
        $current = $this->resolveVariantNumber($variant?->price, $fallbackPricing['current'] ?? $product->price);
        $compare = $this->resolveVariantNumber($variant?->compare_price, $fallbackPricing['compare'] ?? $product->compare_price);

        return [
            'current' => $current,
            'compare' => $compare,
            'current_label' => $this->formatPriceLabel($current, $currency),
            'compare_label' => $compare !== null ? $this->formatPriceLabel($compare, $currency) : null,
        ];
    }

    protected function mapVariantPricing(Product $product, Collection $variants, string $locale, string $currency, array $fallbackPricing): array
    {
        return $variants->map(function (ProductVariant $variant) use ($product, $locale, $currency, $fallbackPricing): array {
            $pricing = $this->resolveVariantPricing($product, $variant, $currency, $fallbackPricing);
            $size = $this->variantSizeLabel($variant, $locale);

            return [
                'variant_id' => $variant->id,
                'product_color_id' => $variant->product_color_id,
                'size_id' => $variant->size_id,
                'size_code' => $variant->size?->code,
                'size' => $size,
                'barcode' => $variant->barcode ?? null,
                'sku' => $variant->sku ?? null,
                'price_current' => $pricing['current'],
                'compare_price' => $pricing['compare'],
                'price_current_label' => $pricing['current_label'],
                'compare_price_label' => $pricing['compare_label'],
                'is_default' => (bool) ($variant->is_default ?? false),
            ];
        })->filter(fn (array $item): bool => filled($item['size'] ?? null))->values()->all();
    }

    protected function sizeLabelsForVariants(array|Collection $variants, string $locale): array
    {
        if ($variants instanceof Collection) {
            return $variants
                ->map(fn (ProductVariant $variant): ?string => $this->variantSizeLabel($variant, $locale))
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        return collect($variants)
            ->pluck('size')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    protected function variantSizeLabel(?ProductVariant $variant, string $locale): ?string
    {
        if (! $variant instanceof ProductVariant || ! $variant->relationLoaded('size') || ! $variant->size) {
            return null;
        }

        return $this->localizedValue($variant->size->name_ar ?? null, $variant->size->name_en ?? null, $locale) ?: ($variant->size->code ?? null);
    }

    protected function resolveVariantNumber(mixed $value, mixed $fallback): ?float
    {
        $current = is_numeric($value) ? (float) $value : (is_numeric($fallback) ? (float) $fallback : null);

        if ($current === null || $current <= 0) {
            return null;
        }

        return round($current, 2);
    }

    protected function resolvePriceNumber(mixed $value): ?float
    {
        if (! is_numeric($value)) {
            return null;
        }

        $current = (float) $value;

        if ($current <= 0) {
            return null;
        }

        return round($current, 2);
    }

    protected function formatPriceLabel(?float $price, string $currency): ?string
    {
        if ($price === null) {
            return null;
        }

        return number_format($price, 0) . ' ' . $currency;
    }

    protected function buildSizeChart(Product $product, string $locale): array
    {
        $rows = $this->measurementChartRows($product, $locale);
        $columns = $this->measurementChartColumns($rows, $locale);
        $guideImage = $this->measurementChartGuideImage($product);

        return [
            'title' => $locale === 'ar' ? __('front.products.size_chart') : __('front.products.size_chart'),
            'subtitle' => $locale === 'ar' ? __('front.products.size_guide') : __('front.products.size_guide'),
            'empty' => $locale === 'ar' ? __('front.products.size_chart_empty') : __('front.products.size_chart_empty'),
            'guide_image' => $guideImage,
            'columns' => $columns,
            'rows' => $rows,
        ];
    }

    protected function measurementChartGuideImage(Product $product): ?string
    {
        $group = $product->relationLoaded('measurementChartGroup')
            ? $product->getRelation('measurementChartGroup')
            : $product->measurementChartGroup()->first();

        $path = trim((string) ($group?->guide_image ?? ''));

        if ($path === '') {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    protected function measurementChartRows(Product $product, string $locale): array
    {
        $rows = $product->relationLoaded('measurementCharts')
            ? $product->getRelation('measurementCharts')
            : $product->measurementCharts()->get();

        return $rows->sortBy('size_code')
            ->map(function ($chart): array {
                return [
                    'size_code' => $chart->size_code ?? '',
                    'chest' => $chart->chest ?? '',
                    'shoulder' => $chart->shoulder ?? '',
                    'waist' => $chart->waist ?? '',
                    'length' => $chart->length ?? '',
                    'sleeve' => $chart->sleeve ?? '',
                    'collar' => $chart->collar ?? '',
                    'inside_leg' => $chart->inside_leg ?? '',
                    'waistline' => $chart->waistline ?? '',
                    'thigh_width' => $chart->thigh_width ?? '',
                    'leg_width' => $chart->leg_width ?? '',
                    'leg_length' => $chart->leg_length ?? '',
                ];
            })
            ->filter(fn (array $row): bool => filled($row['size_code'] ?? null))
            ->values()
            ->all();
    }

    protected function measurementChartColumns(array $rows, string $locale): array
    {
        $columns = [
            'size_code' => $locale === 'ar' ? __('front.products.size_chart_columns.size') : __('front.products.size_chart_columns.size'),
            'chest' => $locale === 'ar' ? __('front.products.size_chart_columns.chest') : __('front.products.size_chart_columns.chest'),
            'shoulder' => $locale === 'ar' ? __('front.products.size_chart_columns.shoulder') : __('front.products.size_chart_columns.shoulder'),
            'waist' => $locale === 'ar' ? __('front.products.size_chart_columns.waist') : __('front.products.size_chart_columns.waist'),
            'length' => $locale === 'ar' ? __('front.products.size_chart_columns.length') : __('front.products.size_chart_columns.length'),
            'sleeve' => $locale === 'ar' ? __('front.products.size_chart_columns.sleeve') : __('front.products.size_chart_columns.sleeve'),
            'collar' => $locale === 'ar' ? __('front.products.size_chart_columns.collar') : __('front.products.size_chart_columns.collar'),
            'inside_leg' => $locale === 'ar' ? __('front.products.size_chart_columns.inside_leg') : __('front.products.size_chart_columns.inside_leg'),
            'waistline' => $locale === 'ar' ? __('front.products.size_chart_columns.waistline') : __('front.products.size_chart_columns.waistline'),
            'thigh_width' => $locale === 'ar' ? __('front.products.size_chart_columns.thigh_width') : __('front.products.size_chart_columns.thigh_width'),
            'leg_width' => $locale === 'ar' ? __('front.products.size_chart_columns.leg_width') : __('front.products.size_chart_columns.leg_width'),
            'leg_length' => $locale === 'ar' ? __('front.products.size_chart_columns.leg_length') : __('front.products.size_chart_columns.leg_length'),
        ];

        return collect($columns)
            ->filter(function (string $label, string $key) use ($rows): bool {
                if ($key === 'size_code') {
                    return true;
                }

                foreach ($rows as $row) {
                    if (filled($row[$key] ?? null)) {
                        return true;
                    }
                }

                return false;
            })
            ->map(fn (string $label, string $key): array => [
                'key' => $key,
                'label' => $label,
            ])
            ->values()
            ->all();
    }

    protected function localizedValue(?string $ar, ?string $en, ?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();
        $value = $locale === 'ar' ? ($ar ?: $en) : ($en ?: $ar);

        return filled($value) ? $value : null;
    }
}
