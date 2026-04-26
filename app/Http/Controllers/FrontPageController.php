<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\CompanyPage;
use App\Services\FrontCartService;
use App\Services\FrontHomePageDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Str;

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

    public function productsIndex(Request $request): View
    {
        $sort = $this->normalizeProductsSort((string) $request->query('sort', 'featured'));
        $categorySlug = trim((string) $request->query('category', ''));
        $category = null;

        if ($categorySlug !== '') {
            $category = Category::query()
                ->where('slug', $categorySlug)
                ->first();
        }

        return $this->renderProductsListing($category, $request, $sort);
    }

    protected function renderProductsListing(?Category $category = null, ?Request $request = null, string $sort = 'featured'): View|JsonResponse
    {
        $shell = $this->homePageData->build();
        $locale = app()->getLocale();
        $categoryIds = [];
        $categoryTrail = collect();

        if ($category instanceof Category) {
            $categoryIds = $this->collectCategoryBranchIds($category);
            $categoryTrail = $category->breadcrumbTrail();
        }

        $minPrice = $request ? $this->normalizePriceInput($request->query('min_price')) : null;
        $maxPrice = $request ? $this->normalizePriceInput($request->query('max_price')) : null;
        $query = Product::query()
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
            $query->whereIn('category_id', $categoryIds);
        }

        if ($minPrice !== null) {
            $query->whereRaw('COALESCE(price, 0) >= ?', [$minPrice]);
        }

        if ($maxPrice !== null) {
            $query->whereRaw('COALESCE(price, 0) <= ?', [$maxPrice]);
        }

        $this->applyProductsSort($query, $sort);

        $paginator = $query->paginate(16)->withQueryString();
        $products = $paginator->getCollection()
            ->map(fn (Product $product): array => $this->homePageData->presentProduct($product, $locale))
            ->values();

        $paginator->setCollection($products);

        if ($request?->boolean('load_more')) {
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

        if ($category instanceof Category) {
            foreach ($categoryTrail as $trailCategory) {
                $breadcrumbItems[] = [
                    'label' => $locale === 'ar'
                        ? ($trailCategory->title_ar ?: $trailCategory->title_en ?: $trailCategory->slug)
                        : ($trailCategory->title_en ?: $trailCategory->title_ar ?: $trailCategory->slug),
                    'url' => route('front.category', $trailCategory->slug),
                ];
            }
        }

        $categories = collect($shell['nav_categories'] ?? [])
            ->filter(fn ($item): bool => $item instanceof Category && $item->parent_id === null)
            ->values();

        $pageTitle = $locale === 'ar'
            ? 'المنتجات'
            : 'Products';

        if ($category instanceof Category) {
            $pageTitle = $locale === 'ar'
                ? ($category->title_ar ?: $category->title_en ?: $pageTitle)
                : ($category->title_en ?: $category->title_ar ?: $pageTitle);
        }

        return view('frontend.pages.products.index', array_merge($shell, [
            'page_title' => $pageTitle,
            'page_subtitle' => $category instanceof Category
                ? ($locale === 'ar'
                    ? 'تصفح منتجات هذا التصنيف'
                    : 'Browse products in this category')
                : ($locale === 'ar'
                    ? 'تصفح مجموعة المنتجات مع الفلتر والفرز'
                    : 'Browse the product catalog with filters and sorting'),
            'breadcrumb_items' => $breadcrumbItems,
            'products' => $paginator,
            'selected_sort' => $sort,
            'selected_category_slug' => $category instanceof Category ? $category->slug : '',
            'selected_min_price' => $minPrice,
            'selected_max_price' => $maxPrice,
            'filter_categories' => $categories,
        ]));
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
}
