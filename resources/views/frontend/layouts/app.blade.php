<!DOCTYPE html>
@php
    $locale = app()->getLocale();
    $direction = $locale === 'ar' ? 'rtl' : 'ltr';
@endphp
<html lang="{{ $locale }}" dir="{{ $direction }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', '400 Four HUNDRED'))</title>
    <meta name="description" content="@yield('meta_description', __('front.brand'))">
    <link rel="shortcut icon" href="{{ asset('images/logo/favicon.png') }}">
    <link rel="apple-touch-icon-precomposed" href="{{ asset('images/logo/favicon.png') }}">
    <link rel="stylesheet" href="{{ asset('fonts/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('fonts/font-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/photoswipe.css') }}">
    <link rel="stylesheet" href="{{ asset('css/swiper-bundle.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/animate.css') }}">
    <link rel="stylesheet" href="{{ asset('css/bootstrap-select.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
    <style>
        [x-cloak] { display: none !important; }
        body.front-shell { background: #fff; }
    </style>
    @stack('styles')
</head>
<body class="front-shell preload-wrapper {{ $direction === 'rtl' ? 'rtl' : '' }}">
    <div class="preload preload-container">
        <div class="preload-logo">
            <img src="{{ asset('images/logo/loader.png') }}" alt="Loading" class="preload-logo-img">
        </div>
    </div>

    <div id="wrapper">
        @yield('content')
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
            localStorage.setItem('dir', @json($direction));
        } catch (error) {
            // Ignore storage issues in restricted browsers.
        }
    </script>
    <script src="{{ asset('js/main.js') }}?v={{ filemtime(public_path('js/main.js')) }}"></script>
    <script src="{{ asset('js/photoswipe-lightbox.umd.min.js') }}"></script>
    <script src="{{ asset('js/model-viewer.min.js') }}"></script>
    <script src="{{ asset('js/zoom.js') }}"></script>

    @stack('scripts')
</body>
</html>
