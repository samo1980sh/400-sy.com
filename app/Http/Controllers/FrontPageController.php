<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CompanyPage;
use App\Models\ExchangeRateSetting;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\ProductVariant;
use App\Models\Size;
use App\Services\FrontCartService;
use App\Services\FrontHomePageDataService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

class FrontPageController extends Controller
{
    public function __construct(protected FrontHomePageDataService $homePageData)
    {
    }

    public function home(): View
    {
        return view('frontend.pages.home', $this->homePageData->build());
    }

    public function category(Request $request, string $slug): View|JsonResponse
    {
        $category = Category::query()
            ->where('slug', $slug)
            ->firstOrFail();

        return $this->renderProductsListing($category, $request);
    }

    public function productsIndex(Request $request): View|JsonResponse
    {
        $sort = $this->normalizeProductsSort((string) $request->query('sort', 'featured'));
        $firstCategorySlug = $this->requestList($request, 'categories', 'category')[0] ?? '';
        $category = null;

        if ($firstCategorySlug !== '') {
            $category = Category::query()
                ->where('slug', $firstCategorySlug)
                ->first();
        }

        return $this->renderProductsListing($category, $request, $sort);
    }

    protected function renderProductsListing(?Category $category = null, ?Request $request = null, string $sort = 'featured'): View|JsonResponse
    {
        $request ??= request();

        $shell = $this->homePageData->build();
        $locale = app()->getLocale();
        $selectedCategorySlugs = $this->resolveSelectedCategorySlugs($request, $category);
        $selectedCategoryModels = $this->resolveSelectedCategoryModels($selectedCategorySlugs);
        $selectedColors = $this->requestList($request, 'colors', 'color');
        $selectedSizes = $this->requestList($request, 'sizes', 'size');
        [$minPrice, $maxPrice] = $this->requestPriceRange($request);
        $queryWithoutFilters = Arr::except($request->query(), ['page', 'min_price', 'max_price', 'price', 'color', 'colors', 'size', 'sizes', 'category', 'categories', 'filter_ajax', 'load_more', 'sort']);
        $resetUrl = $request->url();
        $primaryCategory = $selectedCategoryModels->count() === 1 ? $selectedCategoryModels->first() : $category;
        $categoryTrail = $primaryCategory instanceof Category ? $primaryCategory->breadcrumbTrail() : collect();

        $filters = [
            'category_ids' => $this->collectCategoriesBranchIds($selectedCategoryModels),
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
            'colors' => $selectedColors,
            'sizes' => $selectedSizes,
        ];

        $query = $this->newProductsListingQuery();
        $this->applyProductsFilters($query, $filters);
        $this->applyProductsSort($query, $sort);

        $paginator = $query->paginate(16)->appends(
            Arr::except($request->query(), ['page', 'load_more', 'filter_ajax'])
        );
        $products = $paginator->getCollection()
            ->map(fn (Product $product): array => $this->homePageData->presentProduct($product, $locale))
            ->values();

        $paginator->setCollection($products);

        if ($request->boolean('load_more')) {
            return response()->json([
                'html' => view('frontend.partials.product-grid-items', [
                    'products' => $paginator,
                ])->render(),
                'has_more' => $paginator->hasMorePages(),
                'next_page_url' => $paginator->nextPageUrl(),
            ]);
        }

        $breadcrumbItems = [
            ['label' => __('front.nav.home'), 'url' => route('front.home')],
            ['label' => $locale === 'ar' ? 'المنتجات' : 'Products', 'url' => route('front.products.index')],
        ];

        if ($primaryCategory instanceof Category && $selectedCategoryModels->count() === 1) {
            foreach ($categoryTrail as $trailCategory) {
                $breadcrumbItems[] = [
                    'label' => $locale === 'ar'
                        ? ($trailCategory->title_ar ?: $trailCategory->title_en ?: $trailCategory->slug)
                        : ($trailCategory->title_en ?: $trailCategory->title_ar ?: $trailCategory->slug),
                    'url' => route('front.category', $trailCategory->slug),
                ];
            }
        }

        $categories = $this->filterCategoriesTree();

        $filterCategories = $this->buildFilterCategories($categories, [
            'category_ids' => [],
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
            'colors' => $selectedColors,
            'sizes' => $selectedSizes,
        ]);
        $filterColorOptions = $this->buildColorOptions([
            'category_ids' => $filters['category_ids'],
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
            'colors' => [],
            'sizes' => $selectedSizes,
        ], $locale, $selectedColors);
        $filterSizeOptions = $this->buildSizeOptions([
            'category_ids' => $filters['category_ids'],
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
            'colors' => $selectedColors,
            'sizes' => [],
        ], $locale, $selectedSizes);
        $filterPriceStats = $this->buildPriceStats([
            'category_ids' => $filters['category_ids'],
            'min_price' => null,
            'max_price' => null,
            'colors' => $selectedColors,
            'sizes' => $selectedSizes,
        ], $minPrice, $maxPrice);
        $activeFilterChips = $this->buildActiveFilterChips(
            $selectedCategoryModels,
            $selectedColors,
            $selectedSizes,
            $filterPriceStats,
            $minPrice,
            $maxPrice,
            $locale,
        );

        $pageTitle = $locale === 'ar' ? 'المنتجات' : 'Products';

        if ($primaryCategory instanceof Category && $selectedCategoryModels->count() === 1) {
            $pageTitle = $locale === 'ar'
                ? ($primaryCategory->title_ar ?: $primaryCategory->title_en ?: $pageTitle)
                : ($primaryCategory->title_en ?: $primaryCategory->title_ar ?: $pageTitle);
        }

        $sortOptions = [
            'featured' => $locale === 'ar' ? 'مميز' : 'Featured',
            'best_selling' => $locale === 'ar' ? 'الأكثر مبيعًا' : 'Best selling',
            'price_asc' => $locale === 'ar' ? 'السعر: من الأقل للأعلى' : 'Price, low to high',
            'price_desc' => $locale === 'ar' ? 'السعر: من الأعلى للأقل' : 'Price, high to low',
            'oldest' => $locale === 'ar' ? 'الأقدم أولًا' : 'Date, old to new',
            'newest' => $locale === 'ar' ? 'الأحدث أولًا' : 'Date, new to old',
        ];

        $viewData = array_merge($shell, [
            'page_title' => $pageTitle,
            'page_subtitle' => $primaryCategory instanceof Category && $selectedCategoryModels->count() === 1
                ? ($locale === 'ar' ? 'تصفح منتجات هذا التصنيف' : 'Browse products in this category')
                : ($locale === 'ar' ? 'تصفح مجموعة المنتجات مع الفلتر والفرز' : 'Browse the product catalog with filters and sorting'),
            'breadcrumb_items' => $breadcrumbItems,
            'products' => $paginator,
            'selected_sort' => $sort,
            'sort_options' => $sortOptions,
            'selected_category_slugs' => $selectedCategorySlugs,
            'selected_min_price' => $minPrice,
            'selected_max_price' => $maxPrice,
            'selected_colors' => $selectedColors,
            'selected_sizes' => $selectedSizes,
            'filter_categories' => $filterCategories,
            'filter_color_options' => $filterColorOptions,
            'filter_size_options' => $filterSizeOptions,
            'filter_price_stats' => $filterPriceStats,
            'active_filter_chips' => $activeFilterChips,
            'filter_reset_url' => $resetUrl,
        ]);

        if ($request->ajax() || $request->boolean('filter_ajax')) {
            return response()->json([
                'toolbar_html' => view('frontend.partials.shop-toolbar', [
                    'result_count' => $paginator->total(),
                    'sort_options' => $sortOptions,
                    'selected_sort' => $sort,
                ])->render(),
                'filter_html' => view('frontend.partials.shop-filter', $viewData)->render(),
                'products_html' => view('frontend.partials.product-grid', [
                    'products' => $paginator,
                    'active_filter_chips' => $activeFilterChips,
                    'filter_reset_url' => $resetUrl,
                ])->render(),
                'loadmore_html' => view('frontend.partials.loadmore', [
                    'products' => $paginator,
                ])->render(),
                'result_count' => $paginator->total(),
                'next_page_url' => $paginator->nextPageUrl(),
            ]);
        }

        return view('frontend.pages.products.index', $viewData);
    }

