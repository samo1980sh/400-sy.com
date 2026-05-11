<!DOCTYPE html>
@php
    $pageTitle = __('front.brand');
    $locale = app()->getLocale();
@endphp
<html lang="{{ $locale }}" dir="{{ $locale === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ __('front.brand') }}">
    <link rel="shortcut icon" href="{{ asset('images/logo/favicon.png') }}">
    <link rel="apple-touch-icon-precomposed" href="{{ asset('images/logo/favicon.png') }}">
    <link rel="stylesheet" href="{{ asset('fonts/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('fonts/font-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/swiper-bundle.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/animate.css') }}">
    <link rel="stylesheet" href="{{ asset('css/bootstrap-select.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
</head>
<body class="preload-wrapper {{ $locale === 'ar' ? 'rtl' : '' }}">
    <div class="preload preload-container">
        <div class="preload-logo">
            <img src="{{ asset('images/logo/loader.png') }}" alt="{{ __('front.ui.loading') }}" class="preload-logo-img">
        </div>
    </div>

    <div id="wrapper">
        @include('frontend.partials.announcement-bar', [
            'tickerItems' => $ticker_items,
            'socialLinks' => $social_links,
        ])

        @include('frontend.partials.header', [
            'navCategories' => $nav_categories,
            'currencyOptions' => $currency_options,
            'siteName' => $site_name,
            'cartCount' => $cart_count ?? 0,
        ])

        <main>
            @include('frontend.partials.slider', [
                'slides' => $hero_slides,
            ])

            @include('frontend.partials.collections', [
                'collections' => $collections,
            ])

            @include('frontend.partials.product-section', [
                'sectionId' => 'trending-now',
                'title' => __('front.home.trending_now'),
                'products' => $trending_products,
            ])

            <section class="tf-slideshow about-us-page parallax-banner position-relative">
                <div class="banner-wrapper" aria-label="Parallax banner"></div>
            </section>

            @include('frontend.partials.product-section', [
                'sectionId' => 'new-arrivals',
                'title' => __('front.home.new_arrivals'),
                'products' => $new_products,
            ])

        </main>

        @include('frontend.partials.footer', [
            'contact' => $contact,
            'socialLinks' => $social_links,
            'footerPages' => $footer_pages,
            'collections' => $collections,
        ])

        @include('frontend.partials.toolbar-bottom', [
            'cartCount' => $cart_count ?? 0,
        ])
        @include('frontend.partials.mobile-menu', [
            'navCategories' => $nav_categories,
            'quickLinks' => $quick_links,
        ])
        @include('frontend.partials.search-canvas', [
            'quickLinks' => $quick_links,
        ])
        @include('frontend.partials.shopping-cart', [
            'cartState' => $cart_state ?? [],
        ])
        @include('frontend.partials.auth-modals')
        @include('frontend.partials.quick-add')
        @include('frontend.partials.quick-view')
        @include('frontend.partials.find-size')
    </div>

    <div class="progress-wrap">
        <svg class="progress-circle svg-content" width="100%" height="100%" viewBox="-1 -1 102 102">
            <path d="M50,1 a49,49 0 0,1 0,98 a49,49 0 0,1 0,-98"></path>
        </svg>
    </div>

    <script src="{{ asset('js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('js/jquery.min.js') }}"></script>
    <script src="{{ asset('js/swiper-bundle.min.js') }}"></script>
    <script src="{{ asset('js/carousel.js') }}"></script>
    <script src="{{ asset('js/bootstrap-select.min.js') }}"></script>
    <script src="{{ asset('js/lazysize.min.js') }}"></script>
    <script src="{{ asset('js/count-down.js') }}"></script>
    <script src="{{ asset('js/wow.min.js') }}"></script>
    <script src="{{ asset('js/multiple-modal.js') }}"></script>
    <script>
        try {
            localStorage.setItem('dir', @json($locale === 'ar' ? 'rtl' : 'ltr'));
        } catch (e) {}
    </script>
    <script src="{{ asset('js/main.js') }}?v={{ filemtime(public_path('js/main.js')) }}"></script>
    @include('frontend.partials.product-scripts')
</body>
</html>
