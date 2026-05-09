@extends('frontend.layouts.app')

@php
    $products = $products ?? null;
    $selectedSort = $selected_sort ?? 'featured';
    $resultCount = $products instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator ? $products->total() : 0;
    $sortOptions = [
        'featured' => app()->getLocale() === 'ar' ? 'مميز' : 'Featured',
        'best_selling' => app()->getLocale() === 'ar' ? 'الأكثر مبيعًا' : 'Best selling',
        'price_asc' => app()->getLocale() === 'ar' ? 'السعر: من الأقل للأعلى' : 'Price, low to high',
        'price_desc' => app()->getLocale() === 'ar' ? 'السعر: من الأعلى للأقل' : 'Price, high to low',
        'oldest' => app()->getLocale() === 'ar' ? 'الأقدم أولًا' : 'Date, old to new',
        'newest' => app()->getLocale() === 'ar' ? 'الأحدث أولًا' : 'Date, new to old',
    ];
@endphp

@section('title', $page_title ?? (app()->getLocale() === 'ar' ? 'المنتجات' : 'Products'))
@section('meta_description', $page_subtitle ?? __('front.brand'))

@push('styles')
    <style>
        body.front-shell {
            overflow-x: hidden;
        }

        .tf-page-title {
            background-attachment: scroll;
        }
    </style>
@endpush

@section('content')
    @include('frontend.partials.announcement-bar', [
        'tickerItems' => $ticker_items ?? [],
        'socialLinks' => $social_links ?? [],
    ])

    @include('frontend.partials.header', [
        'navCategories' => $nav_categories ?? [],
        'currencyOptions' => $currency_options ?? [],
        'siteName' => $site_name ?? config('app.name', '400 Four HUNDRED'),
        'cartCount' => $cart_count ?? 0,
    ])

    <main>
        @include('frontend.partials.page-title', [
            'title' => $page_title ?? (app()->getLocale() === 'ar' ? 'المنتجات' : 'Products'),
            'subtitle' => $page_subtitle ?? '',
            'breadcrumbs' => $breadcrumb_items ?? [],
        ])

        <section class="flat-spacing-1">
            <div class="container">
                @include('frontend.partials.shop-toolbar', [
                    'result_count' => $resultCount,
                    'sort_options' => $sort_options ?? $sortOptions,
                    'selected_sort' => $selectedSort,
                ])

                @include('frontend.partials.shop-filter', [
                    'filter_categories' => $filter_categories ?? [],
                    'selected_category_slugs' => $selected_category_slugs ?? [],
                    'selected_sort' => $selectedSort,
                    'selected_min_price' => $selected_min_price ?? null,
                    'selected_max_price' => $selected_max_price ?? null,
                    'selected_colors' => $selected_colors ?? [],
                    'selected_sizes' => $selected_sizes ?? [],
                    'selected_body_fit' => $selected_body_fit ?? [],
                    'selected_drop_type' => $selected_drop_type ?? [],
                    'filter_color_options' => $filter_color_options ?? collect(),
                    'filter_size_options' => $filter_size_options ?? collect(),
                    'filter_body_fit_options' => $filter_body_fit_options ?? collect(),
                    'filter_drop_options' => $filter_drop_options ?? collect(),
                    'filter_price_stats' => $filter_price_stats ?? [],
                ])

                @include('frontend.partials.product-grid', [
                    'products' => $products,
                    'active_filter_chips' => $active_filter_chips ?? [],
                    'filter_reset_url' => $filter_reset_url ?? route('front.products.index'),
                ])

                @include('frontend.partials.loadmore', [
                    'products' => $products,
                ])
            </div>
        </section>
    </main>

    @include('frontend.partials.footer', [
        'contact' => $contact ?? null,
        'socialLinks' => $social_links ?? [],
        'footerPages' => $footer_pages ?? [],
        'collections' => $collections ?? [],
    ])

    @include('frontend.partials.toolbar-bottom', [
        'cartCount' => $cart_count ?? 0,
    ])
    @include('frontend.partials.mobile-menu', [
        'navCategories' => $nav_categories ?? [],
        'quickLinks' => $quick_links ?? [],
    ])
    @include('frontend.partials.search-canvas', [
        'quickLinks' => $quick_links ?? [],
    ])
    @include('frontend.partials.shopping-cart', [
        'cartState' => $cart_state ?? [],
    ])
    @include('frontend.partials.auth-modals')
    @include('frontend.partials.quick-add')
    @include('frontend.partials.quick-view')
    @include('frontend.partials.find-size')
@endsection

@push('scripts')
    @include('frontend.partials.product-scripts')
@endpush
