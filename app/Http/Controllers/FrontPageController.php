<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendContactMessageRequest;
use App\Http\Requests\StoreFrontOrderRequest;
use App\Models\Category;
use App\Models\Color;
use App\Mail\ContactMessageMail;
use App\Models\CompanyPage;
use App\Models\CustomerServiceFaq;
use App\Models\CustomerServiceSetting;
use App\Models\ExchangeRateSetting;
use App\Models\InternalPageHeader;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\ProductVariant;
use App\Models\Size;
use App\Services\FrontCartService;
use App\Services\FrontCheckoutService;
use App\Services\FrontHomePageDataService;
use App\Services\FrontWishlistService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;
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

        $sort = $this->normalizeProductsSort((string) $request->query('sort', 'featured'));

        return $this->renderProductsListing($category, $request, $sort);
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

    public function offers(Request $request): View|JsonResponse
    {
        $sort = $this->normalizeProductsSort((string) $request->query('sort', 'featured'));

        return $this->renderProductsListing(null, $request, $sort, true);
    }

    protected function renderProductsListing(
        ?Category $category = null,
        ?Request $request = null,
        string $sort = 'featured',
        bool $offersPage = false,
    ): View|JsonResponse
    {
        $request ??= request();

        $shell = $this->homePageData->build();
        $locale = app()->getLocale();
        $selectedGrid = $this->normalizeProductsGrid((string) $request->query('grid', 'grid-4'));
        $searchTerm = $this->normalizeSearchTerm($request->query('q', $request->query('text', $request->query('search', ''))));
        $baseCategory = $category;
        $filterScopeCategory = $this->determineCategoryFilterScope($baseCategory);
        $selectedCategorySlugs = $this->resolveSelectedCategorySlugs($request);
        $selectedCategoryModels = $this->scopeSelectedCategoryModelsToBase(
            $this->resolveSelectedCategoryModels($selectedCategorySlugs),
            $filterScopeCategory,
        );
        $selectedCategorySlugs = $selectedCategoryModels
            ->pluck('slug')
            ->filter()
            ->values()
            ->all();
        $selectedColors = $this->requestList($request, 'colors', 'color');
        $selectedSizes = $this->requestList($request, 'sizes', 'size');
        $selectedBodyFit = $this->requestList($request, 'body_fit', 'body_fit');
        $selectedDropType = $this->requestList($request, 'drop_type', 'drop_type');
        $selectedCollections = $this->requestList($request, 'collections', 'collection');
        $selectedSpecialOffers = $this->requestList($request, 'special_offers', 'special_offer');
        $specialOfferOnly = $offersPage || in_array('offer', array_map('strtolower', $selectedSpecialOffers), true);
        [$minPrice, $maxPrice] = $this->requestPriceRange($request);
        $searchClearQuery = Arr::except($request->query(), ['page', 'q', 'text', 'search', 'filter_ajax', 'load_more']);
        $searchClearUrl = $request->url();

        if ($searchClearQuery !== []) {
            $searchClearUrl .= '?' . http_build_query($searchClearQuery);
        }

        $resetUrl = $request->url();
        $primaryCategory = $baseCategory instanceof Category
            ? $baseCategory
            : ($selectedCategoryModels->count() === 1 ? $selectedCategoryModels->first() : null);
        $categoryTrail = $primaryCategory instanceof Category ? $primaryCategory->breadcrumbTrail() : collect();
        $baseProductCategoryLeafIds = $baseCategory instanceof Category
    ? $this->collectLeafCategoryIds($baseCategory)
    : [];

$filterScopeLeafIds = $filterScopeCategory instanceof Category
    ? $this->collectLeafCategoryIds($filterScopeCategory)
    : [];

$effectiveCategoryIds = $selectedCategoryModels->isNotEmpty()
    ? $this->collectCategoriesLeafIds($selectedCategoryModels)
    : $baseProductCategoryLeafIds;

        $filters = [
            'category_ids' => $effectiveCategoryIds,
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
            'colors' => $selectedColors,
            'sizes' => $selectedSizes,
            'body_fit' => $selectedBodyFit,
            'drop_type' => $selectedDropType,
                            'collections' => $selectedCollections,
                    'special_offer' => $specialOfferOnly,
            'search' => $searchTerm,
];

        $query = $this->newProductsListingQuery();
        $this->applyProductsFilters($query, $filters);
        $this->applyProductsSort($query, $sort);

        $paginator = $query->paginate(16)->appends(
            Arr::except($request->query(), ['page', 'load_more', 'filter_ajax'])
        );
        $selectedFilterColorIds = $this->resolveStructureColorIds($selectedColors);

        $products = $paginator->getCollection()
            ->map(fn (Product $product): array => $this->homePageData->presentProduct($product, $locale, $selectedFilterColorIds))
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
        ];

        if ($offersPage) {
            $breadcrumbItems[] = [
                'label' => $locale === 'ar' ? 'العروض' : 'Offers',
                'url' => route('front.offers'),
            ];
        } elseif ($primaryCategory instanceof Category) {
            foreach ($categoryTrail as $trailCategory) {
                $breadcrumbItems[] = [
                    'label' => $locale === 'ar'
                        ? ($trailCategory->title_ar ?: $trailCategory->title_en ?: $trailCategory->slug)
                        : ($trailCategory->title_en ?: $trailCategory->title_ar ?: $trailCategory->slug),
                    'url' => route('front.category', $trailCategory->slug),
                ];
            }
        }

        $categories = $this->filterCategoriesTree($filterScopeCategory);

        $filterCategories = $this->buildFilterCategories($categories, [
            'category_ids' => $filterScopeLeafIds,
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
            'colors' => $selectedColors,
            'sizes' => $selectedSizes,
            'body_fit' => $selectedBodyFit,
            'drop_type' => $selectedDropType,
                            'collections' => $selectedCollections,
                    'special_offer' => $specialOfferOnly,
            'search' => $searchTerm,
]);
        $filterColorOptions = $this->buildColorOptions([
            'category_ids' => $filters['category_ids'],
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
            'colors' => [],
            'sizes' => $selectedSizes,
            'body_fit' => $selectedBodyFit,
            'drop_type' => $selectedDropType,
                            'collections' => $selectedCollections,
                    'special_offer' => $specialOfferOnly,
            'search' => $searchTerm,
], $locale, $selectedColors);
        $filterSizeOptions = $this->buildSizeOptions([
            'category_ids' => $filters['category_ids'],
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
            'colors' => $selectedColors,
            'sizes' => [],
            'body_fit' => $selectedBodyFit,
            'drop_type' => $selectedDropType,
                            'collections' => $selectedCollections,
                    'special_offer' => $specialOfferOnly,
            'search' => $searchTerm,
], $locale, $selectedSizes);
        $filterBodyFitOptions = $this->buildBodyFitOptions([
            'category_ids' => $filters['category_ids'],
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
            'colors' => $selectedColors,
            'sizes' => $selectedSizes,
            'body_fit' => [],
            'drop_type' => $selectedDropType,
                            'collections' => $selectedCollections,
                    'special_offer' => $specialOfferOnly,
            'search' => $searchTerm,
], $selectedBodyFit);
        $filterDropOptions = $this->buildDropOptions([
            'category_ids' => $filters['category_ids'],
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
            'colors' => $selectedColors,
            'sizes' => $selectedSizes,
            'body_fit' => $selectedBodyFit,
            'drop_type' => [],
            'collections' => $selectedCollections,
            'special_offer' => $specialOfferOnly,
            'search' => $searchTerm,
        ], $selectedDropType);
        $filterCollectionOptions = $this->buildCollectionOptions([
            'category_ids' => $filters['category_ids'],
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
            'colors' => $selectedColors,
            'sizes' => $selectedSizes,
            'body_fit' => $selectedBodyFit,
            'drop_type' => $selectedDropType,
            'collections' => [],
            'special_offer' => $specialOfferOnly,
            'search' => $searchTerm,
        ], $selectedCollections);
        $filterSpecialOfferOption = $offersPage
            ? null
            : $this->buildSpecialOfferOption([
                'category_ids' => $filters['category_ids'],
                'min_price' => $minPrice,
                'max_price' => $maxPrice,
                'colors' => $selectedColors,
                'sizes' => $selectedSizes,
                'body_fit' => $selectedBodyFit,
                'drop_type' => $selectedDropType,
                'collections' => $selectedCollections,
                'special_offer' => false,
                'search' => $searchTerm,
            ], $specialOfferOnly, $locale);
        $filterPriceStats = $this->buildPriceStats([
            'category_ids' => $filters['category_ids'],
            'min_price' => null,
            'max_price' => null,
            'colors' => $selectedColors,
            'sizes' => $selectedSizes,
            'body_fit' => $selectedBodyFit,
            'drop_type' => $selectedDropType,
                            'collections' => $selectedCollections,
                    'special_offer' => $specialOfferOnly,
            'search' => $searchTerm,
], $minPrice, $maxPrice);
        $activeFilterChips = $this->buildActiveFilterChips(
            $selectedCategoryModels,
            $selectedColors,
            $selectedSizes,
            $selectedBodyFit,
            $selectedDropType,
            $selectedCollections,
            $offersPage ? false : $specialOfferOnly,
            $filterPriceStats,
            $minPrice,
            $maxPrice,
            $locale,
        );
        $searchFilterChip = $searchTerm !== ''
            ? [
                'label' => __('front.search.active_query', ['term' => $searchTerm]),
                'url' => $searchClearUrl,
            ]
            : null;
        $emptyStateTitle = null;
        $emptyStateMessage = null;
        $emptyStateResetUrl = null;
        $emptyStateResetLabel = null;
        $emptyStateAllUrl = $offersPage ? route('front.offers') : route('front.products.index');
        $emptyStateAllLabel = $offersPage
            ? __('front.search.view_all_offers')
            : __('front.search.view_all_products');

        if ($searchTerm !== '') {
            $emptyStateTitle = __('front.search.no_results_title');
            $emptyStateMessage = __('front.search.no_results_message', ['term' => $searchTerm]);
            $emptyStateResetUrl = $searchClearUrl;
            $emptyStateResetLabel = __('front.search.clear_search');
        } elseif ($offersPage) {
            $emptyStateMessage = $activeFilterChips !== []
                ? ($locale === 'ar' ? 'لا توجد عروض مطابقة للفلاتر المحددة.' : 'No offers match the selected filters.')
                : ($locale === 'ar' ? 'لا توجد عروض متاحة حاليًا.' : 'No offers are currently available.');
        }

        $categoryContextChip = $filterScopeCategory instanceof Category
            ? [
                'label' => $locale === 'ar'
                    ? ($filterScopeCategory->title_ar ?: $filterScopeCategory->title_en ?: $filterScopeCategory->slug)
                    : ($filterScopeCategory->title_en ?: $filterScopeCategory->title_ar ?: $filterScopeCategory->slug),
            ]
            : null;

        $pageTitle = $locale === 'ar' ? 'المنتجات' : 'Products';
        $pageTitleBackground = null;

        if ($primaryCategory instanceof Category) {
            $pageTitle = $locale === 'ar'
                ? ($primaryCategory->title_ar ?: $primaryCategory->title_en ?: $pageTitle)
                : ($primaryCategory->title_en ?: $primaryCategory->title_ar ?: $pageTitle);

            if (blank($primaryCategory->parent_id)) {
                $pageTitleBackground = filled($primaryCategory->banner ?? null)
                    ? asset($primaryCategory->banner)
                    : null;
            }
        }

        if ($offersPage) {
            $pageTitle = $locale === 'ar' ? 'العروض' : 'Offers';
            $pageTitleBackground = null;
            $pageSubtitle = $searchTerm !== ''
                ? (($locale === 'ar' ? 'نتائج البحث ضمن العروض عن: ' : 'Offer results for: ') . $searchTerm)
                : ($locale === 'ar' ? 'تصفح المنتجات المتوفرة ضمن العروض الحالية' : 'Browse products available in the current offers');
        } elseif ($searchTerm !== '') {
            $pageTitle = $locale === 'ar' ? 'نتائج البحث' : 'Search Results';
            $pageSubtitle = ($locale === 'ar' ? 'نتائج البحث عن: ' : 'Search results for: ') . $searchTerm;
        } else {
            $pageSubtitle = $primaryCategory instanceof Category
                ? ($locale === 'ar' ? 'تصفح منتجات هذا التصنيف' : 'Browse products in this category')
                : ($locale === 'ar' ? 'تصفح مجموعة المنتجات مع الفلتر والفرز' : 'Browse the product catalog with filters and sorting');
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
            'page_title_background' => $pageTitleBackground,
            'page_subtitle' => $pageSubtitle,
            'breadcrumb_items' => $breadcrumbItems,
            'products' => $paginator,
            'selected_sort' => $sort,
            'selected_grid' => $selectedGrid,
            'sort_options' => $sortOptions,
            'selected_category_slugs' => $selectedCategorySlugs,
            'selected_min_price' => $minPrice,
            'selected_max_price' => $maxPrice,
            'selected_colors' => $selectedColors,
            'selected_sizes' => $selectedSizes,
            'selected_body_fit' => $selectedBodyFit,
            'selected_drop_type' => $selectedDropType,
            'selected_collections' => $selectedCollections,
            'selected_special_offers' => $offersPage ? [] : ($specialOfferOnly ? ['offer'] : []),
            'selected_search_term' => $searchTerm,
            'filter_categories' => $filterCategories,
            'filter_color_options' => $filterColorOptions,
            'filter_size_options' => $filterSizeOptions,
            'filter_body_fit_options' => $filterBodyFitOptions,
            'filter_drop_options' => $filterDropOptions,
            'filter_collection_options' => $filterCollectionOptions,
            'filter_special_offer_option' => $filterSpecialOfferOption,
            'filter_price_stats' => $filterPriceStats,
            'active_filter_chips' => $activeFilterChips,
            'search_filter_chip' => $searchFilterChip,
            'category_context_chip' => $categoryContextChip,
            'filter_reset_url' => $offersPage ? route('front.offers') : $resetUrl,
            'empty_state_title' => $emptyStateTitle,
            'empty_state_message' => $emptyStateMessage,
            'empty_state_reset_url' => $emptyStateResetUrl,
            'empty_state_reset_label' => $emptyStateResetLabel,
            'empty_state_all_url' => $emptyStateAllUrl,
            'empty_state_all_label' => $emptyStateAllLabel,
        ]);

        if ($request->ajax() || $request->boolean('filter_ajax')) {
            return response()->json([
                'toolbar_html' => view('frontend.partials.shop-toolbar', [
                    'result_count' => $paginator->total(),
                    'sort_options' => $sortOptions,
                    'selected_sort' => $sort,
                    'selected_grid' => $selectedGrid,
                ])->render(),
                'filter_html' => view('frontend.partials.shop-filter', $viewData)->render(),
                'products_html' => view('frontend.partials.product-grid', [
                    'products' => $paginator,
                    'active_filter_chips' => $activeFilterChips,
                    'search_filter_chip' => $searchFilterChip,
                    'category_context_chip' => $categoryContextChip,
                    'filter_reset_url' => $offersPage ? route('front.offers') : $resetUrl,
                    'selected_grid' => $selectedGrid,
                    'empty_state_title' => $viewData['empty_state_title'],
                    'empty_state_message' => $viewData['empty_state_message'],
                    'empty_state_reset_url' => $viewData['empty_state_reset_url'],
                    'empty_state_reset_label' => $viewData['empty_state_reset_label'],
                    'empty_state_all_url' => $viewData['empty_state_all_url'],
                    'empty_state_all_label' => $viewData['empty_state_all_label'],
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
            ->with($this->productDetailRelations())
            ->where('slug', $slug)
            ->visibleToFrontendVisitor()
            ->where('is_active', true)
            ->firstOrFail();

        $locale = app()->getLocale();
        $shell = $this->homePageData->build();
        $presentation = $this->homePageData->presentProduct($product, $locale, [], null);
        $title = $presentation['title'] ?? $slug;
        $relatedProducts = $this->resolveDetailRelatedProducts($product, $locale);

        $breadcrumbItems = [
            ['label' => __('front.nav.home'), 'url' => route('front.home')],
        ];

        if ($product->category instanceof Category) {
            foreach ($product->category->breadcrumbTrail() as $trailCategory) {
                $breadcrumbItems[] = [
                    'label' => $locale === 'ar'
                        ? ($trailCategory->title_ar ?: $trailCategory->title_en ?: $trailCategory->slug)
                        : ($trailCategory->title_en ?: $trailCategory->title_ar ?: $trailCategory->slug),
                    'url' => route('front.category', $trailCategory->slug),
                ];
            }
        }

        $breadcrumbItems[] = [
            'label' => $title,
            'url' => filled($product->slug) ? route('front.products.show', $product->slug) : request()->url(),
        ];

        return view('frontend.pages.products.show', array_merge($shell, [
            'page_title' => $title,
            'page_subtitle' => $presentation['product_code'] ?? '',
            'breadcrumb_items' => $breadcrumbItems,
            'product' => $presentation,
            'product_model' => $product,
            'related_products' => $relatedProducts,
            'locale' => $locale,
        ]));
    }

    protected function productDetailRelations(): array
    {
        return [
            'category',
            'structureColor',
            'variants' => fn ($query) => $query
                ->whereHas('productColor', fn ($colorQuery) => $colorQuery->where('status', 'active'))
                ->with('size'),
            'productColors' => fn ($query) => $query
                ->where('status', 'active')
                ->orderBy('sort_order')
                ->orderBy('id'),
            'productColors.filterColor',
            'productColors.variants.size',
            'measurementCharts',
            'measurementChartGroup',
            'details' => fn ($query) => $query
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id'),
            'complements' => fn ($query) => $query
                ->orderBy('sort_order')
                ->with([
                    'relatedProduct' => fn ($relatedQuery) => $relatedQuery
                        ->with($this->productCardRelations())
                        ->visibleToFrontendVisitor()
                        ->where('is_active', true),
                ]),
        ];
    }

    protected function productCardRelations(): array
    {
        return [
            'category',
            'structureColor',
            'variants' => fn ($query) => $query
                ->whereHas('productColor', fn ($colorQuery) => $colorQuery->where('status', 'active'))
                ->with('size'),
            'productColors' => fn ($query) => $query
                ->where('status', 'active')
                ->orderBy('sort_order')
                ->orderBy('id'),
            'productColors.filterColor',
            'productColors.variants.size',
            'measurementCharts',
            'measurementChartGroup',
        ];
    }

    protected function resolveDetailRelatedProducts(Product $product, string $locale): array
    {
        $relatedModels = $product->relationLoaded('complements')
            ? $product->complements
                ->pluck('relatedProduct')
                ->filter(fn ($item): bool => $item instanceof Product)
                ->unique(fn (Product $related): int => (int) $related->getKey())
                ->values()
            : collect();

        return $relatedModels
            ->take(8)
            ->map(fn (Product $related): array => $this->homePageData->presentProduct($related, $locale))
            ->values()
            ->all();
    }


    public function wishlist(FrontWishlistService $wishlist): View
    {
        $wishlist->cleanupVisibleIds();

        $locale = app()->getLocale();
        $shell = $this->homePageData->build();
        $ids = $wishlist->ids();
        $positionMap = array_flip($ids);

        $products = Product::query()
            ->with($this->productCardRelations())
            ->visibleToFrontendVisitor()
            ->where('is_active', true)
            ->whereIn('id', $ids)
            ->get()
            ->sortBy(fn (Product $product): int => $positionMap[(int) $product->getKey()] ?? PHP_INT_MAX)
            ->map(fn (Product $product): array => $this->homePageData->presentProduct($product, $locale))
            ->values();

        $breadcrumbItems = [
            ['label' => __('front.nav.home'), 'url' => route('front.home')],
            ['label' => __('front.wishlist.title'), 'url' => route('front.wishlist.index')],
        ];

        return view('frontend.pages.wishlist.index', array_merge($shell, [
            'page_title' => __('front.wishlist.title'),
            'page_subtitle' => __('front.wishlist.subtitle'),
            'breadcrumb_items' => $breadcrumbItems,
            'wishlist_products' => $products,
            'locale' => $locale,
        ]));
    }

    public function cart(FrontCartService $cart): View
    {
        $shell = $this->homePageData->build();
        $state = $cart->state();

        return view('frontend.pages.cart.index', array_merge($shell, [
            'page_title' => __('front.cart.page_title'),
            'page_subtitle' => __('front.cart.page_subtitle'),
            'breadcrumb_items' => [
                ['label' => __('front.nav.home'), 'url' => route('front.home')],
                ['label' => __('front.cart.page_title'), 'url' => route('front.cart.view')],
            ],
            'cart_state' => $state,
        ]));
    }

    public function checkout(FrontCartService $cart, FrontCheckoutService $checkout): View|RedirectResponse
    {
        try {
            $cartState = $cart->checkoutState();
        } catch (ValidationException $exception) {
            return redirect()
                ->route('front.cart.view')
                ->withErrors($exception->errors());
        }

        if (empty($cartState['items'])) {
            return redirect()
                ->route('front.cart.view')
                ->with('cart_error', __('front.checkout.cart_empty'));
        }

        $shippingMethods = $checkout->activeShippingMethods();
        $paymentMethods = $checkout->activePaymentMethods();
        $authenticatedCustomer = auth('customer')->user();
        $savedAddresses = $authenticatedCustomer
            ? $authenticatedCustomer->addresses()->orderByDesc('is_default')->latest('id')->get()
            : collect();
        $shell = $this->homePageData->build();

        return view('frontend.pages.checkout.index', array_merge($shell, [
            'page_title' => __('front.checkout.title'),
            'page_subtitle' => __('front.checkout.subtitle'),
            'breadcrumb_items' => [
                ['label' => __('front.nav.home'), 'url' => route('front.home')],
                ['label' => __('front.cart.page_title'), 'url' => route('front.cart.view')],
                ['label' => __('front.checkout.title'), 'url' => route('front.checkout')],
            ],
            'cart_state' => $cartState,
            'shipping_methods' => $shippingMethods,
            'payment_methods' => $paymentMethods,
            'checkout_available' => $shippingMethods->isNotEmpty() && $paymentMethods->isNotEmpty(),
            'authenticated_customer' => $authenticatedCustomer,
            'saved_addresses' => $savedAddresses,
        ]));
    }

    public function storeCheckout(
        StoreFrontOrderRequest $request,
        FrontCheckoutService $checkout,
    ): RedirectResponse {
        $order = $checkout->createOrder($request->validated());

        return redirect()->route('front.checkout.success', $order->order_no);
    }

    public function checkoutSuccess(Order $order): View
    {
        abort_unless(
            (int) session(FrontCheckoutService::SUCCESS_SESSION_KEY) === (int) $order->getKey(),
            404,
        );

        $order->load(['items', 'shippingMethod', 'customer', 'shippingAddress']);
        $paymentMethod = PaymentMethod::query()
            ->where('code', $order->payment_method)
            ->first();
        $shell = $this->homePageData->build();

        return view('frontend.pages.checkout.success', array_merge($shell, [
            'page_title' => __('front.checkout.success_title'),
            'page_subtitle' => __('front.checkout.success_subtitle'),
            'breadcrumb_items' => [
                ['label' => __('front.nav.home'), 'url' => route('front.home')],
                ['label' => __('front.checkout.success_title'), 'url' => route('front.checkout.success', $order->order_no)],
            ],
            'order' => $order,
            'payment_method_record' => $paymentMethod,
        ]));
    }

    public function page(string $slug): View
    {
        if ($slug === 'contact-us') {
            return $this->renderContactPage($slug);
        }

        if ($slug === 'faq') {
            return $this->renderFaqPage($slug);
        }

        $customerServiceSettingKey = match ($slug) {
            'terms-and-conditions' => 'terms',
            'exchange-and-return-policy', 'exchange-policy' => 'exchange_policy',
            default => null,
        };

        if ($customerServiceSettingKey !== null) {
            $page = CustomerServiceSetting::query()
                ->where('setting_key', $customerServiceSettingKey)
                ->where('is_active', true)
                ->firstOrFail();

            return $this->renderContentPage(
                slug: $slug,
                titleAr: $page->title_ar,
                titleEn: $page->title_en,
                contentAr: $page->content_ar,
                contentEn: $page->content_en,
                record: $page,
            );
        }

        $page = CompanyPage::query()
            ->where('slug', $slug)
            ->where('status', 'active')
            ->firstOrFail();

        return $this->renderContentPage(
            slug: $page->slug,
            titleAr: $page->title_ar,
            titleEn: $page->title_en,
            contentAr: $page->content_ar,
            contentEn: $page->content_en,
            record: $page,
        );
    }

    protected function renderContactPage(string $slug): View
    {
        $locale = app()->getLocale();
        $title = __('front.contact.title');
        $shell = $this->homePageData->build();
        $pageHeader = InternalPageHeader::query()
            ->where('section_key', $this->companyPageHeaderSection($slug))
            ->where('status', 'active')
            ->first();

        return view('frontend.pages.contact', array_merge($shell, [
            'page_title' => $title,
            'page_subtitle' => __('front.contact.subtitle'),
            'page_title_background' => $this->internalPageHeaderImageUrl($pageHeader?->image),
            'page_meta_description' => __('front.contact.meta_description'),
            'breadcrumb_items' => [
                ['label' => __('front.nav.home'), 'url' => route('front.home')],
                ['label' => $title, 'url' => route('front.pages.show', $slug)],
            ],
            'contact_locale' => $locale,
        ]));
    }

    public function sendContactMessage(SendContactMessageRequest $request): RedirectResponse|JsonResponse
    {
        $payload = $request->validated();
        $recipient = trim((string) config('mail.contact.to.address'));
        $recipientName = trim((string) config('mail.contact.to.name'));

        if (! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $message = __('front.contact.configuration_error');

            return $request->expectsJson()
                ? response()->json(['ok' => false, 'message' => $message], 503)
                : back()->with('contact_error', $message);
        }

        try {
            Mail::mailer('smtp')
                ->to($recipient, $recipientName !== '' ? $recipientName : null)
                ->send(new ContactMessageMail(
                    messageData: Arr::only($payload, ['name', 'email', 'phone', 'subject', 'message']),
                    mailSubject: __('front.contact.mail_subject', ['subject' => $payload['subject']]),
                ));
        } catch (Throwable $exception) {
            report($exception);

            $message = __('front.contact.send_error');

            return $request->expectsJson()
                ? response()->json(['ok' => false, 'message' => $message], 502)
                : back()->with('contact_error', $message);
        }

        $message = __('front.contact.success');

        return $request->expectsJson()
            ? response()->json(['ok' => true, 'message' => $message])
            : redirect()
                ->route('front.pages.show', 'contact-us')
                ->with('contact_success', $message);
    }

    protected function renderFaqPage(string $slug): View
    {
        $locale = app()->getLocale();
        $title = $locale === 'ar' ? 'الأسئلة الشائعة' : 'Frequently Asked Questions';
        $faqItems = CustomerServiceFaq::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(function (CustomerServiceFaq $faq) use ($locale): array {
                $question = $locale === 'ar'
                    ? ($faq->question_ar ?: $faq->question_en ?: '')
                    : ($faq->question_en ?: $faq->question_ar ?: '');
                $answer = $locale === 'ar'
                    ? ($faq->answer_ar ?: $faq->answer_en ?: '')
                    : ($faq->answer_en ?: $faq->answer_ar ?: '');

                return [
                    'id' => (int) $faq->getKey(),
                    'question' => trim((string) $question),
                    'answer' => trim((string) $answer),
                ];
            })
            ->filter(fn (array $faq): bool => $faq['question'] !== '')
            ->values();
        $shell = $this->homePageData->build();
        $pageHeader = InternalPageHeader::query()
            ->where('section_key', $this->companyPageHeaderSection($slug))
            ->where('status', 'active')
            ->first();
        $pageTitleBackground = $this->internalPageHeaderImageUrl($pageHeader?->image);
        $metaDescription = $faqItems
            ->pluck('answer')
            ->map(fn (string $answer): string => trim(strip_tags($answer)))
            ->first(fn (string $answer): bool => $answer !== '') ?: $title;

        return view('frontend.pages.placeholder', array_merge($shell, [
            'page_title' => $title,
            'page_subtitle' => '',
            'page_title_background' => $pageTitleBackground,
            'page_meta_description' => $metaDescription,
            'breadcrumb_items' => [
                ['label' => __('front.nav.home'), 'url' => route('front.home')],
                ['label' => $title, 'url' => route('front.pages.show', $slug)],
            ],
            'faq_items' => $faqItems,
            'faq_empty_message' => $locale === 'ar'
                ? 'لا توجد أسئلة شائعة متاحة حاليًا.'
                : 'No frequently asked questions are available yet.',
        ]));
    }

    protected function renderContentPage(
        string $slug,
        ?string $titleAr,
        ?string $titleEn,
        ?string $contentAr,
        ?string $contentEn,
        CustomerServiceSetting|CompanyPage $record,
    ): View
    {
        $locale = app()->getLocale();
        $title = $locale === 'ar'
            ? ($titleAr ?: $titleEn ?: $slug)
            : ($titleEn ?: $titleAr ?: $slug);
        $content = $locale === 'ar'
            ? ($contentAr ?: $contentEn ?: '')
            : ($contentEn ?: $contentAr ?: '');
        $shell = $this->homePageData->build();
        $pageHeader = InternalPageHeader::query()
            ->where('section_key', $this->companyPageHeaderSection($slug))
            ->where('status', 'active')
            ->first();
        $pageTitleBackground = $this->internalPageHeaderImageUrl($pageHeader?->image);

        return view('frontend.pages.placeholder', array_merge($shell, [
            'page_title' => $title,
            'page_subtitle' => '',
            'page_title_background' => $pageTitleBackground,
            'breadcrumb_items' => [
                ['label' => __('front.nav.home'), 'url' => route('front.home')],
                ['label' => $title, 'url' => route('front.pages.show', $slug)],
            ],
            'company_page' => $record,
            'company_page_content' => $content,
        ]));
    }

    protected function companyPageHeaderSection(string $slug): string
    {
        $normalizedSlug = Str::lower(trim($slug));

        return match (true) {
            Str::contains($normalizedSlug, ['contact']) => 'contact',
            Str::contains($normalizedSlug, ['branch', 'store']) => 'branches',
            Str::contains($normalizedSlug, ['news', 'event']) => 'news',
            Str::contains($normalizedSlug, ['product']) => 'products',
            Str::contains($normalizedSlug, ['category', 'collection']) => 'categories',
            default => 'about',
        };
    }

    protected function internalPageHeaderImageUrl(?string $image): ?string
    {
        $image = trim((string) $image);

        if ($image === '') {
            return null;
        }

        if (Str::startsWith($image, ['http://', 'https://', '//'])) {
            return $image;
        }

        if (Str::startsWith($image, ['/storage/', 'storage/'])) {
            return asset(ltrim($image, '/'));
        }

        return Storage::disk('public')->url($image);
    }

    public function quickView(Product $product): JsonResponse
    {
        abort_unless($product->isVisibleToFrontendVisitor(), 404);

        return response()->json([
            'product' => $this->homePageData->presentProduct($product, app()->getLocale()),
        ]);
    }


    public function addToWishlist(Product $product, FrontWishlistService $wishlist): JsonResponse
    {
        abort_unless($product->isVisibleToFrontendVisitor() && (bool) $product->is_active, 404);

        $state = $wishlist->add($product);

        return $this->wishlistResponse($product, $state, true);
    }

    public function removeFromWishlist(Product $product, FrontWishlistService $wishlist): JsonResponse
    {
        $state = $wishlist->remove($product);

        return $this->wishlistResponse($product, $state, false);
    }

    protected function wishlistResponse(Product $product, array $state, bool $inWishlist): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'product_id' => (int) $product->getKey(),
            'product_slug' => (string) $product->slug,
            'in_wishlist' => $inWishlist,
            'wishlist_state' => $state,
            'wishlist_count' => $state['count'] ?? 0,
        ]);
    }

    public function addToCart(Request $request, Product $product, FrontCartService $cart): JsonResponse
    {
        abort_unless($product->isVisibleToFrontendVisitor(), 404);

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
        $pageHtml = view('frontend.partials.cart-page-content', [
            'cartState' => $state,
        ])->render();

        return response()->json([
            'ok' => true,
            'cart_state' => $state,
            'cart_html' => $html,
            'cart_page_html' => $pageHtml,
        ]);
    }

    protected function collectLeafCategoryIds(Category $category): array
    {
        $children = $category->children()->get();

        if ($children->isEmpty()) {
            return [(int) $category->getKey()];
        }

        $ids = [];

        foreach ($children as $child) {
            $ids = array_merge($ids, $this->collectLeafCategoryIds($child));
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }

    protected function collectCategoriesLeafIds(Collection|EloquentCollection $categories): array
    {
        return $categories
            ->flatMap(fn (Category $item): array => $this->collectLeafCategoryIds($item))
            ->unique()
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();
    }

    protected function determineCategoryFilterScope(?Category $baseCategory): ?Category
    {
        if (! $baseCategory instanceof Category) {
            return null;
        }

        if (! $baseCategory->isLeaf()) {
            return $baseCategory;
        }

        $trail = $baseCategory->breadcrumbTrail();

        return $trail->first() instanceof Category
            ? $trail->first()
            : $baseCategory;
    }

    protected function normalizeProductsSort(string $sort): string
    {
        return in_array($sort, ['featured', 'best_selling', 'price_asc', 'price_desc', 'newest', 'oldest'], true)
            ? $sort
            : 'featured';
    }

    protected function normalizeProductsGrid(string $grid): string
    {
        return in_array($grid, ['grid-2', 'grid-3', 'grid-4', 'grid-5', 'grid-6'], true)
            ? $grid
            : 'grid-4';
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

    protected function resolveSelectedCategorySlugs(Request $request): array
    {
        return $this->requestList($request, 'categories', 'category');
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
        $compactValue = $request->query($compactKey);

        if (is_array($compactValue)) {
            return $this->normalizeStringArray($compactValue);
        }

        $compact = trim((string) ($compactValue ?? ''));
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
        $min = null;
        $max = null;

        if ($compact !== '' && preg_match('/^\s*(\d+(?:\.\d+)?)\s*-\s*(\d+(?:\.\d+)?)\s*$/', $compact, $matches)) {
            $min = $this->normalizePriceInput($matches[1]);
            $max = $this->normalizePriceInput($matches[2]);
        } else {
            $min = $this->normalizePriceInput($request->query('min_price'));
            $max = $this->normalizePriceInput($request->query('max_price'));
        }

        if ($min !== null && $max !== null && $min > $max) {
            [$min, $max] = [$max, $min];
        }

        return [$min, $max];
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
                'measurementChartGroup',
                'category',
            ])
            ->visibleToFrontendVisitor()
            ->whereHas('productColors', fn (Builder $query) => $query->where('status', 'active'))
            ->where('is_active', true);
    }

    protected function applyProductsFilters(Builder $query, array $filters): Builder
    {
        $categoryIds = array_values(array_filter(array_map('intval', $filters['category_ids'] ?? [])));
        $minPrice = $this->normalizePriceInput($filters['min_price'] ?? null);
        $maxPrice = $this->normalizePriceInput($filters['max_price'] ?? null);
        $colorIds = $this->resolveStructureColorIds($this->normalizeStringArray($filters['colors'] ?? []));
        $sizes = $this->resolveSizeFilterTerms($this->normalizeStringArray($filters['sizes'] ?? []));
        $bodyFits = $this->normalizeStringArray($filters['body_fit'] ?? []);
        $dropTypes = $this->normalizeStringArray($filters['drop_type'] ?? []);
        $collections = $this->normalizeStringArray($filters['collections'] ?? []);
        $specialOffer = (bool) ($filters['special_offer'] ?? false);
        $searchTerm = $this->normalizeSearchTerm($filters['search'] ?? '');

        if ($minPrice !== null && $maxPrice !== null && $minPrice > $maxPrice) {
            [$minPrice, $maxPrice] = [$maxPrice, $minPrice];
        }

        if ($searchTerm !== '') {
            $this->applyProductsSearch($query, $searchTerm);
        }

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
                    ->whereIn('filter_color_id', $colorIds);
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

        if ($bodyFits !== []) {
            $query->whereIn('body_fit', $bodyFits);
        }

        if ($dropTypes !== []) {
            $query->whereIn('drop_type', $dropTypes);
        }

        if ($collections !== []) {
            $query->whereIn('collection', $collections);
        }

        if ($specialOffer) {
            $query->where('is_special_offer', true);
        }

        return $query;
    }


    protected function normalizeSearchTerm(mixed $value): string
    {
        $term = trim(strip_tags((string) $value));

        if ($term === '') {
            return '';
        }

        $term = preg_replace('/\s+/u', ' ', $term) ?: $term;

        return mb_substr($term, 0, 120);
    }

    protected function applyProductsSearch(Builder $query, string $term): void
    {
        $terms = $this->searchTerms($term);

        if ($terms === []) {
            return;
        }

        $query->where(function (Builder $searchQuery) use ($terms): void {
            foreach ($terms as $searchTerm) {
                $like = $this->searchLikeTerm($searchTerm);

                $searchQuery->where(function (Builder $termQuery) use ($like): void {
                    $this->addProductSearchConditions($termQuery, $like);

                    $termQuery->orWhereHas('complements.relatedProduct', function (Builder $relatedQuery) use ($like): void {
                        $relatedQuery
                            ->visibleToFrontendVisitor()
                            ->where('is_active', true)
                            ->where(function (Builder $relatedSearchQuery) use ($like): void {
                                $this->addProductSearchConditions($relatedSearchQuery, $like);
                            });
                    });
                });
            }
        });
    }

    protected function addProductSearchConditions(Builder $query, string $like): void
    {
        $query
            ->where('model_no', 'like', $like)
            ->orWhere('slug', 'like', $like)
            ->orWhere('title_ar', 'like', $like)
            ->orWhere('title_en', 'like', $like)
            ->orWhere('description_ar', 'like', $like)
            ->orWhere('description_en', 'like', $like)
            ->orWhere('structure', 'like', $like)
            ->orWhere('body_fit', 'like', $like)
            ->orWhere('drop_type', 'like', $like)
            ->orWhere('collection', 'like', $like)
            ->orWhereHas('category', function (Builder $categoryQuery) use ($like): void {
                $categoryQuery
                    ->where('title_ar', 'like', $like)
                    ->orWhere('title_en', 'like', $like)
                    ->orWhere('slug', 'like', $like);
            })
            ->orWhereHas('structureColor', function (Builder $structureColorQuery) use ($like): void {
                $structureColorQuery
                    ->where('code', 'like', $like)
                    ->orWhere('name_ar', 'like', $like)
                    ->orWhere('name_en', 'like', $like);
            })
            ->orWhereHas('productColors', function (Builder $colorQuery) use ($like): void {
                $colorQuery
                    ->where('status', 'active')
                    ->where(function (Builder $colorSearchQuery) use ($like): void {
                        $colorSearchQuery
                            ->where('color_code', 'like', $like)
                            ->orWhere('color_name_ar', 'like', $like)
                            ->orWhere('color_name_en', 'like', $like)
                            ->orWhere('color_hex', 'like', $like)
                            ->orWhereHas('filterColor', function (Builder $filterColorQuery) use ($like): void {
                                $filterColorQuery
                                    ->where('code', 'like', $like)
                                    ->orWhere('name_ar', 'like', $like)
                                    ->orWhere('name_en', 'like', $like);
                            });
                    });
            })
            ->orWhereHas('variants', function (Builder $variantQuery) use ($like): void {
                $variantQuery
                    ->whereHas('productColor', fn (Builder $colorQuery) => $colorQuery->where('status', 'active'))
                    ->where(function (Builder $variantSearchQuery) use ($like): void {
                        $variantSearchQuery
                            ->where('sku', 'like', $like)
                            ->orWhere('barcode', 'like', $like)
                            ->orWhereHas('size', function (Builder $sizeQuery) use ($like): void {
                                $sizeQuery
                                    ->where('code', 'like', $like)
                                    ->orWhere('name_ar', 'like', $like)
                                    ->orWhere('name_en', 'like', $like);
                            });
                    });
            })
            ->orWhereHas('details', function (Builder $detailQuery) use ($like): void {
                $detailQuery
                    ->where('is_active', true)
                    ->where(function (Builder $detailSearchQuery) use ($like): void {
                        $detailSearchQuery
                            ->where('label_ar', 'like', $like)
                            ->orWhere('label_en', 'like', $like)
                            ->orWhere('value_ar', 'like', $like)
                            ->orWhere('value_en', 'like', $like);
                    });
            });
    }

    protected function searchTerms(string $term): array
    {
        $normalized = $this->normalizeSearchTerm($term);

        if ($normalized === '') {
            return [];
        }

        return collect(preg_split('/\s+/u', $normalized) ?: [])
            ->map(fn ($item): string => trim((string) $item))
            ->filter(fn (string $item): bool => $item !== '')
            ->unique()
            ->take(8)
            ->values()
            ->all();
    }

    protected function searchLikeTerm(string $term): string
    {
        return '%' . addcslashes($term, '\\%_') . '%';
    }

    protected function buildFilterCategories(Collection $categories, array $filters): Collection
    {
        $baseQuery = $this->newProductsListingQuery();
        $this->applyProductsFilters($baseQuery, $filters);

        return $this->pruneFilterCategories(
            $this->applyCategoryCounts($categories, $baseQuery)
        );
    }

    protected function buildColorOptions(array $filters, string $locale, array $selectedColors): Collection
    {
        $selected = collect($selectedColors)
            ->map(fn ($value) => $this->makeFilterSlug((string) $value))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $baseQuery = $this->newProductsListingQuery();
        $this->applyProductsFilters($baseQuery, array_merge($filters, ['colors' => []]));

        $productIds = (clone $baseQuery)->select('products.id');

        $colorCounts = ProductColor::query()
            ->where('status', 'active')
            ->whereNotNull('filter_color_id')
            ->whereIn('product_id', $productIds)
            ->selectRaw('filter_color_id, COUNT(DISTINCT product_id) as products_count')
            ->groupBy('filter_color_id')
            ->pluck('products_count', 'filter_color_id');

        if ($colorCounts->isEmpty()) {
            return collect();
        }

        $fallbackHexByColorId = ProductColor::query()
            ->where('status', 'active')
            ->whereIn('filter_color_id', $colorCounts->keys()->all())
            ->whereNotNull('color_hex')
            ->where('color_hex', '!=', '')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['filter_color_id', 'color_hex'])
            ->groupBy('filter_color_id')
            ->map(fn (Collection $items): string => (string) $items->first()->color_hex);

        return Color::query()
            ->where('status', 'active')
            ->whereIn('id', $colorCounts->keys()->all())
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'code', 'name_ar', 'name_en', 'hex'])
            ->map(function (Color $color) use ($locale, $selected, $colorCounts, $fallbackHexByColorId): array {
                $value = $this->structureColorFilterKey($color);
                $label = trim((string) ($locale === 'ar'
                    ? ($color->name_ar ?: $color->name_en ?: $color->code ?: $color->id)
                    : ($color->name_en ?: $color->name_ar ?: $color->code ?: $color->id)));

                $hex = $this->normalizeHexColor($color->hex)
                    ?? $this->normalizeHexColor($fallbackHexByColorId[$color->id] ?? null);

                return [
                    'value' => $value,
                    'label' => $label,
                    'hex' => (string) ($hex ?? ''),
                    'fallback_key' => trim((string) ($color->code ?: $color->name_en ?: $color->name_ar ?: '')),
                    'count' => (int) ($colorCounts[$color->id] ?? 0),
                    'selected' => in_array($value, $selected, true),
                ];
            })
            ->filter(fn (array $option): bool => $option['label'] !== '' && $option['value'] !== '')
            ->values();
    }

    protected function applyStructureColorActiveSwatchConstraint(Builder $query, ?array $colorIds = null): void
    {
        $query->whereNotNull('structure_color_id')
            ->whereExists(function ($subQuery) use ($colorIds): void {
                $subQuery
                    ->selectRaw('1')
                    ->from('product_colors')
                    ->whereColumn('product_colors.product_id', 'products.id')
                    ->where('product_colors.status', 'active')
                    ->whereExists(function ($colorQuery) use ($colorIds): void {
                        $colorQuery
                            ->selectRaw('1')
                            ->from('colors as filter_colors')
                            ->whereColumn('filter_colors.id', 'products.structure_color_id');

                        if ($colorIds !== null) {
                            $colorQuery->whereIn('filter_colors.id', $colorIds);
                        }

                        $colorQuery->where(function ($matchQuery): void {
                            $matchQuery
                                ->where(function ($codeQuery): void {
                                    $codeQuery
                                        ->whereNotNull('filter_colors.code')
                                        ->where('filter_colors.code', '!=', '')
                                        ->whereRaw('LOWER(TRIM(product_colors.color_code)) COLLATE utf8mb4_unicode_ci = LOWER(TRIM(filter_colors.code)) COLLATE utf8mb4_unicode_ci');
                                })
                                ->orWhere(function ($nameArQuery): void {
                                    $nameArQuery
                                        ->whereNotNull('filter_colors.name_ar')
                                        ->where('filter_colors.name_ar', '!=', '')
                                        ->whereRaw("REPLACE(TRIM(product_colors.color_name_ar), ' ', '') COLLATE utf8mb4_unicode_ci = REPLACE(TRIM(filter_colors.name_ar), ' ', '') COLLATE utf8mb4_unicode_ci");
                                })
                                ->orWhere(function ($nameEnQuery): void {
                                    $nameEnQuery
                                        ->whereNotNull('filter_colors.name_en')
                                        ->where('filter_colors.name_en', '!=', '')
                                        ->whereRaw('LOWER(TRIM(product_colors.color_name_en)) COLLATE utf8mb4_unicode_ci = LOWER(TRIM(filter_colors.name_en)) COLLATE utf8mb4_unicode_ci');
                                });
                        });
                    });
            });
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

    protected function buildBodyFitOptions(array $filters, array $selectedBodyFit): Collection
    {
        $selected = $this->normalizeStringArray($selectedBodyFit);
        $query = $this->newProductsListingQuery();
        $this->applyProductsFilters($query, $filters);

        return (clone $query)
            ->whereNotNull('body_fit')
            ->where('body_fit', '!=', '')
            ->selectRaw('body_fit, COUNT(DISTINCT products.id) as products_count')
            ->groupBy('body_fit')
            ->orderBy('body_fit')
            ->get()
            ->map(fn ($row): array => [
                'value' => (string) $row->body_fit,
                'label' => (string) $row->body_fit,
                'count' => (int) $row->products_count,
                'selected' => in_array((string) $row->body_fit, $selected, true),
            ])
            ->values();
    }

    protected function buildDropOptions(array $filters, array $selectedDropTypes): Collection
    {
        $selected = $this->normalizeStringArray($selectedDropTypes);
        $query = $this->newProductsListingQuery();
        $this->applyProductsFilters($query, $filters);

        return (clone $query)
            ->whereNotNull('drop_type')
            ->where('drop_type', '!=', '')
            ->selectRaw('drop_type, COUNT(DISTINCT products.id) as products_count')
            ->groupBy('drop_type')
            ->orderBy('drop_type')
            ->get()
            ->map(fn ($row): array => [
                'value' => (string) $row->drop_type,
                'label' => (string) $row->drop_type,
                'count' => (int) $row->products_count,
                'selected' => in_array((string) $row->drop_type, $selected, true),
            ])
            ->values();
    }

    protected function buildCollectionOptions(array $filters, array $selectedCollections): Collection
    {
        $selected = $this->normalizeStringArray($selectedCollections);
        $query = $this->newProductsListingQuery();
        $this->applyProductsFilters($query, array_merge($filters, ['collections' => []]));

        return (clone $query)
            ->whereNotNull('collection')
            ->where('collection', '!=', '')
            ->selectRaw('collection, COUNT(DISTINCT products.id) as products_count')
            ->groupBy('collection')
            ->orderBy('collection')
            ->get()
            ->map(fn ($row): array => [
                'value' => (string) $row->collection,
                'label' => (string) $row->collection,
                'count' => (int) $row->products_count,
                'selected' => in_array((string) $row->collection, $selected, true),
            ])
            ->values();
    }

    protected function buildSpecialOfferOption(array $filters, bool $selectedSpecialOffer, string $locale): ?array
    {
        $option = $this->buildSpecialOfferOptions($filters, $selectedSpecialOffer)->first();

        return is_array($option) ? $option : null;
    }
    protected function buildSpecialOfferOptions(array $filters, bool $selectedSpecialOffer): Collection
    {
        $query = $this->newProductsListingQuery();
        $this->applyProductsFilters($query, array_merge($filters, ['special_offer' => false]));

        $count = (int) (clone $query)
            ->where('is_special_offer', true)
            ->count();

        if ($count <= 0 && ! $selectedSpecialOffer) {
            return collect();
        }

        return collect([
            [
                'value' => 'offer',
                'label' => app()->getLocale() === 'ar' ? 'عروض خاصة' : 'Special offers',
                'count' => $count,
                'selected' => $selectedSpecialOffer,
            ],
        ]);
    }
    protected function buildPriceStats(array $filters, ?float $selectedMin, ?float $selectedMax): array
    {
        $query = $this->newProductsListingQuery();
        $priceIndependentFilters = array_merge($filters, [
            'min_price' => null,
            'max_price' => null,
        ]);
        $this->applyProductsFilters($query, $priceIndependentFilters);

        $minBase = (float) ((clone $query)->min('price') ?? 0);
        $maxBase = (float) ((clone $query)->max('price') ?? 0);
        $selectedMinBase = $selectedMin !== null ? $this->normalizePriceInput($selectedMin) : $minBase;
        $selectedMaxBase = $selectedMax !== null ? $this->normalizePriceInput($selectedMax) : $maxBase;

        if ($selectedMinBase !== null && $selectedMaxBase !== null && $selectedMinBase > $selectedMaxBase) {
            [$selectedMinBase, $selectedMaxBase] = [$selectedMaxBase, $selectedMinBase];
        }

        $displayMin = $this->basePriceToDisplay($minBase);
        $displayMax = $this->basePriceToDisplay($maxBase);
        $displayUpper = max(1, (int) ceil($displayMax));
        $selectedMinDisplay = $this->basePriceToDisplay($selectedMinBase);
        $selectedMaxDisplay = $this->basePriceToDisplay($selectedMaxBase);
        $currencyContext = $this->currentCurrencyContext();

        return [
            'base_min_limit' => max(0, (int) floor($minBase)),
            'base_max_limit' => max(1, (int) ceil($maxBase)),
            'selected_min_base' => max(0, (int) floor($selectedMinBase ?? $minBase)),
            'selected_max_base' => max(1, (int) ceil($selectedMaxBase ?? $maxBase)),
            'display_min_limit' => max(0, (int) floor($displayMin)),
            'display_max_limit' => $displayUpper,
            'selected_min_display' => max(0, (int) floor($selectedMinDisplay)),
            'selected_max_display' => max(1, (int) ceil($selectedMaxDisplay)),
            'min_limit' => max(0, (int) floor($displayMin)),
            'max_limit' => $displayUpper,
            'selected_min' => max(0, (int) floor($selectedMinDisplay)),
            'selected_max' => max(1, (int) ceil($selectedMaxDisplay)),
            'currency' => $currencyContext['currency'],
            'symbol' => $currencyContext['symbol'],
            'rate' => $currencyContext['rate'],
        ];
    }

    protected function buildActiveFilterChips(
        EloquentCollection $selectedCategoryModels,
        array $selectedColors,
        array $selectedSizes,
        array $selectedBodyFit,
        array $selectedDropType,
        array $selectedCollections,
        bool $selectedSpecialOffer,
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

        foreach ($selectedBodyFit as $bodyFit) {
            $chips[] = [
                'type' => 'body_fit',
                'value' => (string) $bodyFit,
                'label' => (string) $bodyFit,
            ];
        }

        foreach ($selectedDropType as $dropType) {
            $chips[] = [
                'type' => 'drop_type',
                'value' => (string) $dropType,
                'label' => (string) $dropType,
            ];
        }

        foreach ($selectedCollections as $collection) {
            $chips[] = [
                'type' => 'collection',
                'value' => (string) $collection,
                'label' => (string) $collection,
            ];
        }

        if ($selectedSpecialOffer) {
            $chips[] = [
                'type' => 'special_offer',
                'value' => 'offer',
                'label' => $locale === 'ar' ? 'عروض خاصة' : 'Special offers',
            ];
        }

        $defaultMinBase = (float) ($priceStats['base_min_limit'] ?? 0);
        $defaultMaxBase = (float) ($priceStats['base_max_limit'] ?? 0);
        $resolvedMinBase = $selectedMin ?? $defaultMinBase;
        $resolvedMaxBase = $selectedMax ?? $defaultMaxBase;

        if ($resolvedMinBase !== $defaultMinBase || $resolvedMaxBase !== $defaultMaxBase) {
            $currency = (string) ($priceStats['currency'] ?? 'SYP');
            $rangeMin = (int) floor($this->basePriceToDisplay($resolvedMinBase));
            $rangeMax = (int) ceil($this->basePriceToDisplay($resolvedMaxBase));

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

    protected function resolveStructureColorIds(array $selectedColors): array
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

        return Color::query()
            ->select(['id', 'code', 'name_ar', 'name_en'])
            ->where('status', 'active')
            ->get()
            ->filter(fn (Color $color): bool => in_array($this->structureColorFilterKey($color), $selected, true))
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();
    }

    protected function resolveColorChipLabel(string $value, string $locale): string
    {
        $slug = $this->makeFilterSlug((string) $value);

        $color = Color::query()
            ->select(['id', 'code', 'name_ar', 'name_en'])
            ->where('status', 'active')
            ->get()
            ->first(fn (Color $color): bool => $this->structureColorFilterKey($color) === $slug);

        if (! $color instanceof Color) {
            return $value;
        }

        return trim((string) ($locale === 'ar'
            ? ($color->name_ar ?: $color->name_en ?: $color->code ?: $value)
            : ($color->name_en ?: $color->name_ar ?: $color->code ?: $value)));
    }

    protected function structureColorFilterKey(Color $color): string
    {
        if (filled($color->code)) {
            return $this->makeFilterSlug((string) $color->code);
        }

        if (filled($color->name_en)) {
            return $this->makeFilterSlug((string) $color->name_en);
        }

        if (filled($color->name_ar)) {
            return $this->makeFilterSlug((string) $color->name_ar);
        }

        return $this->makeFilterSlug((string) $color->id);
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

    protected function filterCategoriesTree(?Category $baseCategory = null): Collection
    {
        $categories = Category::query()
            ->orderBy('sort_order')
            ->orderBy('title_ar')
            ->get();

        $tree = $this->nestFilterCategories($categories);

        if (! $baseCategory instanceof Category) {
            return $tree;
        }

        if ($baseCategory->isLeaf()) {
            return collect();
        }

        return $this->extractFilterSubtree($tree, (int) $baseCategory->getKey());
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

    protected function extractFilterSubtree(Collection $tree, int $baseCategoryId): Collection
    {
        foreach ($tree as $category) {
            if ((int) $category->getKey() === $baseCategoryId) {
                return $category->relationLoaded('children')
                    ? $category->children->values()
                    : collect();
            }

            if ($category->relationLoaded('children') && $category->children->isNotEmpty()) {
                $found = $this->extractFilterSubtree($category->children, $baseCategoryId);

                if ($found->isNotEmpty()) {
                    return $found;
                }
            }
        }

        return collect();
    }

    protected function applyCategoryCounts(Collection $categories, Builder $baseQuery): Collection
    {
        return $categories->map(function (Category $category) use ($baseQuery): Category {
            if ($category->relationLoaded('children')) {
                $category->setRelation('children', $this->applyCategoryCounts($category->children, $baseQuery));
            }

            $leafIds = $this->collectLeafCategoryIds($category);
            $category->setAttribute('products_count', $leafIds === []
                ? 0
                : (clone $baseQuery)->whereIn('category_id', $leafIds)->count());
            $category->setAttribute('is_selectable_leaf', $category->relationLoaded('children')
                ? $category->children->isEmpty()
                : $category->children()->doesntExist());

            return $category;
        })->values();
    }

    protected function pruneFilterCategories(Collection $categories): Collection
    {
        return $categories
            ->map(function (Category $category): ?Category {
                if ($category->relationLoaded('children')) {
                    $category->setRelation('children', $this->pruneFilterCategories($category->children));
                }

                $hasChildren = $category->relationLoaded('children') && $category->children->isNotEmpty();
                $count = (int) ($category->getAttribute('products_count') ?? 0);

                if ($count <= 0 && ! $hasChildren) {
                    return null;
                }

                return $category;
            })
            ->filter()
            ->values();
    }

    protected function scopeSelectedCategoryModelsToBase(EloquentCollection $selectedCategories, ?Category $baseCategory): EloquentCollection
    {
        if (! $baseCategory instanceof Category || $selectedCategories->isEmpty()) {
            return $selectedCategories;
        }

        $allowedLeafIds = $this->collectLeafCategoryIds($baseCategory);

        if ($allowedLeafIds === []) {
            return new EloquentCollection();
        }

        return $selectedCategories
            ->filter(fn (Category $category): bool => in_array((int) $category->getKey(), $allowedLeafIds, true))
            ->values();
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
