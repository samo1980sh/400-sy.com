<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Trader;
use App\Services\FrontHomePageDataService;
use App\Services\ProductPresentationService;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class FrontTraderProductsController extends Controller
{
    public function __construct(
        protected FrontHomePageDataService $homePageData,
        protected ProductPresentationService $productPresentation,
    ) {
    }

    public function index(Request $request): View
    {
        /** @var Trader $trader */
        $trader = Auth::guard('trader')->user();
        $trader->loadMissing('wholesaleCustomerGroup');

        $locale = app()->getLocale();
        $isArabic = $locale === 'ar';
        $groupId = $trader->wholesale_customer_group_id;
        $products = collect();
        $categoryOptions = collect();
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'category_id' => (string) $request->query('category_id', ''),
        ];

        if ($groupId !== null) {
            $baseQuery = Product::query()
                ->with([
                    'category',
                    'productColors.filterColor',
                    'variants.size',
                    'structureColor',
                    'wholesaleColors',
                    'wholesaleAvailabilities' => fn ($query) => $query
                        ->where('wholesale_customer_group_id', $groupId)
                        ->where('max_quantity', '>', 0),
                    'wholesaleAvailabilities.wholesaleColor',
                    'wholesaleQuantities',
                ])
                ->where('is_active', true)
                ->where('show_web', true)
                ->where('show_wholesale', true)
                ->whereHas('wholesaleAvailabilities', fn ($query) => $query
                    ->where('wholesale_customer_group_id', $groupId)
                    ->where('max_quantity', '>', 0));

            $availableCategoryIds = Product::query()
                ->whereNotNull('category_id')
                ->where('is_active', true)
                ->where('show_web', true)
                ->where('show_wholesale', true)
                ->whereHas('wholesaleAvailabilities', fn ($query) => $query
                    ->where('wholesale_customer_group_id', $groupId)
                    ->where('max_quantity', '>', 0))
                ->distinct()
                ->pluck('category_id');

            $categoryOptions = Category::query()
                ->whereIn('id', $availableCategoryIds)
                ->orderBy($isArabic ? 'title_ar' : 'title_en')
                ->get();

            $products = $baseQuery
                ->when($filters['q'] !== '', fn ($query) => $query->where(function ($searchQuery) use ($filters) {
                    $term = $filters['q'];

                    $searchQuery
                        ->where('model_no', 'like', "%{$term}%")
                        ->orWhere('title_ar', 'like', "%{$term}%")
                        ->orWhere('title_en', 'like', "%{$term}%");
                }))
                ->when($filters['category_id'] !== '', fn ($query) => $query->where('category_id', (int) $filters['category_id']))
                ->latest('id')
                ->paginate(12)
                ->withQueryString();
        }

        $productPresentations = $products instanceof Paginator
            ? collect($products->items())->mapWithKeys(fn (Product $item): array => [
                $item->getKey() => $this->productPresentation->presentProduct($item, $locale, [], null),
            ])->all()
            : [];

        return view('frontend.pages.trader.products', array_merge($this->homePageData->build(), [
            'page_title' => $isArabic ? 'منتجات الجملة' : 'Wholesale Products',
            'page_subtitle' => $isArabic
                ? 'المنتجات المتاحة حسب مجموعة الجملة المرتبطة بحسابك.'
                : 'Products available for your assigned wholesale group.',
            'breadcrumb_items' => [
                ['label' => $isArabic ? 'لوحة التاجر' : 'Trader Dashboard', 'url' => route('front.trader.dashboard')],
                ['label' => $isArabic ? 'منتجات الجملة' : 'Wholesale Products', 'url' => null],
            ],
            'trader' => $trader,
            'products' => $products,
            'wholesale_group' => $trader->wholesaleCustomerGroup,
            'product_presentations' => $productPresentations,
            'category_options' => $categoryOptions,
            'filters' => $filters,
            'trader_cart_count' => count(session()->get('front_trader_wholesale_cart_'.$trader->getKey(), [])),
        ]));
    }

    public function show(Product $product): View
    {
        /** @var Trader $trader */
        $trader = Auth::guard('trader')->user();
        $trader->loadMissing('wholesaleCustomerGroup');

        $groupId = $trader->wholesale_customer_group_id;
        abort_if($groupId === null, 404);

        $product = Product::query()
            ->with([
                'category',
                'productColors.filterColor',
                'variants.size',
                'structureColor',
                'wholesaleColors',
                'wholesaleAvailabilities' => fn ($query) => $query
                    ->where('wholesale_customer_group_id', $groupId)
                    ->where('max_quantity', '>', 0),
                'wholesaleAvailabilities.wholesaleColor',
                'wholesaleQuantities',
                'measurementChartGroup',
                'measurementCharts',
                'details',
            ])
            ->whereKey($product->getKey())
            ->where('is_active', true)
            ->where('show_web', true)
            ->where('show_wholesale', true)
            ->whereHas('wholesaleAvailabilities', fn ($query) => $query
                ->where('wholesale_customer_group_id', $groupId)
                ->where('max_quantity', '>', 0))
            ->firstOrFail();

        $locale = app()->getLocale();
        $isArabic = $locale === 'ar';
        $productPresentation = $this->productPresentation->presentProduct($product, $locale, [], null);
        $title = (string) ($productPresentation['title'] ?? ($isArabic
            ? ($product->title_ar ?: $product->title_en ?: $this->traderProductCode($product->model_no))
            : ($product->title_en ?: $product->title_ar ?: $this->traderProductCode($product->model_no))));

        return view('frontend.pages.trader.product-details', array_merge($this->homePageData->build(), [
            'page_title' => $title,
            'page_subtitle' => $isArabic
                ? 'تفاصيل منتج الجملة المتاح لمجموعتك فقط.'
                : 'Wholesale product details available only for your group.',
            'breadcrumb_items' => [
                ['label' => $isArabic ? 'لوحة التاجر' : 'Trader Dashboard', 'url' => route('front.trader.dashboard')],
                ['label' => $isArabic ? 'منتجات الجملة' : 'Wholesale Products', 'url' => route('front.trader.products.index')],
                ['label' => $title, 'url' => null],
            ],
            'trader' => $trader,
            'product' => $product,
            'wholesale_group' => $trader->wholesaleCustomerGroup,
            'product_presentation' => $productPresentation,
            'trader_cart_count' => count(session()->get('front_trader_wholesale_cart_'.$trader->getKey(), [])),
        ]));
    }

    protected function traderProductCode(?string $code): string
    {
        return Str::substr((string) $code, 3);
    }
}