    public function setLocale(Request $request, string $locale): RedirectResponse
    {
        if (! in_array($locale, ['ar', 'en'], true)) {
            $locale = 'en';
        }

        session(['locale' => $locale]);
        app()->setLocale($locale);

        return redirect()->back();
    }

    public function product(string $slug): View
    {
        $product = Product::query()
            ->with(['category', 'variants.size', 'productColors.variants.size', 'measurementCharts'])
            ->where('slug', $slug)
            ->first();

        if ($product instanceof Product) {
            $presentation = $this->homePageData->presentProduct($product);
            $title = $presentation['title'] ?? $slug;

            return view('frontend.pages.placeholder', [
                'title' => $title,
                'eyebrow' => __('front.products.view_full_details'),
                'message' => __('front.products.page_placeholder_message'),
                'details' => [
                    ['label' => __('front.products.product_code'), 'value' => $presentation['product_code'] ?? null],
                    ['label' => __('front.products.color'), 'value' => $presentation['default_color'] ?? null],
                    ['label' => __('front.products.size'), 'value' => $presentation['default_size'] ?? null],
                ],
                'back_url' => route('front.home') . '#featured-products',
            ]);
        }

        return view('frontend.pages.placeholder', [
            'title' => Str::headline(str_replace('-', ' ', $slug)),
            'eyebrow' => __('front.products.view_full_details'),
            'message' => __('front.products.page_placeholder_message'),
            'details' => [],
            'back_url' => route('front.home') . '#featured-products',
        ]);
    }

