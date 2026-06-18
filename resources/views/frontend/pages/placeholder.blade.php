@extends('frontend.layouts.app')

@php
    $pageTitle = $page_title ?? __('front.brand');
    $pageContent = trim((string) ($company_page_content ?? ''));
    $metaDescription = trim(strip_tags($pageContent));
@endphp

@section('title', $pageTitle)
@section('meta_description', $metaDescription !== '' ? \Illuminate\Support\Str::limit($metaDescription, 160) : $pageTitle)

@section('content')
    @include('frontend.partials.announcement-bar', [
        'tickerItems' => $ticker_items ?? [],
        'socialLinks' => $social_links ?? [],
    ])

    @include('frontend.partials.header', [
        'navCategories' => $nav_categories ?? [],
        'currencyOptions' => $currency_options ?? [],
        'cartCount' => $cart_count ?? 0,
        'wishlistCount' => $wishlist_count ?? 0,
        'wishlistUrl' => $wishlist_url ?? route('front.wishlist.index'),
        'siteName' => $site_name ?? __('front.brand'),
    ])

    @include('frontend.partials.page-title', [
        'title' => $pageTitle,
        'subtitle' => $page_subtitle ?? '',
        'breadcrumbs' => $breadcrumb_items ?? [],
        'background' => $page_title_background ?? null,
    ])

    <section class="flat-spacing-10">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-lg-10">
                    <div class="tf-page-privacy-policy">
                        @if ($pageContent !== '')
                            {!! $pageContent !!}
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    @include('frontend.partials.footer', [
        'contact' => $contact ?? null,
        'socialLinks' => $social_links ?? [],
        'footerPages' => $footer_pages ?? [],
        'collections' => $collections ?? [],
    ])

    @include('frontend.partials.toolbar-bottom', [
        'cartCount' => $cart_count ?? 0,
        'wishlistCount' => $wishlist_count ?? 0,
        'wishlistUrl' => $wishlist_url ?? route('front.wishlist.index'),
    ])

    @include('frontend.partials.mobile-menu', [
        'navCategories' => $nav_categories ?? [],
        'quickLinks' => $quick_links ?? [],
    ])

    @include('frontend.partials.search-canvas', ['quickLinks' => $quick_links ?? []])
    @include('frontend.partials.shopping-cart', ['cartState' => $cart_state ?? []])
    @include('frontend.partials.auth-modals')
    @include('frontend.partials.quick-add')
    @include('frontend.partials.quick-view')
    @include('frontend.partials.find-size')
@endsection

@push('scripts')
    @include('frontend.partials.product-scripts')
@endpush
