<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Trader;
use App\Services\FrontHomePageDataService;
use App\Services\ProductPresentationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class FrontTraderProductsController extends Controller
{
    public function __construct(
        protected FrontHomePageDataService $homePageData,
        protected ProductPresentationService $productPresentation,
    ) {
    }

    public function index(): View
    {
        /** @var Trader $trader */
        $trader = Auth::guard('trader')->user();
        $trader->loadMissing('wholesaleCustomerGroup');

        $groupId = $trader->wholesale_customer_group_id;
        $products = collect();

        if ($groupId !== null) {
            $products = Product::query()
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
                    ->where('max_quantity', '>', 0))
                ->latest('id')
                ->paginate(12);
        }

        $locale = app()->getLocale();
        $isArabic = $locale === 'ar';
        $productPresentations = $products instanceof \Illuminate\Contracts\Pagination\Paginator
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
                ['label' => __('front.nav.home'), 'url' => route('front.home')],
                ['label' => $isArabic ? 'لوحة التاجر' : 'Trader Dashboard', 'url' => route('front.trader.dashboard')],
                ['label' => $isArabic ? 'منتجات الجملة' : 'Wholesale Products', 'url' => null],
            ],
            'trader' => $trader,
            'products' => $products,
            'wholesale_group' => $trader->wholesaleCustomerGroup,
            'product_presentations' => $productPresentations,
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
            ? ($product->title_ar ?: $product->title_en ?: $product->model_no)
            : ($product->title_en ?: $product->title_ar ?: $product->model_no)));

        return view('frontend.pages.trader.product-details', array_merge($this->homePageData->build(), [
            'page_title' => $title,
            'page_subtitle' => $isArabic
                ? 'تفاصيل منتج الجملة المتاح لمجموعتك فقط.'
                : 'Wholesale product details available only for your group.',
            'breadcrumb_items' => [
                ['label' => __('front.nav.home'), 'url' => route('front.home')],
                ['label' => $isArabic ? 'لوحة التاجر' : 'Trader Dashboard', 'url' => route('front.trader.dashboard')],
                ['label' => $isArabic ? 'منتجات الجملة' : 'Wholesale Products', 'url' => route('front.trader.products.index')],
                ['label' => $title, 'url' => null],
            ],
            'trader' => $trader,
            'product' => $product,
            'wholesale_group' => $trader->wholesaleCustomerGroup,
            'product_presentation' => $productPresentation,
        ]));
    }
}