    public function cart(FrontCartService $cart): View
    {
        return view('frontend.pages.placeholder', [
            'title' => __('front.cart.view_cart'),
            'eyebrow' => __('front.cart.title'),
            'message' => __('front.cart.page_placeholder_message'),
            'details' => [],
            'back_url' => route('front.home') . '#featured-products',
            'cart_state' => $cart->state(),
        ]);
    }

    public function checkout(): View
    {
        return view('frontend.pages.placeholder', [
            'title' => __('front.cart.check_out'),
            'eyebrow' => __('front.cart.title'),
            'message' => __('front.cart.checkout_placeholder_message'),
            'details' => [],
            'back_url' => route('front.cart.view'),
        ]);
    }

    public function page(string $slug): View
    {
        $page = CompanyPage::query()
            ->where('slug', $slug)
            ->where('status', 'active')
            ->first();

        if ($page instanceof CompanyPage) {
            $title = app()->getLocale() === 'ar'
                ? ($page->title_ar ?: $page->title_en ?: $slug)
                : ($page->title_en ?: $page->title_ar ?: $slug);

            return view('frontend.pages.placeholder', [
                'title' => $title,
                'eyebrow' => __('front.nav.about'),
                'message' => __('front.products.page_placeholder_message'),
                'details' => [],
                'back_url' => route('front.home'),
            ]);
        }

        return view('frontend.pages.placeholder', [
            'title' => Str::headline(str_replace('-', ' ', $slug)),
            'eyebrow' => __('front.nav.about'),
            'message' => __('front.products.page_placeholder_message'),
            'details' => [],
            'back_url' => route('front.home'),
        ]);
    }

    public function quickView(Product $product): JsonResponse
    {
        return response()->json([
            'product' => $this->homePageData->presentProduct($product, app()->getLocale()),
        ]);
    }

