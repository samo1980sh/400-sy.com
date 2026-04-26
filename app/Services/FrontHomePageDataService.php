<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Category;
use App\Models\CompanyHeaderImage;
use App\Models\CompanyNewsItem;
use App\Models\CompanyNewsTickerItem;
use App\Models\CompanyPage;
use App\Models\CompanySocialLink;
use App\Models\ContactInfoSetting;
use App\Models\ExchangeRateSetting;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class FrontHomePageDataService
{
    public function __construct(
        protected ProductImageCatalogService $imageCatalog,
        protected FrontCartService $cartService,
    ) {
    }

    public function build(?Category $category = null): array
    {
        $locale = app()->getLocale();
        $categoryIds = $category ? $this->collectCategoryBranchIds($category) : [];
        $cartState = $this->cartService->state();

        $productsQuery = Product::query()
            ->with([
                'productColors' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
                'variants.size',
                'productColors.variants.size',
                'measurementCharts',
                'category',
            ])
            ->where('show_web', true)
            ->where('is_active', true);

        if ($categoryIds !== []) {
            $productsQuery->whereIn('category_id', $categoryIds);
        }

        $trendingProducts = (clone $productsQuery)
            ->where('is_best_seller', true)
            ->orderByDesc('updated_at')
            ->limit(4)
            ->get();

        $newProducts = (clone $productsQuery)
            ->where('is_new', true)
            ->orderByDesc('updated_at')
            ->limit(4)
            ->get();

        return [
            'current_category' => $category,
            'locale' => $locale,
            'ticker_items' => $this->buildTickerItems($locale),
            'hero_slides' => $this->buildHeroSlides(),
            'collections' => $this->buildCollections($locale),
            'nav_categories' => $this->buildNavCategories(),
            'trending_products' => $this->buildProductCards($trendingProducts, $locale, 'trending'),
            'new_products' => $this->buildProductCards($newProducts, $locale, 'new'),
            'branches' => $this->buildBranches($locale),
            'contact' => ContactInfoSetting::query()->first(),
            'social_links' => $this->buildSocialLinks($locale),
            'footer_pages' => $this->buildFooterPages($locale),
            'news_items' => $this->buildNewsItems($locale),
            'quick_links' => $this->buildQuickLinks($locale),
            'currency_options' => $this->buildCurrencyOptions(),
            'cart_state' => $cartState,
            'cart_count' => $cartState['count'] ?? 0,
            'site_name' => __('front.brand'),
        ];
    }

    protected function buildTickerItems(string $locale): Collection
    {
        $items = CompanyNewsTickerItem::query()
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->limit(6)
            ->get()
            ->map(function (CompanyNewsTickerItem $item): array {
                return [
                    'text' => $this->localizedValue($item->text_ar ?? null, $item->text_en ?? null),
                    'link_url' => $item->link_url,
                ];
            })
            ->filter(fn (array $item): bool => filled($item['text'] ?? null))
            ->values();

        if ($items->isNotEmpty()) {
            return $items;
        }

        if ($locale === 'ar') {
            return collect([
                ['text' => __('front.announcement.one'), 'link_url' => null],
                ['text' => __('front.announcement.two'), 'link_url' => null],
                ['text' => __('front.announcement.three'), 'link_url' => null],
                ['text' => __('front.announcement.free_shipping'), 'link_url' => null],
            ]);
        }

        return collect([
            ['text' => __('front.announcement.one'), 'link_url' => null],
            ['text' => __('front.announcement.two'), 'link_url' => null],
            ['text' => __('front.announcement.three'), 'link_url' => null],
            ['text' => __('front.announcement.free_shipping'), 'link_url' => null],
        ]);
    }

    protected function buildHeroSlides(): Collection
    {
        $slides = CompanyHeaderImage::query()
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->get()
            ->map(function (CompanyHeaderImage $image): array {
                $imageUrl = filled($image->image) ? Storage::disk('public')->url($image->image) : null;
                $videoUrl = filled($image->video) ? Storage::disk('public')->url($image->video) : null;

                return [
                    'type' => filled($videoUrl) ? 'video' : 'image',
                    'image' => $imageUrl,
                    'video' => $videoUrl,
                    'poster' => $imageUrl,
                    'title' => $this->localizedValue($image->title_ar ?? null, $image->title_en ?? null) ?: __('front.brand'),
                    'link_url' => $image->link_url,
                ];
            })
            ->values();

        if ($slides->isNotEmpty()) {
            return $slides;
        }

        return collect([
            [
                'type' => 'video',
                'video' => asset('images/slider/400v.mp4'),
                'poster' => asset('images/slider/fashion-slideshow-05.jpg'),
                'title' => __('front.brand'),
                'link_url' => '#featured-products',
            ],
            [
                'type' => 'image',
                'image' => asset('images/slider/fashion-slideshow-05.jpg'),
                'poster' => asset('images/slider/fashion-slideshow-05.jpg'),
                'title' => __('front.brand'),
                'link_url' => '#featured-products',
            ],
        ]);
    }

    protected function buildCollections(string $locale): Collection
    {
        $collections = Category::query()
            ->whereNull('parent_id')
            ->where('show_in_home', true)
            ->orderBy('sort_order')
            ->get()
            ->map(function (Category $category) use ($locale): array {
                return [
                    'slug' => $category->slug,
                    'title' => $this->localizedValue($category->title_ar ?? null, $category->title_en ?? null, $locale) ?: __('front.brand'),
                    'image' => filled($category->image) ? Storage::disk('public')->url($category->image) : asset('images/collections/collection-circle-1.jpg'),
                    'link' => route('front.category', $category->slug),
                ];
            })
            ->values();

        if ($collections->isNotEmpty()) {
            return $collections;
        }

        return collect([
            ['slug' => 'men', 'title' => $this->demoText($locale, 'رجالي', 'Men'), 'image' => asset('images/collections/collection-circle-1.jpg'), 'link' => '#featured-products'],
            ['slug' => 'accessory', 'title' => $this->demoText($locale, 'إكسسوارات', 'Accessory'), 'image' => asset('images/collections/collection-circle-2.jpg'), 'link' => '#featured-products'],
            ['slug' => 'boys', 'title' => $this->demoText($locale, 'أولاد', 'Boys'), 'image' => asset('images/collections/collection-circle-3.jpg'), 'link' => '#featured-products'],
            ['slug' => 'teen', 'title' => $this->demoText($locale, 'شبابي', 'Teen'), 'image' => asset('images/collections/collection-circle-4.jpg'), 'link' => '#featured-products'],
            ['slug' => 'uniform', 'title' => $this->demoText($locale, 'زي موحد', 'Uniform'), 'image' => asset('images/collections/collection-circle-5.jpg'), 'link' => '#featured-products'],
            ['slug' => 'gift-card', 'title' => $this->demoText($locale, 'بطاقة هدية', 'Gift card'), 'image' => asset('images/collections/collection-circle-6.jpg'), 'link' => '#featured-products'],
        ]);
    }

    protected function buildNavCategories(): Collection
    {
        $categories = Category::query()
            ->whereNull('parent_id')
            ->whereNotIn('slug', ['offers', 'branches'])
            ->whereNotIn('title_en', ['Offers', 'Branches'])
            ->whereNotIn('title_ar', ['العروض', 'الفروع'])
            ->with([
                'children' => fn ($query) => $query
                    ->orderBy('sort_order')
                    ->orderBy('title_ar')
                    ->with([
                        'children' => fn ($childQuery) => $childQuery
                            ->orderBy('sort_order')
                            ->orderBy('title_ar'),
                    ]),
            ])
            ->orderBy('sort_order')
            ->orderBy('title_ar')
            ->get();

        if ($categories->isNotEmpty()) {
            return $categories;
        }

        return $this->demoNavCategories($locale);
    }

    protected function buildProductCards(Collection $products, string $locale, string $sectionKey): Collection
    {
        if ($products->isEmpty()) {
            return $this->demoProducts($sectionKey, $locale);
        }

        return $products->map(fn (Product $product): array => $this->presentProduct($product, $locale))->values();
    }

    public function presentProduct(Product $product, ?string $locale = null): array
    {
        $locale ??= app()->getLocale();

        $imagePath = $this->imageCatalog->mainImagePath($product);
        $imageUrl = filled($imagePath)
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
        $colors = $this->productColors($product, $locale, $currency, $pricing);
        $defaultColor = $colors[0] ?? [];
        $defaultSize = $this->selectDefaultSize($sizeOptions) ?: (array_key_first($sizePricing) ?: ($sizes[0] ?? null));
        $displayPricing = $defaultSize && isset($sizePricing[$defaultSize]) ? $sizePricing[$defaultSize] : [
            'price_current' => $pricing['current'],
            'compare_price' => $pricing['compare'],
            'price_current_label' => $pricing['current_label'],
            'compare_price_label' => $pricing['compare_label'],
        ];
        $gallery = collect(array_merge([$imageUrl], array_map(fn (array $color): string => $color['image'] ?? '', $colors)))
            ->filter()
            ->unique()
            ->take(4)
            ->values()
            ->all();
        $sizeChart = $this->buildSizeChart($product, $locale);
        $hasSizeChart = ! empty($sizeChart['rows']) && ! empty($sizeChart['columns']);

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

    protected function productColors(Product $product, string $locale, string $currency, array $pricing): array
    {
        $variantsByColor = $this->variantsByColor($product);

        return collect($this->imageCatalog->availableColors($product))
            ->take(4)
            ->values()
            ->map(function (array $color, int $index) use ($product, $locale, $currency, $pricing, $variantsByColor): array {
                $fallbackImage = asset('images/products/4brouwn1.jpg');
                $gallery = $color['detail_urls'] ?? $color['thumb_urls'] ?? [];
                $colorVariants = $variantsByColor->get((int) ($color['id'] ?? 0), collect());
                $defaultVariant = $this->selectDefaultVariant($colorVariants);
                $colorPricing = $this->resolveVariantPricing($product, $defaultVariant, $currency, $pricing);
                $sizeOptions = $this->buildSizeOptions($product, $colorVariants, $locale, $currency);
                $sizes = $this->sizeLabelsForVariants($sizeOptions, $locale);
                $defaultSize = $this->selectDefaultSize($sizeOptions) ?: ($this->variantSizeLabel($defaultVariant, $locale) ?: ($sizes[0] ?? null));

                return [
                    'id' => (int) ($color['id'] ?? 0),
                    'name' => $this->localizedValue($color['name_ar'] ?? null, $color['name_en'] ?? null, $locale) ?: ($color['name'] ?? '-'),
                    'class_name' => $color['class_name'] ?? 'four-Black',
                    'color_code' => $color['color_code'] ?? null,
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
        if ($product->relationLoaded('variants')) {
            $variants = $product->getRelation('variants');

            if ($variants instanceof Collection) {
                return $variants->values();
            }
        }

        return $product->variants()
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

        return [
            'title' => $locale === 'ar' ? __('front.products.size_chart') : __('front.products.size_chart'),
            'subtitle' => $locale === 'ar' ? __('front.products.size_guide') : __('front.products.size_guide'),
            'empty' => $locale === 'ar' ? __('front.products.size_chart_empty') : __('front.products.size_chart_empty'),
            'columns' => $columns,
            'rows' => $rows,
        ];
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

    protected function buildBranches(string $locale): Collection
    {
        $branches = Branch::query()
            ->with('category')
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->limit(3)
            ->get()
            ->map(function (Branch $branch) use ($locale): array {
                return [
                    'name' => $this->localizedValue($branch->name_ar ?? null, $branch->name_en ?? null, $locale) ?: __('front.branches.untitled'),
                    'address' => trim((string) $this->localizedValue($branch->address_ar ?? null, $branch->address_en ?? null, $locale)),
                    'hours' => trim((string) $this->localizedValue($branch->description_ar ?? null, $branch->description_en ?? null, $locale)),
                    'image' => filled($branch->main_image) ? Storage::disk('public')->url($branch->main_image) : asset('images/shop/store/ourstore1.png'),
                    'phone' => $branch->phone ?: $branch->mobile ?: '',
                    'email' => $branch->email ?: '',
                ];
            })
            ->values();

        if ($branches->isNotEmpty()) {
            return $branches;
        }

        return collect([
            [
                'name' => $this->demoText($locale, 'الفرع الأول', 'First branch'),
                'address' => $this->demoText($locale, 'اختبر عنوان الفرع وموقعه هنا', 'Experience the branch address and location'),
                'hours' => $this->demoText($locale, 'السبت - الخميس، 8:30 صباحًا - 10:30 مساءً' . "\n" . 'السبت، 8:30 صباحًا - 10:30 مساءً' . "\n" . 'الجمعة مغلق', 'Sat - Thu, 8:30am - 10:30pm' . "\n" . 'Saturday, 8:30am - 10:30pm' . "\n" . 'Friday Closed'),
                'image' => asset('images/shop/store/ourstore1.png'),
                'phone' => '+963 11 691 2400',
                'email' => 'info.sy@400-online.com',
            ],
            [
                'name' => $this->demoText($locale, 'الفرع الثاني', 'Branch Two'),
                'address' => $this->demoText($locale, 'اختبر عنوان الفرع وموقعه هنا', 'Experience the branch address and location'),
                'hours' => $this->demoText($locale, 'السبت - الخميس، 8:30 صباحًا - 10:30 مساءً' . "\n" . 'السبت، 8:30 صباحًا - 10:30 مساءً' . "\n" . 'الجمعة مغلق', 'Sat - Thu, 8:30am - 10:30pm' . "\n" . 'Saturday, 8:30am - 10:30pm' . "\n" . 'Friday Closed'),
                'image' => asset('images/shop/store/ourstore2.png'),
                'phone' => '+963 11 691 2400',
                'email' => 'info.sy@400-online.com',
            ],
            [
                'name' => $this->demoText($locale, 'الفرع الثالث', 'Third branch'),
                'address' => $this->demoText($locale, 'اختبر عنوان الفرع وموقعه هنا', 'Experience the branch address and location'),
                'hours' => $this->demoText($locale, 'السبت - الخميس، 8:30 صباحًا - 10:30 مساءً' . "\n" . 'السبت، 8:30 صباحًا - 10:30 مساءً' . "\n" . 'الجمعة مغلق', 'Sat - Thu, 8:30am - 10:30pm' . "\n" . 'Saturday, 8:30am - 10:30pm' . "\n" . 'Friday Closed'),
                'image' => asset('images/shop/store/ourstore3.png'),
                'phone' => '+963 11 691 2400',
                'email' => 'info.sy@400-online.com',
            ],
        ]);
    }

    protected function buildSocialLinks(string $locale): Collection
    {
        $socialLinks = CompanySocialLink::query()
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->limit(6)
            ->get()
            ->map(function (CompanySocialLink $socialLink) use ($locale): array {
                return [
                    'title' => $this->localizedValue($socialLink->title_ar ?? null, $socialLink->title_en ?? null, $locale) ?: $socialLink->platform_key,
                    'url' => $socialLink->url ?: '#',
                    'anchor_class' => $this->socialPresentation($socialLink->platform_key, $socialLink->icon)['anchor_class'],
                    'icon_class' => $this->socialPresentation($socialLink->platform_key, $socialLink->icon)['icon_class'],
                ];
            })
            ->values();

        if ($socialLinks->isNotEmpty()) {
            return $socialLinks;
        }

        return collect([
            ['title' => 'Instagram', 'url' => '#', 'anchor_class' => 'social-instagram', 'icon_class' => 'icon-instagram'],
            ['title' => 'Facebook', 'url' => '#', 'anchor_class' => 'social-facebook', 'icon_class' => 'icon-fb'],
            ['title' => 'WhatsApp', 'url' => '#', 'anchor_class' => 'social-whatsapp', 'icon_class' => 'icon-whatsapp'],
        ]);
    }

    protected function buildFooterPages(string $locale): Collection
    {
        $pages = CompanyPage::query()
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->limit(4)
            ->get()
            ->map(function (CompanyPage $page) use ($locale): array {
                return [
                    'title' => $this->localizedValue($page->title_ar ?? null, $page->title_en ?? null, $locale),
                    'url' => filled($page->slug) ? route('front.pages.show', $page->slug) : route('front.pages.show', 'page'),
                ];
            })
            ->filter(fn (array $page): bool => filled($page['title'] ?? null))
            ->values();

        if ($pages->isNotEmpty()) {
            return $pages;
        }

        return collect([
            ['title' => $this->demoText($locale, 'من نحن', 'About Us'), 'url' => route('front.pages.show', 'about-us')],
            ['title' => $this->demoText($locale, 'الأخبار والفعاليات', 'News and Event'), 'url' => route('front.pages.show', 'news-and-events')],
            ['title' => $this->demoText($locale, 'المخزون', 'Stocks'), 'url' => route('front.pages.show', 'stocks')],
            ['title' => $this->demoText($locale, 'الأسئلة الشائعة', 'Faq'), 'url' => route('front.pages.show', 'faq')],
            ['title' => $this->demoText($locale, 'سياسة الاستبدال والإرجاع', 'Exchange and return policy'), 'url' => route('front.pages.show', 'exchange-and-return-policy')],
            ['title' => $this->demoText($locale, 'تواصل معنا', 'Contact Us'), 'url' => route('front.pages.show', 'contact-us')],
        ]);
    }

    protected function buildNewsItems(string $locale): Collection
    {
        $items = CompanyNewsItem::query()
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->limit(3)
            ->get()
            ->map(function (CompanyNewsItem $item) use ($locale): array {
                return [
                    'title' => $this->localizedValue($item->title_ar ?? null, $item->title_en ?? null, $locale),
                    'excerpt' => $this->localizedValue($item->excerpt_ar ?? null, $item->excerpt_en ?? null, $locale),
                    'date' => $item->event_date instanceof Carbon ? $item->event_date->translatedFormat('d M Y') : null,
                    'image' => filled($item->main_image) ? Storage::disk('public')->url($item->main_image) : asset('images/collections/collection-1.jpg'),
                    'url' => '#',
                ];
            })
            ->filter(fn (array $item): bool => filled($item['title'] ?? null))
            ->values();

        if ($items->isNotEmpty()) {
            return $items;
        }

        return collect([
            ['title' => __('front.news.item_1'), 'excerpt' => null, 'date' => __('front.news.date_1'), 'image' => asset('images/collections/collection-1.jpg'), 'url' => '#'],
            ['title' => __('front.news.item_2'), 'excerpt' => null, 'date' => __('front.news.date_2'), 'image' => asset('images/collections/collection-1.jpg'), 'url' => '#'],
            ['title' => __('front.news.item_3'), 'excerpt' => null, 'date' => __('front.news.date_3'), 'image' => asset('images/collections/collection-1.jpg'), 'url' => '#'],
        ]);
    }

    protected function buildQuickLinks(string $locale): Collection
    {
        $links = $this->buildNavCategories()
            ->take(4)
            ->map(function ($category) use ($locale): array {
                return [
                    'label' => $this->localizedValue($category->title_ar ?? null, $category->title_en ?? null, $locale) ?: ($category->title_ar ?? $category->title_en ?? ''),
                    'href' => filled($category->slug ?? null) ? route('front.category', $category->slug) : '#',
                ];
            })
            ->values();

        $links->push([
            'label' => __('front.nav.offers'),
            'href' => route('front.home') . '#featured-products',
        ]);

        $links->push([
            'label' => __('front.nav.branches'),
            'href' => route('front.home') . '#store-locations',
        ]);

        return $links;
    }

    protected function buildCurrencyOptions(): array
    {
        $settings = ExchangeRateSetting::singleton();
        $selectedCurrency = strtoupper((string) (
            session('selectedCurrency')
            ?: request()->cookie('selectedCurrency')
            ?: 'SYP'
        ));

        $options = [
            [
                'value' => 'SYP',
                'label' => 'SYP (LS)',
                'selected' => $selectedCurrency === 'SYP',
                'rate' => 1,
                'symbol' => 'SYP',
            ],
        ];

        if ($settings->show_usd) {
            $options[] = [
                'value' => 'USD',
                'label' => 'USD ($)',
                'selected' => $selectedCurrency === 'USD',
                'rate' => (float) ($settings->usd_syp_rate ?: 0),
                'symbol' => '$',
            ];
        }

        if ($settings->show_eur) {
            $options[] = [
                'value' => 'EUR',
                'label' => 'EUR (€)',
                'selected' => $selectedCurrency === 'EUR',
                'rate' => (float) ($settings->eur_syp_rate ?: 0),
                'symbol' => '€',
            ];
        }

        if (! collect($options)->contains(fn (array $option): bool => (bool) ($option['selected'] ?? false))) {
            $options[0]['selected'] = true;
        }

        return $options;
    }

    protected function collectCategoryBranchIds(Category $category): array
    {
        $ids = [$category->getKey()];

        foreach ($category->children ?? collect() as $child) {
            if (! $child instanceof Category) {
                continue;
            }

            $ids = array_merge($ids, $this->collectCategoryBranchIds($child));
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }

    protected function localizedValue(?string $ar, ?string $en, ?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();
        $value = $locale === 'ar' ? ($ar ?: $en) : ($en ?: $ar);

        return filled($value) ? $value : null;
    }

    protected function socialPresentation(string $platformKey, string $iconValue = ''): array
    {
        $platformKey = strtolower(trim($platformKey));
        $iconValue = strtolower(trim($iconValue));

        $map = [
            'facebook' => ['anchor_class' => 'social-facebook', 'icon_class' => 'icon-fb'],
            'instagram' => ['anchor_class' => 'social-instagram', 'icon_class' => 'icon-instagram'],
            'whatsapp' => ['anchor_class' => 'social-whatsapp', 'icon_class' => 'icon-whatsapp'],
            'youtube' => ['anchor_class' => 'social-youtube', 'icon_class' => 'icon-youtube'],
            'x' => ['anchor_class' => 'social-twiter', 'icon_class' => 'icon-twitter'],
            'twitter' => ['anchor_class' => 'social-twiter', 'icon_class' => 'icon-twitter'],
            'tiktok' => ['anchor_class' => 'social-tiktok', 'icon_class' => 'icon-tiktok'],
            'snapchat' => ['anchor_class' => 'social-snapchat', 'icon_class' => 'icon-snapchat'],
            'linkedin' => ['anchor_class' => 'social-linkedin', 'icon_class' => 'icon-linkedin'],
        ];

        if (isset($map[$platformKey])) {
            return $map[$platformKey];
        }

        if (isset($map[$iconValue])) {
            return $map[$iconValue];
        }

        return [
            'anchor_class' => 'social-facebook',
            'icon_class' => 'icon-link',
        ];
    }

    protected function demoNavCategories(string $locale): Collection
    {
        return collect([
            $this->demoNavNode([
                'slug' => 'men',
                'title_en' => 'Men',
                'title_ar' => 'رجالي',
                'image' => asset('images/collections/collection-1.jpg'),
                'children' => [
                    [
                        'slug' => 'suit-and-blazer',
                        'title_en' => 'Suit and blazer',
                        'title_ar' => 'بدلات وجاكيت',
                        'children' => [
                            ['slug' => 'blazer', 'title_en' => 'Blazer', 'title_ar' => 'بليزر'],
                            ['slug' => 'formal-suit', 'title_en' => 'Formal suit', 'title_ar' => 'بدلة رسمية'],
                            ['slug' => 'casual-set', 'title_en' => 'Casual set', 'title_ar' => 'طقم كاجوال'],
                            ['slug' => 'tuxedo', 'title_en' => 'Tuxedo', 'title_ar' => 'سموكن'],
                            ['slug' => 'special-size-suit', 'title_en' => 'Special size suit', 'title_ar' => 'بدلات مقاسات خاصة'],
                            ['slug' => 'formal-vest', 'title_en' => 'Formal vest', 'title_ar' => 'صدرية رسمية'],
                        ],
                    ],
                    [
                        'slug' => 'pants',
                        'title_en' => 'Pants',
                        'title_ar' => 'بناطيل',
                        'children' => [
                            ['slug' => 'pants-jeans', 'title_en' => 'Pants - Jeans', 'title_ar' => 'جينز'],
                            ['slug' => 'pants-casual', 'title_en' => 'Pants - Casual', 'title_ar' => 'كاجوال'],
                            ['slug' => 'pants-formal', 'title_en' => 'Pants - Formal', 'title_ar' => 'رسمي'],
                            ['slug' => 'pants-velvet', 'title_en' => 'Pants - Velvet', 'title_ar' => 'مخمل'],
                            ['slug' => 'pants-tracksuit', 'title_en' => 'Pants - Tracksuit', 'title_ar' => 'شروال رياضي'],
                        ],
                    ],
                    [
                        'slug' => 'shirt',
                        'title_en' => 'Shirt',
                        'title_ar' => 'قمصان',
                        'children' => [
                            ['slug' => 'shirt-casual', 'title_en' => 'Shirt - Casual', 'title_ar' => 'كاجوال'],
                            ['slug' => 'shirt-formal', 'title_en' => 'Shirt - Formal', 'title_ar' => 'رسمي'],
                            ['slug' => 'shirt-plain', 'title_en' => 'Shirt - Plain', 'title_ar' => 'سادة'],
                            ['slug' => 'shirt-warm', 'title_en' => 'Shirt - warm', 'title_ar' => 'شتوي'],
                        ],
                    ],
                ],
            ]),
            $this->demoNavNode([
                'slug' => 'accessorys',
                'title_en' => 'Accessorys',
                'title_ar' => 'إكسسوارات',
                'image' => asset('images/collections/collection-2.jpg'),
                'children' => [
                    [
                        'slug' => 'shoes',
                        'title_en' => 'Shoes',
                        'title_ar' => 'أحذية',
                        'children' => [
                            ['slug' => 'shoes-formal', 'title_en' => 'Shoes - Formal', 'title_ar' => 'رسمي'],
                            ['slug' => 'shoes-sneakers', 'title_en' => 'Shoes - Sneakers', 'title_ar' => 'رياضي'],
                            ['slug' => 'sleeper', 'title_en' => 'Sleeper', 'title_ar' => 'صندل'],
                            ['slug' => 'warm-shoes', 'title_en' => 'Warm shoes', 'title_ar' => 'شتوي'],
                        ],
                    ],
                ],
            ]),
            $this->demoNavNode([
                'slug' => 'boys',
                'title_en' => 'Boys',
                'title_ar' => 'أولاد',
                'image' => asset('images/collections/collection-3.jpg'),
                'children' => [
                    [
                        'slug' => 'boys-products',
                        'title_en' => 'Products',
                        'title_ar' => 'منتجات',
                        'children' => [
                            ['slug' => 'jeans', 'title_en' => 'Jeans', 'title_ar' => 'جينز'],
                            ['slug' => 'casual-pants', 'title_en' => 'Casual pants', 'title_ar' => 'بناطيل كاجوال'],
                        ],
                    ],
                ],
            ]),
            $this->demoNavNode([
                'slug' => 'teen',
                'title_en' => 'Teen',
                'title_ar' => 'مراهقين',
                'image' => asset('images/collections/collection-4.jpg'),
                'children' => [
                    [
                        'slug' => 'sweater-and-tracksuit',
                        'title_en' => 'Sweater and tracksuit',
                        'title_ar' => 'سويتير وشروال',
                        'children' => [
                            ['slug' => 'fleece-sweater', 'title_en' => 'Fleece sweater', 'title_ar' => 'سويتير صوف'],
                            ['slug' => 'knitted-sweater', 'title_en' => 'Knitted sweater', 'title_ar' => 'سويتير محبوك'],
                            ['slug' => 'winter-tracksuit', 'title_en' => 'Winter tracksuit', 'title_ar' => 'بدلة شتوية'],
                        ],
                    ],
                ],
            ]),
            $this->demoNavNode([
                'slug' => 'uniform',
                'title_en' => 'Uniform',
                'title_ar' => 'زي موحد',
                'image' => asset('images/collections/collection-5.jpg'),
                'children' => [
                    [
                        'slug' => 'uniform-products',
                        'title_en' => 'Uniform',
                        'title_ar' => 'زي موحد',
                        'children' => [
                            ['slug' => 'school-uniform', 'title_en' => 'School uniform', 'title_ar' => 'مدرسي'],
                            ['slug' => 'work-uniform', 'title_en' => 'Work uniform', 'title_ar' => 'عملي'],
                        ],
                    ],
                ],
            ]),
        ]);
    }

    protected function demoNavNode(array $data): object
    {
        $node = new \stdClass();
        $node->id = $data['id'] ?? abs(crc32($data['slug'] ?? ($data['title_en'] ?? uniqid('', true))));
        $node->slug = $data['slug'] ?? '';
        $node->title_ar = $data['title_ar'] ?? null;
        $node->title_en = $data['title_en'] ?? null;
        $node->image = $data['image'] ?? null;
        $node->children = collect($data['children'] ?? [])
            ->map(fn (array $child): object => $this->demoNavNode($child));

        return $node;
    }

    protected function demoProducts(string $sectionKey, string $locale): Collection
    {
        $items = $sectionKey === 'trending'
            ? [
                [
                    'title' => $this->demoText($locale, 'جاكيت مبطن', 'Puffer Jacket'),
                    'image' => asset('images/products/4brouwn1.jpg'),
                    'list_url' => route('front.products.show', 'placeholder-product'),
                    'detail_url' => route('front.products.show', 'placeholder-product'),
                    'price_label' => '850,000 SYP',
                    'base_price' => 850000,
                    'base_currency' => 'SYP',
                    'sizes' => ['XL', '2XL'],
                    'colors' => [
                        ['name' => $this->demoText($locale, 'بني', 'Brown'), 'class_name' => 'four-Brouwn', 'image' => asset('images/products/4brouwn1.jpg')],
                        ['name' => $this->demoText($locale, 'أزرق كحلي', 'Navy Blue'), 'class_name' => 'four-Navy-Blue', 'image' => asset('images/products/4navyblue1.jpg')],
                    ],
                ],
                [
                    'title' => $this->demoText($locale, 'بدلة رسمية', 'Slub Formal Suit'),
                    'image' => asset('images/products/4indigo2.jpg'),
                    'list_url' => route('front.products.show', 'placeholder-product'),
                    'detail_url' => route('front.products.show', 'placeholder-product'),
                    'price_label' => '1,100,000 SYP',
                    'base_price' => 1100000,
                    'base_currency' => 'SYP',
                    'sizes' => ['50', '52', '54'],
                    'colors' => [
                        ['name' => $this->demoText($locale, 'نيلي', 'Indigo'), 'class_name' => 'four-Indigo', 'image' => asset('images/products/4indigo2.jpg')],
                        ['name' => $this->demoText($locale, 'أزرق كحلي', 'Navy Blue'), 'class_name' => 'four-Navy-Blue', 'image' => asset('images/products/4navyblue2.jpg')],
                        ['name' => $this->demoText($locale, 'رمادي', 'Grey'), 'class_name' => 'four-Grey', 'image' => asset('images/products/4grey2.jpg')],
                        ['name' => $this->demoText($locale, 'بترولي', 'Petro'), 'class_name' => 'four-Petro', 'image' => asset('images/products/4petrol2.jpg')],
                    ],
                ],
                [
                    'title' => $this->demoText($locale, 'تكسيدو S5H127', 'Tuxedo S5H127'),
                    'image' => asset('images/products/4black3.jpg'),
                    'list_url' => route('front.products.show', 'placeholder-product'),
                    'detail_url' => route('front.products.show', 'placeholder-product'),
                    'price_label' => '1,575,000 SYP',
                    'base_price' => 1575000,
                    'base_currency' => 'SYP',
                    'sizes' => ['48', '50', '52', '54', '56'],
                    'colors' => [
                        ['name' => $this->demoText($locale, 'أسود', 'Black'), 'class_name' => 'four-Black', 'image' => asset('images/products/4black3.jpg')],
                    ],
                ],
                [
                    'title' => $this->demoText($locale, 'جاكيت قطيفة', 'Velvet Jacket'),
                    'image' => asset('images/products/4brouwn4.jpg'),
                    'list_url' => route('front.products.show', 'placeholder-product'),
                    'detail_url' => route('front.products.show', 'placeholder-product'),
                    'price_label' => '775,000 SYP',
                    'base_price' => 775000,
                    'base_currency' => 'SYP',
                    'sizes' => ['M', 'L', 'XL', '2XL'],
                    'colors' => [
                        ['name' => $this->demoText($locale, 'بني', 'Brown'), 'class_name' => 'four-Brouwn', 'image' => asset('images/products/4brouwn4.jpg')],
                        ['name' => $this->demoText($locale, 'أسود', 'Black'), 'class_name' => 'four-Black', 'image' => asset('images/products/4black4.jpg')],
                        ['name' => $this->demoText($locale, 'أخضر داكن', 'Dark Green'), 'class_name' => 'four-Dark-Green', 'image' => asset('images/products/4darkgreen4.jpg')],
                        ['name' => $this->demoText($locale, 'أزرق كحلي', 'Navy Blue'), 'class_name' => 'four-Navy-Blue', 'image' => asset('images/products/4navyblue4.jpg')],
                    ],
                ],
            ]
            : [
                [
                    'title' => $this->demoText($locale, 'جاكيت غوخ', 'Gogh Jacket'),
                    'image' => asset('images/products/4navyblue5.jpg'),
                    'list_url' => route('front.products.show', 'placeholder-product'),
                    'detail_url' => route('front.products.show', 'placeholder-product'),
                    'price_label' => '850,000 SYP',
                    'base_price' => 850000,
                    'base_currency' => 'SYP',
                    'sizes' => ['48', '50', '52', '54', '56', '58'],
                    'colors' => [
                        ['name' => $this->demoText($locale, 'أزرق كحلي', 'Navy Blue'), 'class_name' => 'four-Navy-Blue', 'image' => asset('images/products/4navyblue5.jpg')],
                        ['name' => $this->demoText($locale, 'شوكولا', 'Choco'), 'class_name' => 'four-Choco', 'image' => asset('images/products/4choco5.jpg')],
                        ['name' => $this->demoText($locale, 'رمادي', 'Grey'), 'class_name' => 'four-Grey', 'image' => asset('images/products/4grey5.jpg')],
                        ['name' => $this->demoText($locale, 'فحمي', 'Charcoal'), 'class_name' => 'four-Charcoal', 'image' => asset('images/products/4charcoal5.jpg')],
                    ],
                ],
                [
                    'title' => $this->demoText($locale, 'جاكيت جلد طبيعي', 'Genuine Leather Jacket'),
                    'image' => asset('images/products/4brouwn6.jpg'),
                    'list_url' => route('front.products.show', 'placeholder-product'),
                    'detail_url' => route('front.products.show', 'placeholder-product'),
                    'price_label' => '1,250,000 SYP',
                    'base_price' => 1250000,
                    'base_currency' => 'SYP',
                    'sizes' => ['M', 'L', 'XL', '2XL'],
                    'colors' => [
                        ['name' => $this->demoText($locale, 'بني', 'Brown'), 'class_name' => 'four-Brouwn', 'image' => asset('images/products/4brouwn6.jpg')],
                        ['name' => $this->demoText($locale, 'أسود', 'Black'), 'class_name' => 'four-Black', 'image' => asset('images/products/4black6.jpg')],
                    ],
                ],
                [
                    'title' => $this->demoText($locale, 'جاكيت مبطن', 'Puffer Jacket'),
                    'image' => asset('images/products/4black7.jpg'),
                    'list_url' => route('front.products.show', 'placeholder-product'),
                    'detail_url' => route('front.products.show', 'placeholder-product'),
                    'price_label' => '925,000 SYP',
                    'base_price' => 925000,
                    'base_currency' => 'SYP',
                    'sizes' => ['M', 'L', 'XL', '2XL', '3XL'],
                    'colors' => [
                        ['name' => $this->demoText($locale, 'أسود', 'Black'), 'class_name' => 'four-Black', 'image' => asset('images/products/4black7.jpg')],
                        ['name' => $this->demoText($locale, 'بني', 'Brown'), 'class_name' => 'four-Brouwn', 'image' => asset('images/products/4brouwn7.jpg')],
                        ['name' => $this->demoText($locale, 'أزرق كحلي', 'Navy Blue'), 'class_name' => 'four-Navy-Blue', 'image' => asset('images/products/4navyblue7.jpg')],
                    ],
                ],
                [
                    'title' => $this->demoText($locale, 'سويتير محبوك', 'Knitted Sweater'),
                    'image' => asset('images/products/4brick8.jpg'),
                    'list_url' => route('front.products.show', 'placeholder-product'),
                    'detail_url' => route('front.products.show', 'placeholder-product'),
                    'price_label' => '350,000 SYP',
                    'base_price' => 350000,
                    'base_currency' => 'SYP',
                    'sizes' => ['M', 'L', 'XL', '2XL'],
                    'colors' => [
                        ['name' => $this->demoText($locale, 'طوبي', 'Brick'), 'class_name' => 'four-Brick', 'image' => asset('images/products/4brick8.jpg')],
                        ['name' => $this->demoText($locale, 'أخضر داكن', 'Dark Green'), 'class_name' => 'four-Dark-Green', 'image' => asset('images/products/4darkgreen8.jpg')],
                        ['name' => $this->demoText($locale, 'فحمي', 'Charcoal'), 'class_name' => 'four-Charcoal', 'image' => asset('images/products/4charcoal8.jpg')],
                        ['name' => $this->demoText($locale, 'كرزي', 'Cherry'), 'class_name' => 'four-Cherry', 'image' => asset('images/products/4cherry8.jpg')],
                        ['name' => $this->demoText($locale, 'بترولي', 'Petrol'), 'class_name' => 'four-Petrol', 'image' => asset('images/products/4petrol8.jpg')],
                    ],
                ],
            ];

        foreach ($items as $index => $item) {
            $slug = sprintf('%s-demo-%d', $sectionKey, $index + 1);
            $items[$index]['slug'] = $slug;
            $items[$index]['url'] = route('front.products.show', $slug);
            $items[$index]['list_url'] = $items[$index]['url'];
            $items[$index]['detail_url'] = $items[$index]['url'];
        }

        return collect($items);
    }

    protected function demoText(string $locale, string $ar, string $en): string
    {
        return $locale === 'ar' ? $ar : $en;
    }
}
