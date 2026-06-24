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
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class FrontHomePageDataService
{
    public function __construct(
        protected ProductPresentationService $productPresenter,
        protected FrontCartService $cartService,
        protected FrontWishlistService $wishlistService,
    ) {
    }

    public function build(?Category $category = null): array
    {
        $locale = app()->getLocale();
        $categoryIds = $category ? $this->collectCategoryBranchIds($category) : [];
        $cartState = $this->cartService->state();
        $wishlistState = $this->wishlistService->cleanupVisibleIds();

        $productsQuery = Product::query()
            ->with([
                'productColors' => fn ($query) => $query
                    ->where('status', 'active')
                    ->orderBy('sort_order')
                    ->orderBy('id'),
                'productColors.filterColor',
                'variants' => fn ($query) => $query
                    ->whereHas('productColor', fn ($colorQuery) => $colorQuery->where('status', 'active'))
                    ->with('size'),
                'productColors.variants.size',
                'measurementCharts',
                'category',
            ])
            ->visibleToFrontendVisitor()
            ->whereHas('productColors', fn ($query) => $query->where('status', 'active'))
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

        if ($trendingProducts->isEmpty()) {
            $trendingProducts = (clone $productsQuery)
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->limit(4)
                ->get();
        }

        if ($newProducts->isEmpty()) {
            $newProducts = (clone $productsQuery)
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->limit(4)
                ->get();
        }

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
            'wishlist_state' => $wishlistState,
            'wishlist_count' => $wishlistState['count'] ?? 0,
            'wishlist_url' => route('front.wishlist.index'),
            'customer_authenticated' => auth('customer')->check(),
            'authenticated_customer' => auth('customer')->user(),
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

    public function presentProduct(Product $product, ?string $locale = null, array $preferredFilterColorIds = [], ?int $colorLimit = 4): array
    {
        $presentation = $this->productPresenter->presentProduct($product, $locale, $preferredFilterColorIds, $colorLimit);

        if (filled($product->slug ?? null)) {
            $presentation['wishlist_add_url'] = route('front.wishlist.add', $product->slug);
            $presentation['wishlist_remove_url'] = route('front.wishlist.remove', $product->slug);
            $presentation['is_in_wishlist'] = $this->wishlistService->has($product);
        }

        return $presentation;
    }

    public function buildBranchesPage(): array
    {
        $locale = app()->getLocale();
        $data = $this->build();
        $data['page_title'] = __('front.nav.branches');
        $data['branch_categories'] = $this->buildBranchCategories($locale);

        return $data;
    }

    protected function buildBranchCategories(string $locale): Collection
    {
        return \App\Models\BranchCategory::query()
            ->where('status', 'active')
            ->with(['branches' => function ($query): void {
                $query
                    ->where('status', 'active')
                    ->orderBy('sort_order');
            }])
            ->orderBy('sort_order')
            ->get()
            ->map(function (\App\Models\BranchCategory $category) use ($locale): array {
                $branches = $category->branches
                    ->map(function (\App\Models\Branch $branch) use ($locale): array {
                        $gallery = collect($branch->gallery_images ?? [])
                            ->filter()
                            ->map(fn (string $image): string => Storage::disk('public')->url($image))
                            ->values();

                        return [
                            'name' => $this->localizedValue($branch->name_ar ?? null, $branch->name_en ?? null, $locale) ?: __('front.branches.untitled'),
                            'type' => $branch->type ?: '',
                            'address' => trim((string) $this->localizedValue($branch->address_ar ?? null, $branch->address_en ?? null, $locale)),
                            'description' => trim((string) $this->localizedValue($branch->description_ar ?? null, $branch->description_en ?? null, $locale)),
                            'notes' => trim((string) $this->localizedValue($branch->notes_ar ?? null, $branch->notes_en ?? null, $locale)),
                            'phone' => $branch->phone ?: '',
                            'mobile' => $branch->mobile ?: '',
                            'whatsapp' => $branch->whatsapp ?: '',
                            'email' => $branch->email ?: '',
                            'map_url' => $branch->map_url ?: '',
                            'image' => filled($branch->main_image) ? Storage::disk('public')->url($branch->main_image) : asset('images/shop/store/ourstore1.png'),
                            'gallery' => $gallery,
                        ];
                    })
                    ->values();

                return [
                    'name' => $this->localizedValue($category->name_ar ?? null, $category->name_en ?? null, $locale) ?: __('front.branches.untitled'),
                    'description' => trim((string) $this->localizedValue($category->description_ar ?? null, $category->description_en ?? null, $locale)),
                    'branches' => $branches,
                ];
            })
            ->filter(fn (array $category): bool => $category['branches']->isNotEmpty())
            ->values();
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
                'hours' => $this->demoText($locale, 'السبت - الخميس، 8:30 صباحاً - 10:30 مساءً' . "\n" . 'الجمعة مغلق', 'Sat - Thu, 8:30am - 10:30pm' . "\n" . 'Friday Closed'),
                'image' => asset('images/shop/store/ourstore1.png'),
                'phone' => '+963 11 691 2400',
                'email' => 'info.sy@400-online.com',
            ],
            [
                'name' => $this->demoText($locale, 'الفرع الثاني', 'Branch Two'),
                'address' => $this->demoText($locale, 'اختبر عنوان الفرع وموقعه هنا', 'Experience the branch address and location'),
                'hours' => $this->demoText($locale, 'السبت - الخميس، 8:30 صباحاً - 10:30 مساءً' . "\n" . 'الجمعة مغلق', 'Sat - Thu, 8:30am - 10:30pm' . "\n" . 'Friday Closed'),
                'image' => asset('images/shop/store/ourstore2.png'),
                'phone' => '+963 11 691 2400',
                'email' => 'info.sy@400-online.com',
            ],
            [
                'name' => $this->demoText($locale, 'الفرع الثالث', 'Third branch'),
                'address' => $this->demoText($locale, 'اختبر عنوان الفرع وموقعه هنا', 'Experience the branch address and location'),
                'hours' => $this->demoText($locale, 'السبت - الخميس، 8:30 صباحاً - 10:30 مساءً' . "\n" . 'الجمعة مغلق', 'Sat - Thu, 8:30am - 10:30pm' . "\n" . 'Friday Closed'),
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
            'href' => route('front.branches.index'),
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