    public function addToCart(Request $request, Product $product, FrontCartService $cart): JsonResponse
    {
        $payload = $request->validate([
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
            'size' => ['nullable', 'string', 'max:100'],
            'size_id' => ['nullable', 'integer', 'min:1'],
            'size_code' => ['nullable', 'string', 'max:100'],
            'variant_id' => ['nullable', 'integer', 'min:1'],
            'color' => ['nullable', 'string', 'max:100'],
            'color_name' => ['nullable', 'string', 'max:100'],
            'color_id' => ['nullable', 'integer', 'min:1'],
            'color_code' => ['nullable', 'string', 'max:100'],
        ]);

        $state = $cart->add($product, $payload);

        return $this->cartResponse($state);
    }

    public function updateCartItem(Request $request, string $key, FrontCartService $cart): JsonResponse
    {
        $payload = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:99'],
        ]);

        $state = $cart->update($key, (int) $payload['quantity']);

        return $this->cartResponse($state);
    }

    public function removeCartItem(string $key, FrontCartService $cart): JsonResponse
    {
        $state = $cart->remove($key);

        return $this->cartResponse($state);
    }

    protected function cartResponse(array $state): JsonResponse
    {
        $html = view('frontend.partials.shopping-cart', [
            'cartState' => $state,
        ])->render();

        return response()->json([
            'ok' => true,
            'cart_state' => $state,
            'cart_html' => $html,
        ]);
    }

    protected function collectCategoryBranchIds(Category $category): array
    {
        $ids = [$category->getKey()];

        $children = $category->children()->get();

        foreach ($children as $child) {
            $ids = array_merge($ids, $this->collectCategoryBranchIds($child));
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }

    protected function collectCategoriesBranchIds(Collection|EloquentCollection $categories): array
    {
        return $categories
            ->flatMap(fn (Category $item): array => $this->collectCategoryBranchIds($item))
            ->unique()
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();
    }

    protected function normalizeProductsSort(string $sort): string
    {
        return in_array($sort, ['featured', 'best_selling', 'price_asc', 'price_desc', 'newest', 'oldest'], true)
            ? $sort
            : 'featured';
    }

    protected function applyProductsSort($query, string $sort): void
    {
        match ($sort) {
            'best_selling' => $query->orderByDesc('is_best_seller')->orderByDesc('updated_at'),
            'price_asc' => $query->orderByRaw('COALESCE(price, 0) asc')->orderByDesc('updated_at'),
            'price_desc' => $query->orderByRaw('COALESCE(price, 0) desc')->orderByDesc('updated_at'),
            'newest' => $query->orderByDesc('created_at'),
            'oldest' => $query->orderBy('created_at'),
            default => $query->orderByDesc('is_best_seller')->orderByDesc('is_new')->orderByDesc('updated_at'),
        };
    }

    protected function normalizePriceInput(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    protected function normalizeStringArray(mixed $value): array
    {
        $items = is_array($value) ? $value : [$value];

        return collect($items)
            ->map(fn ($item): string => trim((string) $item))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    protected function resolveSelectedCategorySlugs(Request $request, ?Category $category = null): array
    {
        $selected = $this->requestList($request, 'categories', 'category');

        if ($selected === [] && $category instanceof Category) {
            $selected = [$category->slug];
        }

        return $selected;
    }

    protected function resolveSelectedCategoryModels(array $slugs): EloquentCollection
    {
        if ($slugs === []) {
            return new EloquentCollection();
        }

        return Category::query()
            ->whereIn('slug', $slugs)
            ->get();
    }

    protected function requestList(Request $request, string $compactKey, string $legacyKey): array
    {
        $compact = trim((string) $request->query($compactKey, ''));
        if ($compact !== '') {
            return collect(explode(',', $compact))
                ->map(fn ($item): string => trim((string) $item))
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        return $this->normalizeStringArray($request->query($legacyKey));
    }

    protected function requestPriceRange(Request $request): array
    {
        $compact = trim((string) $request->query('price', ''));
        if ($compact !== '' && preg_match('/^\s*(\d+(?:\.\d+)?)\s*-\s*(\d+(?:\.\d+)?)\s*$/', $compact, $matches)) {
            return [
                $this->normalizePriceInput($matches[1]),
                $this->normalizePriceInput($matches[2]),
            ];
        }

        return [
            $this->normalizePriceInput($request->query('min_price')),
            $this->normalizePriceInput($request->query('max_price')),
        ];
    }

    protected function newProductsListingQuery(): Builder
    {
        return Product::query()
            ->with([
                'productColors' => fn ($query) => $query
                    ->where('status', 'active')
                    ->orderBy('sort_order')
                    ->orderBy('id'),
                'variants' => fn ($query) => $query
                    ->whereHas('productColor', fn (Builder $colorQuery) => $colorQuery->where('status', 'active'))
                    ->with('size'),
                'productColors.variants.size',
                'measurementCharts',
                'category',
            ])
            ->where('show_web', true)
            ->whereHas('productColors', fn (Builder $query) => $query->where('status', 'active'))
            ->where('is_active', true);
    }

    protected function applyProductsFilters(Builder $query, array $filters): Builder
    {
        $categoryIds = array_values(array_filter(array_map('intval', $filters['category_ids'] ?? [])));
        $minPrice = $this->displayPriceToBase($filters['min_price'] ?? null);
        $maxPrice = $this->displayPriceToBase($filters['max_price'] ?? null);
        $colorIds = $this->resolveActiveProductColorIds($this->normalizeStringArray($filters['colors'] ?? []));
        $sizes = $this->resolveSizeFilterTerms($this->normalizeStringArray($filters['sizes'] ?? []));

        if ($categoryIds !== []) {
            $query->whereIn('category_id', $categoryIds);
        }

        if ($minPrice !== null) {
            $query->whereRaw('COALESCE(price, 0) >= ?', [$minPrice]);
        }

        if ($maxPrice !== null) {
            $query->whereRaw('COALESCE(price, 0) <= ?', [$maxPrice]);
        }

        if ($colorIds !== []) {
            $query->whereHas('productColors', function (Builder $colorQuery) use ($colorIds): void {
                $colorQuery
                    ->where('status', 'active')
                    ->whereIn('product_colors.id', $colorIds);
            });
        }

        if ($sizes !== []) {
            $query->whereHas('variants', function (Builder $variantQuery) use ($sizes): void {
                $variantQuery
                    ->whereHas('productColor', fn (Builder $colorQuery) => $colorQuery->where('status', 'active'))
                    ->whereHas('size', function (Builder $sizeQuery) use ($sizes): void {
                        $sizeQuery->where(function (Builder $nested) use ($sizes): void {
                            $nested->whereIn('code', $sizes)
                                ->orWhereIn('name_ar', $sizes)
                                ->orWhereIn('name_en', $sizes);
                        });
                    });
                });
        }

        return $query;
    }

    protected function buildFilterCategories(Collection $categories, array $filters): Collection
    {
        $baseQuery = $this->newProductsListingQuery();
        $this->applyProductsFilters($baseQuery, $filters);

        return $this->applyCategoryCounts($categories, $baseQuery);
    }

    protected function buildColorOptions(array $filters, string $locale, array $selectedColors): Collection
    {
        $selected = collect($selectedColors)
            ->map(fn ($value) => $this->makeFilterSlug((string) $value))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $colors = ProductColor::query()
            ->select(['id', 'product_id', 'color_name_ar', 'color_name_en', 'color_code', 'color_hex'])
            ->where('status', 'active')
            ->whereHas('product', function (Builder $query) use ($filters): void {
                $this->applyProductsFilters($query, array_merge($filters, [
                    'colors' => [],
                ]));
            })
            ->get();

        return $colors
            ->groupBy(fn (ProductColor $color): string => $this->productColorFilterKey($color))
            ->map(function (Collection $group) use ($locale, $selected): array {
                /** @var ProductColor|null $first */
                $first = $group->first();
                $value = $first instanceof ProductColor ? $this->productColorFilterKey($first) : '';
                $label = trim((string) ($locale === 'ar'
                    ? ($first?->color_name_ar ?: $first?->color_name_en ?: $first?->color_code)
                    : ($first?->color_name_en ?: $first?->color_name_ar ?: $first?->color_code)));

                return [
                    'value' => $value,
                    'label' => $label,
                    'hex' => (string) ($group
                        ->map(fn (ProductColor $color): ?string => $this->normalizeHexColor($color->color_hex))
                        ->filter()
                        ->first() ?? ''),
                    'fallback_key' => trim((string) ($first?->color_name_en ?: $first?->color_code ?: '')),
                    'count' => $group->pluck('product_id')->unique()->count(),
                    'selected' => in_array($value, $selected, true),
                ];
            })
            ->filter(fn (array $option): bool => $option['label'] !== '' && $option['value'] !== '')
            ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    protected function buildSizeOptions(array $filters, string $locale, array $selectedSizes): Collection
    {
        $variants = ProductVariant::query()
            ->with('size')
            ->select(['product_id', 'size_id'])
            ->whereHas('productColor', fn (Builder $query) => $query->where('status', 'active'))
            ->whereHas('product', function (Builder $query) use ($filters): void {
                $this->applyProductsFilters($query, $filters);
            })
            ->whereHas('size')
            ->get();

        return $variants
            ->filter(fn (ProductVariant $variant): bool => $variant->size !== null)
            ->groupBy(function (ProductVariant $variant): string {
                return $this->makeFilterSlug($variant->size->code ?: $variant->size->name_ar ?: $variant->size->name_en);
            })
            ->map(function (Collection $group) use ($locale, $selectedSizes): array {
                /** @var ProductVariant|null $first */
                $first = $group->first();
                $size = $first?->size;
                $value = $this->makeFilterSlug($size?->code ?: $size?->name_ar ?: $size?->name_en);
                $label = trim((string) ($size?->code ?: ($locale === 'ar' ? ($size?->name_ar ?: $size?->name_en) : ($size?->name_en ?: $size?->name_ar))));

                return [
                    'value' => $value,
                    'label' => $label,
                    'count' => $group->pluck('product_id')->unique()->count(),
                    'selected' => in_array($value, $selectedSizes, true),
                ];
            })
            ->filter(fn (array $option): bool => $option['label'] !== '')
            ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    protected function buildPriceStats(array $filters, ?float $selectedMin, ?float $selectedMax): array
    {
        $query = $this->newProductsListingQuery();
        $this->applyProductsFilters($query, $filters);

        $minBase = (float) ((clone $query)->min('price') ?? 0);
        $maxBase = (float) ((clone $query)->max('price') ?? 0);
        $min = $this->basePriceToDisplay($minBase);
        $max = $this->basePriceToDisplay($maxBase);
        $upper = max(1, (int) ceil($max));
        $currencyContext = $this->currentCurrencyContext();

        return [
            'min_limit' => max(0, (int) floor($min)),
            'max_limit' => $upper,
            'selected_min' => $selectedMin !== null ? (int) floor($selectedMin) : max(0, (int) floor($min)),
            'selected_max' => $selectedMax !== null ? (int) ceil($selectedMax) : $upper,
            'currency' => $currencyContext['currency'],
            'symbol' => $currencyContext['symbol'],
            'rate' => $currencyContext['rate'],
        ];
    }

    protected function buildActiveFilterChips(
        EloquentCollection $selectedCategoryModels,
        array $selectedColors,
        array $selectedSizes,
        array $priceStats,
        ?float $selectedMin,
        ?float $selectedMax,
        string $locale,
    ): array {
        $chips = [];

        foreach ($selectedCategoryModels as $category) {
            if (! $category instanceof Category) {
                continue;
            }

            $chips[] = [
                'type' => 'category',
                'value' => (string) $category->slug,
                'label' => ($locale === 'ar'
                    ? ($category->title_ar ?: $category->title_en ?: $category->slug)
                    : ($category->title_en ?: $category->title_ar ?: $category->slug)),
            ];
        }

        foreach ($selectedColors as $color) {
            $chips[] = [
                'type' => 'color',
                'value' => (string) $color,
                'label' => $this->resolveColorChipLabel((string) $color, $locale),
            ];
        }

        foreach ($selectedSizes as $size) {
            $chips[] = [
                'type' => 'size',
                'value' => (string) $size,
                'label' => $this->resolveSizeChipLabel((string) $size),
            ];
        }

        if ($selectedMin !== null || $selectedMax !== null) {
            $currency = (string) ($priceStats['currency'] ?? 'SYP');
            $rangeMin = (int) ($selectedMin ?? $priceStats['selected_min'] ?? 0);
            $rangeMax = (int) ($selectedMax ?? $priceStats['selected_max'] ?? $priceStats['max_limit'] ?? 0);

            $chips[] = [
                'type' => 'price',
                'value' => '',
                'label' => $rangeMin . ' - ' . $rangeMax . ' ' . $currency,
            ];
        }

        return $chips;
    }

    protected function currentCurrencyContext(): array
    {
        $settings = ExchangeRateSetting::singleton();
        $currency = strtoupper((string) (
            session('selectedCurrency')
            ?: (app()->bound('currentCurrency') ? app('currentCurrency') : null)
            ?: request()->cookie('selectedCurrency')
            ?: 'SYP'
        ));

        $rate = match ($currency) {
            'USD' => (float) ($settings->usd_syp_rate ?: 1),
            'EUR' => (float) ($settings->eur_syp_rate ?: 1),
            default => 1.0,
        };

        if ($rate <= 0) {
            $rate = 1.0;
        }

        return [
            'currency' => in_array($currency, ['SYP', 'USD', 'EUR'], true) ? $currency : 'SYP',
            'symbol' => match ($currency) {
                'USD' => '$',
                'EUR' => 'EUR',
                default => 'SYP',
            },
            'rate' => $rate,
        ];
    }

    protected function displayPriceToBase(?float $displayPrice): ?float
    {
        if ($displayPrice === null) {
            return null;
        }

        return round($displayPrice * $this->currentCurrencyContext()['rate'], 4);
    }

    protected function basePriceToDisplay(?float $basePrice): float
    {
        $basePrice = (float) ($basePrice ?? 0);
        $rate = $this->currentCurrencyContext()['rate'];

        if ($rate <= 0) {
            $rate = 1.0;
        }

        return $basePrice / $rate;
    }

    protected function resolveActiveProductColorIds(array $selectedColors): array
    {
        $selected = collect($selectedColors)
            ->map(fn ($value) => $this->makeFilterSlug((string) $value))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($selected === []) {
            return [];
        }

        return ProductColor::query()
            ->select(['id', 'color_name_ar', 'color_name_en', 'color_code', 'color_hex'])
            ->where('status', 'active')
            ->get()
            ->filter(fn (ProductColor $color): bool => in_array($this->productColorFilterKey($color), $selected, true))
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();
    }

    protected function resolveColorChipLabel(string $value, string $locale): string
    {
        $slug = $this->makeFilterSlug((string) $value);

        $color = ProductColor::query()
            ->select(['id', 'color_name_ar', 'color_name_en', 'color_code', 'color_hex'])
            ->where('status', 'active')
            ->get()
            ->first(fn (ProductColor $color): bool => $this->productColorFilterKey($color) === $slug);

        if (! $color instanceof ProductColor) {
            return $value;
        }

        return trim((string) ($locale === 'ar'
            ? ($color->color_name_ar ?: $color->color_name_en ?: $color->color_code ?: $value)
            : ($color->color_name_en ?: $color->color_name_ar ?: $color->color_code ?: $value)));
    }

    protected function productColorFilterKey(ProductColor $color): string
    {
        $hex = $this->normalizeHexColor($color->color_hex);

        if ($hex !== null) {
            return $this->makeFilterSlug($hex);
        }

        if (filled($color->color_name_en)) {
            return $this->makeFilterSlug((string) $color->color_name_en);
        }

        if (filled($color->color_name_ar)) {
            return $this->makeFilterSlug((string) $color->color_name_ar);
        }

        return $this->makeFilterSlug((string) $color->color_code);
    }

    protected function normalizeHexColor(?string $hex): ?string
    {
        $hex = trim((string) $hex);

        if ($hex === '') {
            return null;
        }

        if (! str_starts_with($hex, '#')) {
            $hex = '#' . $hex;
        }

        return preg_match('/^#[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/', $hex) === 1
            ? strtoupper($hex)
            : null;
    }

    protected function filterCategoriesTree(): Collection
    {
        $categories = Category::query()
            ->orderBy('sort_order')
            ->orderBy('title_ar')
            ->get();

        return $this->nestFilterCategories($categories);
    }

    protected function nestFilterCategories(Collection $categories, ?int $parentId = null): Collection
    {
        return $categories
            ->filter(fn (Category $category): bool => (int) ($category->parent_id ?? 0) === (int) ($parentId ?? 0))
            ->values()
            ->map(function (Category $category) use ($categories): Category {
                $category->setRelation('children', $this->nestFilterCategories($categories, (int) $category->getKey()));

                return $category;
            });
    }

    protected function applyCategoryCounts(Collection $categories, Builder $baseQuery): Collection
    {
        return $categories->map(function (Category $category) use ($baseQuery): Category {
            $category->setAttribute('products_count', (clone $baseQuery)->whereIn('category_id', $this->collectCategoryBranchIds($category))->count());

            if ($category->relationLoaded('children')) {
                $category->setRelation('children', $this->applyCategoryCounts($category->children, $baseQuery));
            }

            return $category;
        })->values();
    }

    protected function resolveSizeFilterTerms(array $selectedSizes): array
    {
        if ($selectedSizes === []) {
            return [];
        }

        $matching = Size::query()
            ->select(['code', 'name_ar', 'name_en'])
            ->get()
            ->filter(function (Size $size) use ($selectedSizes): bool {
                return in_array($this->makeFilterSlug($size->code), $selectedSizes, true)
                    || in_array($this->makeFilterSlug($size->name_ar), $selectedSizes, true)
                    || in_array($this->makeFilterSlug($size->name_en), $selectedSizes, true);
            });

        return $matching
            ->flatMap(function (Size $size): array {
                return array_values(array_filter([
                    trim((string) $size->code),
                    trim((string) $size->name_ar),
                    trim((string) $size->name_en),
                ]));
            })
            ->unique()
            ->values()
            ->all();
    }

    protected function resolveSizeChipLabel(string $slug): string
    {
        $match = Size::query()
            ->select(['code', 'name_ar', 'name_en'])
            ->get()
            ->first(function (Size $size) use ($slug): bool {
                return $this->makeFilterSlug($size->code) === $slug
                    || $this->makeFilterSlug($size->name_ar) === $slug
                    || $this->makeFilterSlug($size->name_en) === $slug;
            });

        if (! $match instanceof Size) {
            return Str::headline(str_replace('-', ' ', $slug));
        }

        return trim((string) ($match->code ?: $match->name_en ?: $match->name_ar ?: $slug));
    }

    protected function makeFilterSlug(?string $value): string
    {
        return Str::slug(trim((string) $value));
    }
}
