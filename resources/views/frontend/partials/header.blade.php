@php
    $locale = app()->getLocale();
    $isRtl = $locale === 'ar';
    $homeUrl = route('front.home');
    $offersUrl = route('front.offers');
    $branchesUrl = route('front.home') . '#store-locations';
    $navCategories = collect($navCategories ?? []);
    $currencyOptions = collect($currencyOptions ?? []);
    $cartCount = (int) ($cartCount ?? 0);
    $wishlistCount = (int) ($wishlistCount ?? ($wishlist_count ?? 0));
    $wishlistUrl = $wishlistUrl ?? ($wishlist_url ?? route('front.wishlist.index'));
    $languageSwitchLocale = $locale === 'ar' ? 'en' : 'ar';
    $languageSwitchLabel = $locale === 'ar' ? __('front.ui.english') : __('front.ui.arabic');
    $languageSwitchUrl = route('front.locale', $languageSwitchLocale);
@endphp

<header id="header" class="header-default header-style-2">
    <div class="main-header line" style="background-color: #ffe293;">
        <div class="container-full px_15 lg-px_40">
            <div class="row wrapper-header align-items-center" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
                <div class="col-xl-4 tf-md-hidden">
                    <div class="header-list-categories locale-switchers">
                        <div class="country-select-wrap">
                            <select class="image-select country-select js-country-select" dir="{{ $isRtl ? 'rtl' : 'ltr' }}" aria-label="{{ __('front.ui.select_country') }}">
                                <option value="{{ $homeUrl }}" data-thumbnail="{{ asset('images/logo/syria.png') }}" selected>{{ __('front.countries.syria') }}</option>
                                <option value="{{ $homeUrl }}" data-thumbnail="{{ asset('images/logo/egypt.png') }}">{{ __('front.countries.egypt') }}</option>
                                <option value="{{ $homeUrl }}" data-thumbnail="{{ asset('images/logo/jordan.png') }}">{{ __('front.countries.jordan') }}</option>
                            </select>
                        </div>
                        <div class="language-select-wrap">
                        <a href="{{ $languageSwitchUrl }}"
                           class="image-select language-select language-switch-link"
                           dir="{{ $isRtl ? 'rtl' : 'ltr' }}"
                           aria-label="{{ __('front.ui.select_language') }}"
                           data-language-switch-opposite>
                            <span class="language-switch-icon" aria-hidden="true">🌐</span>
                            <span class="language-switch-label">{{ $languageSwitchLabel }}</span>
                        </a>
                    </div>
                    </div>
                </div>
                <div class="col-md-4 col-3 tf-lg-hidden">
                    <a href="#mobileMenu" data-bs-toggle="offcanvas" aria-controls="offcanvasLeft">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="16" viewBox="0 0 24 16" fill="none">
                            <path d="M2.00056 2.28571H16.8577C17.1608 2.28571 17.4515 2.16531 17.6658 1.95098C17.8802 1.73665 18.0006 1.44596 18.0006 1.14286C18.0006 0.839753 17.8802 0.549063 17.6658 0.334735C17.4515 0.120408 17.1608 0 16.8577 0H2.00056C1.69745 0 1.40676 0.120408 1.19244 0.334735C0.978109 0.549063 0.857702 0.839753 0.857702 1.14286C0.857702 1.44596 0.978109 1.73665 1.19244 1.95098C1.40676 2.16531 1.69745 2.28571 2.00056 2.28571ZM0.857702 8C0.857702 7.6969 0.978109 7.40621 1.19244 7.19188C1.40676 6.97755 1.69745 6.85714 2.00056 6.85714H22.572C22.8751 6.85714 23.1658 6.97755 23.3801 7.19188C23.5944 7.40621 23.7148 7.6969 23.7148 8C23.7148 8.30311 23.5944 8.59379 23.3801 8.80812C23.1658 9.02245 22.8751 9.14286 22.572 9.14286H2.00056C1.69745 9.14286 1.40676 9.02245 1.19244 8.80812C0.978109 8.59379 0.857702 8.30311 0.857702 8ZM0.857702 14.8571C0.857702 14.554 0.978109 14.2633 1.19244 14.049C1.40676 13.8347 1.69745 13.7143 2.00056 13.7143H12.2863C12.5894 13.7143 12.8801 13.8347 13.0944 14.049C13.3087 14.2633 13.4291 14.554 13.4291 14.8571C13.4291 15.1602 13.3087 15.4509 13.0944 15.6653C12.8801 15.8796 12.5894 16 12.2863 16H2.00056C1.69745 16 1.40676 15.8796 1.19244 15.6653C0.978109 15.4509 0.857702 15.1602 0.857702 14.8571Z" fill="currentColor"></path>
                        </svg>
                    </a>
                </div>
                <div class="col-xl-4 col-md-4 col-6 text-center">
                    <a href="{{ $homeUrl }}" class="logo-header" style="display:inline-flex;justify-content:center;padding-inline:24px;padding-top:6px;">
                        <img src="{{ asset('images/logo/logo.png') }}" alt="{{ $siteName ?? __('front.brand') }}" class="logo" style="width:210px;height:48px;max-width:none;object-fit:contain;">
                    </a>
                </div>
                <div class="col-xl-4 col-md-4 col-3">
                    <ul class="nav-icon d-flex justify-content-end align-items-center gap-20">
                        <li class="nav-currency">
                            <form method="POST" action="{{ route('front.currency') }}" class="currency-switch-form">
                                @csrf
                                <select class="image-select currency-select js-currency-select" name="currency" dir="ltr" aria-label="{{ __('front.ui.select_currency') }}">
                                    @foreach ($currencyOptions as $option)
                                        <option
                                            value="{{ $option['value'] }}"
                                            data-rate="{{ $option['rate'] ?? '' }}"
                                            data-symbol="{{ $option['symbol'] ?? '' }}"
                                            @selected($option['selected'] ?? false)
                                        >
                                            {{ $option['label'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </form>
                        </li>
                        <li class="nav-search"><a href="#canvasSearch" data-bs-toggle="offcanvas" aria-controls="offcanvasLeft" class="nav-icon-item"><i class="icon icon-search"></i></a></li>
                        <li class="nav-account"><a href="#login" data-bs-toggle="modal" class="nav-icon-item"><i class="icon icon-account"></i></a></li>
                        <li class="nav-wishlist"><a href="{{ $wishlistUrl }}" class="nav-icon-item" aria-label="{{ __('front.toolbar.wishlist') }}"><i class="icon icon-heart"></i><span class="count-box" data-wishlist-count>{{ $wishlistCount }}</span></a></li>
                        <li class="nav-cart"><a href="#shoppingCart" data-bs-toggle="modal" class="nav-icon-item"><i class="icon icon-bag"></i><span class="count-box" data-cart-count>{{ $cartCount }}</span></a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="header-bottom line">
        <div class="container-full px_15 lg-px_40">
            <div class="wrapper-header d-flex justify-content-center align-items-center">
                <nav class="box-navigation text-center">
                    <ul class="box-nav-ul d-flex align-items-center justify-content-center gap-30">
                        <li class="menu-item"><a href="{{ $homeUrl }}" class="item-link">{{ __('front.nav.home') }}</a></li>
                        @if ($navCategories->isNotEmpty())
                            @foreach ($navCategories as $category)
                                @php
                                    $label = $locale === 'ar'
                                        ? ($category->title_ar ?: $category->title_en ?: '')
                                        : ($category->title_en ?: $category->title_ar ?: '');
                                @endphp
                                <li class="menu-item {{ $category->children->isNotEmpty() ? 'has-dropdown' : '' }}">
                                    <a href="{{ route('front.category', $category->slug) }}" class="item-link">
                                        {{ $label }}
                                        @if ($category->children->isNotEmpty())
                                            <i class="icon icon-arrow-down"></i>
                                        @endif
                                    </a>
                                    @if ($category->children->isNotEmpty())
                                        <div class="sub-menu mega-menu">
                                            <div class="container">
                                                <div class="row mega-menu-row">
                                                    @foreach ($category->children as $child)
                                                        @php
                                                            $childLabel = $locale === 'ar'
                                                                ? ($child->title_ar ?: $child->title_en ?: '')
                                                                : ($child->title_en ?: $child->title_ar ?: '');
                                                        @endphp
                                                        <div class="col-lg-3 mega-menu-col">
                                                            <div class="mega-menu-item">
                                                                <div class="menu-heading">{{ $childLabel }}</div>
                                                                <ul class="menu-list">
                                                                    @if ($child->children->isNotEmpty())
                                                                        @foreach ($child->children as $grandChild)
                                                                            @php
                                                                                $grandLabel = $locale === 'ar'
                                                                                    ? ($grandChild->title_ar ?: $grandChild->title_en ?: '')
                                                                                    : ($grandChild->title_en ?: $grandChild->title_ar ?: '');
                                                                            @endphp
                                                                            <li>
                                                                                <a href="{{ route('front.category', $grandChild->slug) }}" class="menu-link-text link">{{ $grandLabel }}</a>
                                                                            </li>
                                                                        @endforeach
                                                                    @else
                                                                        <li>
                                                                            <a href="{{ route('front.category', $child->slug) }}" class="menu-link-text link">{{ $childLabel }}</a>
                                                                        </li>
                                                                    @endif
                                                                </ul>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                    <div class="col-lg-3 mega-menu-promo">
                                                        <div class="collection-item hover-img">
                                                            <div class="collection-inner">
                                                                <a href="{{ route('front.category', $category->slug) }}" class="collection-image img-style">
                                                                    <img class="lazyload" data-src="{{ filled($category->image) ? \Storage::disk('public')->url($category->image) : asset('images/collections/collection-1.jpg') }}" src="{{ filled($category->image) ? \Storage::disk('public')->url($category->image) : asset('images/collections/collection-1.jpg') }}" alt="{{ $label }}">
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </li>
                            @endforeach
                        @endif
                        <li class="menu-item"><a href="{{ $offersUrl }}" class="item-link">{{ __('front.nav.offers') }}</a></li>
                        <li class="menu-item"><a href="{{ $branchesUrl }}" class="item-link">{{ __('front.nav.branches') }}</a></li>
                    </ul>
                </nav>
                <div class="header-list-categories tf-lg-hidden country-select-wrap">
                    <select class="image-select country-select js-country-select" aria-label="{{ __('front.ui.select_country') }}">
                        <option value="{{ $homeUrl }}" data-thumbnail="{{ asset('images/logo/syria.png') }}" selected>{{ __('front.countries.syria') }}</option>
                        <option value="{{ $homeUrl }}" data-thumbnail="{{ asset('images/logo/egypt.png') }}">{{ __('front.countries.egypt') }}</option>
                        <option value="{{ $homeUrl }}" data-thumbnail="{{ asset('images/logo/jordan.png') }}">{{ __('front.countries.jordan') }}</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</header>
