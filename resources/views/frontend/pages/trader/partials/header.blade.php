@php
    $locale = app()->getLocale();
    $isArabic = $locale === 'ar';
    $traderIsLoggedIn = Auth::guard('trader')->check();
    $trader = Auth::guard('trader')->user();
    $traderCartCount = (int) ($traderCartCount ?? $trader_cart_count ?? 0);
    $brandName = $siteName ?? $site_name ?? __('front.brand');
    $logoHref = $traderIsLoggedIn ? route('front.trader.dashboard') : route('front.trader.login');
    $traderNavItems = [
        [
            'label' => $isArabic ? 'لوحة التاجر' : 'Trader Dashboard',
            'route' => 'front.trader.dashboard',
            'active' => 'front.trader.dashboard',
        ],
        [
            'label' => $isArabic ? 'منتجات الجملة' : 'Wholesale Products',
            'route' => 'front.trader.products.index',
            'active' => 'front.trader.products.*',
        ],
        [
            'label' => $isArabic ? 'طلب الجملة المؤقت' : 'Wholesale Cart',
            'route' => 'front.trader.cart.index',
            'active' => 'front.trader.cart.*',
            'count' => $traderCartCount,
        ],
        [
            'label' => $isArabic ? 'طلباتي' : 'My Orders',
            'route' => 'front.trader.orders.index',
            'active' => 'front.trader.orders.*',
        ],
    ];
@endphp

