@extends('frontend.layouts.app')

@section('title', $page_title ?? __('front.wishlist.title'))
@section('meta_description', $page_subtitle ?? __('front.wishlist.subtitle'))

@section('content')
    @include('frontend.partials.header', [
        'navCategories' => $nav_categories ?? [],
        'currencyOptions' => $currency_options ?? [],
        'cartCount' => $cart_count ?? 0,
        'wishlistCount' => $wishlist_count ?? 0,
        'wishlistUrl' => $wishlist_url ?? route('front.wishlist.index'),
        'siteName' => $site_name ?? __('front.brand'),
    ])

    <main>
        <div class="tf-page-title">
            <div class="container-full">
                <div class="heading text-center">{{ $page_title ?? __('front.wishlist.title') }}</div>
                <p class="text-center text-2 text_black-2 mt_5">{{ $page_subtitle ?? __('front.wishlist.subtitle') }}</p>
                <ul class="breadcrumbs d-flex align-items-center justify-content-center gap-10">
                    @foreach (($breadcrumb_items ?? []) as $item)
                        <li>
                            @if (! empty($item['url']) && ! $loop->last)
                                <a href="{{ $item['url'] }}" class="link">{{ $item['label'] ?? '' }}</a>
                            @else
                                <span>{{ $item['label'] ?? '' }}</span>
                            @endif
                        </li>
                        @if (! $loop->last)
                            <li><i class="icon-arrow-right"></i></li>
                        @endif
                    @endforeach
                </ul>
            </div>
        </div>

        <section class="flat-spacing-2">
            <div class="container">
                @if (($wishlist_products ?? collect())->isNotEmpty())
                    <div class="grid-layout wrapper-shop" data-grid="grid-4" data-wishlist-grid>
                        @foreach ($wishlist_products as $wishlistProduct)
                            @include('frontend.partials.product-card', ['product' => $wishlistProduct, 'loadmore_hidden' => false])
                        @endforeach
                    </div>
                @endif

                <div class="tf-page-cart text-center {{ ($wishlist_products ?? collect())->isNotEmpty() ? 'd-none' : '' }}" data-wishlist-empty>
                    <h5 class="mb_12">{{ __('front.wishlist.empty_title') }}</h5>
                    <p class="text_black-2 mb_24">{{ __('front.wishlist.empty_message') }}</p>
                    <a href="{{ route('front.products.index') }}" class="tf-btn btn-fill animate-hover-btn justify-content-center">
                        {{ __('front.wishlist.continue_shopping') }}
                    </a>
                </div>
            </div>
        </section>
    </main>

    @include('frontend.partials.footer', ['contact' => $contact ?? null, 'socialLinks' => $social_links ?? [], 'footerPages' => $footer_pages ?? [], 'collections' => $collections ?? []])
    @include('frontend.partials.toolbar-bottom', ['cartCount' => $cart_count ?? 0, 'wishlistCount' => $wishlist_count ?? 0, 'wishlistUrl' => $wishlist_url ?? route('front.wishlist.index')])
    @include('frontend.partials.mobile-menu', ['navCategories' => $nav_categories ?? [], 'quickLinks' => $quick_links ?? []])
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