@once
    <style>
        .trader-shell-header,
        .trader-shell-footer {
            font-family: "Albert Sans", sans-serif;
        }
        :root {
            --trader-ink: #111;
            --trader-muted: #667085;
            --trader-line: #e8e2d4;
            --trader-paper: #fffaf0;
            --trader-soft: #fff5d6;
            --trader-gold: #ffe293;
            --trader-shadow: 0 18px 45px rgba(17, 17, 17, .08);
            --trader-shadow-hover: 0 26px 60px rgba(17, 17, 17, .12);
        }
        .trader-shell-header {
            position: sticky;
            top: 0;
            z-index: 100;
            background: #fff;
        }
        .trader-shell-header .main-header {
            background-color: #ffe293;
            border-bottom: 1px solid rgba(17, 17, 17, .08);
        }
        .trader-shell-header .wrapper-header {
            min-height: 82px;
        }
        .trader-shell-header__mark {
            display: inline-flex;
            align-items: center;
            min-height: 38px;
            padding: 7px 14px;
            border: 1px solid rgba(17, 17, 17, .12);
            border-radius: 999px;
            background: rgba(255, 255, 255, .52);
            color: #111;
            font-size: 13px;
            font-weight: 800;
            white-space: nowrap;
        }
        .trader-shell-header__logo {
            display: inline-flex;
            justify-content: center;
            padding-inline: 24px;
            padding-top: 6px;
        }
        .trader-shell-header__logo img {
            width: 210px;
            height: 48px;
            max-width: none;
            object-fit: contain;
            display: block;
        }
        .trader-shell-header__icons {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 18px;
            margin: 0;
            padding: 0;
            list-style: none;
        }
        .trader-shell-header__icon-link {
            position: relative;
            min-width: 34px;
            min-height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #111;
            font-size: 22px;
        }
        .trader-shell-header__icon-link:hover {
            color: #111;
        }
        .trader-shell-header__count {
            position: absolute;
            top: -5px;
            inset-inline-end: -8px;
            min-width: 18px;
            height: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            padding: 1px 5px;
            background: #111;
            color: #fff;
            font-size: 11px;
            font-weight: 900;
            line-height: 1;
        }
        .trader-shell-header__logout {
            min-height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #111;
            border-radius: 3px;
            background: #111;
            color: #fff;
            padding: 7px 14px;
            font-size: 13px;
            font-weight: 800;
            line-height: 1.2;
        }
        .trader-shell-header__logout:hover {
            background: #333;
            border-color: #333;
            color: #fff;
        }
        .trader-shell-header__toggle {
            display: none;
            color: #111;
            cursor: pointer;
        }
        .trader-shell-header__mobile-check {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }
        .trader-shell-header .header-bottom {
            background: #fff;
            border-bottom: 1px solid #e8e8e8;
        }
        .trader-shell-header__nav-list {
            min-height: 56px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 30px;
            margin: 0;
            padding: 0;
            list-style: none;
        }
        .trader-shell-header__nav-link {
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            color: #111;
            font-size: 15px;
            font-weight: 600;
            line-height: 1.2;
            padding: 18px 0;
        }
        .trader-shell-header__nav-link::after {
            content: "";
            position: absolute;
            inset-inline: 0;
            bottom: 12px;
            height: 2px;
            background: #111;
            transform: scaleX(0);
            transform-origin: center;
            transition: transform .2s ease;
        }
        .trader-shell-header__nav-link:hover,
        .trader-shell-header__nav-link.is-active {
            color: #111;
        }
        .trader-shell-header__nav-link:hover::after,
        .trader-shell-header__nav-link.is-active::after {
            transform: scaleX(1);
        }
        .trader-shell-footer {
            border-top: 1px solid #e8e8e8;
            background: #fff;
            color: #666;
            padding: 22px 0;
            font-size: 13px;
        }
        .trader-shell-footer__inner {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
        }
        .trader-shell-footer strong {
            color: #111;
        }
        .tf-page-title--plain {
            background:
                linear-gradient(135deg, rgba(255, 226, 147, .46), rgba(255, 255, 255, .88) 44%, rgba(17, 17, 17, .04)),
                #fff;
            border-bottom: 1px solid var(--trader-line);
        }
        .tf-page-title .heading {
            letter-spacing: 0;
        }
        .trader-dashboard,
        .trader-products,
        .trader-product,
        .trader-cart,
        .trader-page,
        .trader-order-show,
        .trader-auth {
            background:
                linear-gradient(180deg, #fffdf8 0%, #fff 38%, #fffaf0 100%) !important;
        }
        .trader-dashboard__welcome,
        .trader-dashboard__account,
        .trader-stat,
        .trader-action,
        .trader-orders-preview,
        .trader-account-details,
        .trader-products__summary-card,
        .trader-products__filters,
        .trader-products__empty,
        .trader-product__media,
        .trader-product__info,
        .trader-product__section,
        .trader-color,
        .trader-cart__list,
        .trader-cart__summary,
        .trader-cart__empty,
        .trader-orders-filters,
        .trader-orders-card,
        .trader-empty,
        .trader-panel,
        .trader-summary-card,
        .trader-auth__shell,
        .trader-product-card {
            border-color: var(--trader-line) !important;
            box-shadow: var(--trader-shadow);
        }
        .trader-dashboard__welcome,
        .trader-products__summary-card,
        .trader-summary-card,
        .trader-stat,
        .trader-product__info,
        .trader-cart__summary,
        .trader-auth__card {
            position: relative;
            overflow: hidden;
        }
        .trader-dashboard__welcome::before,
        .trader-products__summary-card::before,
        .trader-summary-card::before,
        .trader-stat::before,
        .trader-product__info::before,
        .trader-cart__summary::before,
        .trader-auth__card::before {
            content: "";
            position: absolute;
            inset: 0 0 auto;
            height: 4px;
            background: linear-gradient(90deg, var(--trader-gold), var(--trader-ink));
        }
        .trader-dashboard__account {
            background:
                linear-gradient(135deg, #111 0%, #2c2416 58%, #6e5524 100%) !important;
        }
        .trader-btn,
        .trader-action span,
        .trader-product-card__action,
        .trader-series-order button,
        .trader-cart__submit {
            border-radius: 999px !important;
            transition: transform .18s ease, box-shadow .18s ease, background-color .18s ease, border-color .18s ease;
        }
        .trader-btn:hover,
        .trader-action:hover,
        .trader-product-card:hover,
        .trader-series-order button:hover,
        .trader-cart__submit:hover {
            transform: translateY(-2px);
            box-shadow: var(--trader-shadow-hover);
        }
        .trader-btn,
        .trader-btn--dark,
        .trader-product-card__action,
        .trader-cart__submit,
        .trader-series-order button {
            background: #111 !important;
            border-color: #111 !important;
            color: #fff !important;
        }
        .trader-btn--line,
        .trader-action--line span {
            background: #fff !important;
            border-color: var(--trader-line) !important;
            color: #111 !important;
        }
        .trader-pill,
        .trader-badge,
        .trader-product__badge,
        .trader-cart__matrix-cell,
        .trader-matrix__cell {
            letter-spacing: 0;
        }
        .trader-pill {
            background: var(--trader-soft) !important;
            color: #3d310f !important;
        }
        .trader-product-card {
            border-radius: 14px !important;
            background: #fff !important;
        }
        .trader-product-card__image {
            background:
                linear-gradient(180deg, #fffaf0, #fff) !important;
        }
        .trader-product-card__image img {
            transition: transform .24s ease;
        }
        .trader-product-card:hover .trader-product-card__image img {
            transform: scale(1.035);
        }
        .trader-product-card__price {
            border-color: var(--trader-line) !important;
        }
        .trader-products__filters,
        .trader-orders-filters {
            background: rgba(255, 255, 255, .86) !important;
            backdrop-filter: blur(8px);
        }
        .trader-orders-table th,
        .trader-items th {
            background: #fff8e6 !important;
            color: #4d4124 !important;
        }
        .trader-orders-table tbody tr,
        .trader-items tbody tr {
            transition: background-color .16s ease;
        }
        .trader-orders-table tbody tr:hover,
        .trader-items tbody tr:hover {
            background: #fffdf4;
        }
        .trader-color,
        .trader-series-order,
        .trader-history-item {
            background: #fffdf8 !important;
        }
        .trader-alert {
            border-radius: 12px !important;
            box-shadow: 0 10px 28px rgba(17, 17, 17, .05);
        }
        .trader-empty,
        .trader-cart__empty,
        .trader-products__empty {
            background:
                linear-gradient(135deg, #fff, #fff8e6) !important;
        }
        @media (max-width: 991px) {
            .trader-shell-header .wrapper-header {
                min-height: 72px;
            }
            .trader-shell-header__toggle {
                display: inline-flex;
                align-items: center;
            }
            .trader-shell-header__logo img {
                width: 160px;
                height: 38px;
            }
            .trader-shell-header__mark {
                display: none;
            }
            .trader-shell-header__logout {
                min-height: 34px;
                padding: 6px 10px;
                font-size: 12px;
            }
            .trader-shell-header__icons {
                gap: 10px;
            }
            .trader-shell-header .header-bottom {
                display: none;
            }
            .trader-shell-header__mobile-check:checked ~ .header-bottom {
                display: block;
            }
            .trader-shell-header__nav-list {
                min-height: auto;
                flex-direction: column;
                align-items: stretch;
                gap: 0;
                padding: 8px 0;
            }
            .trader-shell-header__nav-link {
                justify-content: space-between;
                padding: 13px 0;
                border-bottom: 1px solid #eee;
            }
            .trader-shell-header__nav-list li:last-child .trader-shell-header__nav-link {
                border-bottom: 0;
            }
            .trader-shell-header__nav-link::after {
                display: none;
            }
            .trader-shell-footer__inner {
                flex-direction: column;
                align-items: flex-start;
            }
        }
        @media (max-width: 575px) {
            .trader-shell-header__logo {
                padding-inline: 6px;
            }
            .trader-shell-header__logo img {
                width: 132px;
            }
            .trader-shell-header__logout {
                padding: 6px 8px;
            }
        }
    </style>
@endonce

<header class="trader-shell-header header-default header-style-2" dir="{{ $isArabic ? 'rtl' : 'ltr' }}">
    @if ($traderIsLoggedIn)
        <input id="trader-shell-nav-toggle" class="trader-shell-header__mobile-check" type="checkbox">
    @endif

    <div class="main-header line">
        <div class="container-full px_15 lg-px_40">
            <div class="row wrapper-header align-items-center">
                <div class="col-xl-4 col-md-4 col-3">
                    @if ($traderIsLoggedIn)
                        <label for="trader-shell-nav-toggle" class="trader-shell-header__toggle" aria-label="{{ $isArabic ? 'فتح قائمة التجار' : 'Open trader menu' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="16" viewBox="0 0 24 16" fill="none">
                                <path d="M2.00056 2.28571H16.8577C17.1608 2.28571 17.4515 2.16531 17.6658 1.95098C17.8802 1.73665 18.0006 1.44596 18.0006 1.14286C18.0006 0.839753 17.8802 0.549063 17.6658 0.334735C17.4515 0.120408 17.1608 0 16.8577 0H2.00056C1.69745 0 1.40676 0.120408 1.19244 0.334735C0.978109 0.549063 0.857702 0.839753 0.857702 1.14286C0.857702 1.44596 0.978109 1.73665 1.19244 1.95098C1.40676 2.16531 1.69745 2.28571 2.00056 2.28571ZM0.857702 8C0.857702 7.6969 0.978109 7.40621 1.19244 7.19188C1.40676 6.97755 1.69745 6.85714 2.00056 6.85714H22.572C22.8751 6.85714 23.1658 6.97755 23.3801 7.19188C23.5944 7.40621 23.7148 7.6969 23.7148 8C23.7148 8.30311 23.5944 8.59379 23.3801 8.80812C23.1658 9.02245 22.8751 9.14286 22.572 9.14286H2.00056C1.69745 9.14286 1.40676 9.02245 1.19244 8.80812C0.978109 8.59379 0.857702 8.30311 0.857702 8ZM0.857702 14.8571C0.857702 14.554 0.978109 14.2633 1.19244 14.049C1.40676 13.8347 1.69745 13.7143 2.00056 13.7143H12.2863C12.5894 13.7143 12.8801 13.8347 13.0944 14.049C13.3087 14.2633 13.4291 14.554 13.4291 14.8571C13.4291 15.1602 13.3087 15.4509 13.0944 15.6653C12.8801 15.8796 12.5894 16 12.2863 16H2.00056C1.69745 16 1.40676 15.8796 1.19244 15.6653C0.978109 15.4509 0.857702 15.1602 0.857702 14.8571Z" fill="currentColor"></path>
                            </svg>
                        </label>
                    @endif

                    <span class="trader-shell-header__mark">{{ $isArabic ? 'بوابة تجار الجملة' : 'Wholesale Trader Portal' }}</span>
                </div>

                <div class="col-xl-4 col-md-4 col-6 text-center">
                    <a href="{{ $logoHref }}" class="trader-shell-header__logo" aria-label="{{ $brandName }}">
                        <img src="{{ asset('images/logo/logo.png') }}" alt="{{ $brandName }}">
                    </a>
                </div>

                <div class="col-xl-4 col-md-4 col-3">
                    <ul class="trader-shell-header__icons">
                        @if ($traderIsLoggedIn)
                            <li>
                                <a href="{{ route('front.trader.cart.index') }}" class="trader-shell-header__icon-link" aria-label="{{ $isArabic ? 'طلب الجملة المؤقت' : 'Wholesale Cart' }}">
                                    <i class="icon icon-bag"></i>
                                    @if ($traderCartCount > 0)
                                        <span class="trader-shell-header__count" dir="ltr">{{ $traderCartCount }}</span>
                                    @endif
                                </a>
                            </li>
                            <li class="tf-md-hidden">
                                <a href="{{ route('front.trader.dashboard') }}" class="trader-shell-header__icon-link" aria-label="{{ $isArabic ? 'حساب التاجر' : 'Trader Account' }}" title="{{ $trader?->name }}">
                                    <i class="icon icon-account"></i>
                                </a>
                            </li>
                            <li>
                                <form method="POST" action="{{ route('front.trader.logout') }}" class="m-0">
                                    @csrf
                                    <button type="submit" class="trader-shell-header__logout">{{ $isArabic ? 'خروج' : 'Logout' }}</button>
                                </form>
                            </li>
                        @else
                            <li>
                                <span class="trader-shell-header__mark">{{ $isArabic ? 'دخول التجار' : 'Trader Login' }}</span>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </div>

    @if ($traderIsLoggedIn)
        <div class="header-bottom line">
            <div class="container-full px_15 lg-px_40">
                <nav aria-label="{{ $isArabic ? 'تنقل قسم التجار' : 'Trader navigation' }}">
                    <ul class="trader-shell-header__nav-list">
                        @foreach ($traderNavItems as $item)
                            <li>
                                <a href="{{ route($item['route']) }}" class="trader-shell-header__nav-link {{ request()->routeIs($item['active']) ? 'is-active' : '' }}">
                                    <span>{{ $item['label'] }}</span>
                                    @if (array_key_exists('count', $item) && $item['count'] > 0)
                                        <span dir="ltr">({{ $item['count'] }})</span>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
            </div>
        </div>
    @endif
</header>
