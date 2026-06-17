@php
    $product = $product ?? [];
    $imageUrl = $product['image'] ?? '';
    $hoverImageUrl = trim((string) ($product['hover_image'] ?? ''));

    if ($hoverImageUrl === '') {
        foreach (($product['colors'] ?? []) as $cardHoverColor) {
            $candidateHoverImage = trim((string) ($cardHoverColor['hover_image'] ?? ''));

            if ($candidateHoverImage !== '') {
                $hoverImageUrl = $candidateHoverImage;
                break;
            }
        }
    }

    if ($hoverImageUrl === '' && ! empty($product['gallery'][1])) {
        $hoverImageUrl = (string) $product['gallery'][1];
    }

    $listUrl = $product['list_url'] ?? ($product['url'] ?? '#');
    $detailUrl = $product['detail_url'] ?? ($product['url'] ?? '#');
    $loadmoreHidden = $loadmore_hidden ?? true;
    $cardDefaultColorCode = '';
    $wishlistActive = ! empty($product['is_in_wishlist']);
    $wishlistAddUrl = $product['wishlist_add_url'] ?? '';
    $wishlistRemoveUrl = $product['wishlist_remove_url'] ?? '';
    $productSlug = $product['slug'] ?? '';
    $wishlistAvailable = filled($productSlug)
        && filled($wishlistAddUrl)
        && filled($wishlistRemoveUrl);
    $wishlistLabel = $wishlistActive
        ? __('front.wishlist.remove')
        : __('front.products.add_to_wishlist');

    foreach (($product['colors'] ?? []) as $cardColor) {
        $candidateColorCode = trim((string) ($cardColor['color_code'] ?? ''));

        if ($candidateColorCode !== '') {
            $cardDefaultColorCode = $candidateColorCode;
            break;
        }
    }
@endphp

<article class="card-product {{ $loadmoreHidden ? 'fl-item' : '' }} card-product-skeleton" data-product='@json($product)'>
    <div class="card-product-wrapper hover-img">
        <div class="card-skeleton" aria-hidden="true">
            <div class="card-skeleton__media"></div>
            <div class="card-skeleton__actions">
                <span></span>
                <span></span>
                <span></span>
            </div>
            <div class="card-skeleton__sizes">
                <span></span><span></span><span></span><span></span>
            </div>
        </div>
        <a href="{{ $listUrl }}" class="collection-image img-style {{ $hoverImageUrl !== '' ? 'product-img has-card-hover-image' : '' }}">
            <img class="lazyload img-product" data-card-primary-image data-src="{{ $imageUrl }}" src="{{ $imageUrl }}" alt="{{ $product['title'] ?? '' }}">
            @if ($hoverImageUrl !== '')
                <img class="lazyload img-hover" data-card-hover-image data-src="{{ $hoverImageUrl }}" src="{{ $hoverImageUrl }}" alt="{{ $product['title'] ?? '' }}">
            @endif
        </a>
        @if (!empty($product['badge']))
            <span class="product-card-badge {{ $product['badge_class'] ?? '' }}">{{ $product['badge'] }}</span>
        @endif
        <div class="list-product-btn">
            <a href="#quick_add" class="box-icon bg_white quick-add tf-btn-loading" data-product-action="quick-add">
                <span class="icon icon-bag"></span>
                <span class="tooltip">{{ __('front.products.quick_add') }}</span>
            </a>
            @if ($wishlistAvailable)
                <a href="javascript:void(0);" class="box-icon bg_white wishlist btn-icon-action {{ $wishlistActive ? 'active' : '' }}" data-wishlist-button data-product-slug="{{ $productSlug }}" data-wishlist-add-url="{{ $wishlistAddUrl }}" data-wishlist-remove-url="{{ $wishlistRemoveUrl }}" data-wishlist-add-label="{{ __('front.products.add_to_wishlist') }}" data-wishlist-remove-label="{{ __('front.wishlist.remove') }}" aria-pressed="{{ $wishlistActive ? 'true' : 'false' }}" aria-label="{{ $wishlistLabel }}" title="{{ $wishlistLabel }}">
                    <span class="icon icon-heart"></span>
                    <span class="tooltip" data-wishlist-label>{{ $wishlistLabel }}</span>
                    <span class="icon icon-delete"></span>
                </a>
            @endif
            <a href="#quick_view" class="box-icon bg_white quickview tf-btn-loading" data-product-action="quick-view">
                <span class="icon icon-view"></span>
                <span class="tooltip">{{ __('front.products.quick_view') }}</span>
            </a>
        </div>
        <div class="size-list">
            @foreach (($product['size_options'] ?? []) as $size)
                <span>{{ $size['size'] ?? ($size['name'] ?? '') }}</span>
            @endforeach
            @if (empty($product['size_options']))
                @foreach (($product['sizes'] ?? []) as $size)
                    <span>{{ $size }}</span>
                @endforeach
            @endif
        </div>
    </div>
    <div class="card-product-info" style="text-align:center">
        <a href="{{ $detailUrl }}" class="title link">{{ $product['title'] ?? '' }}</a>
        @if (!empty($product['product_code']))
            <div class="product-card-code">
                {{ __('front.products.product_code') }}:
                <span dir="ltr" data-card-product-code data-base-product-code="{{ $product['product_code'] }}">{{ $product['product_code'] }}@if ($cardDefaultColorCode !== '')-{{ $cardDefaultColorCode }}@endif</span>
            </div>
        @endif
        <div class="product-card-price">
            <span class="price js-currency-price" data-base-price="{{ $product['price_current'] ?? $product['base_price'] ?? 0 }}" data-base-currency="{{ $product['base_currency'] ?? 'SYP' }}">{{ $product['price_current_label'] ?? $product['price_label'] ?? '' }}</span>
            @if (!empty($product['compare_price_label']))
                <del class="compare-at-price js-currency-price" data-base-price="{{ $product['compare_price'] ?? 0 }}" data-base-currency="{{ $product['base_currency'] ?? 'SYP' }}">{{ $product['compare_price_label'] }}</del>
            @endif
        </div>
        <ul class="list-color-product">
            @foreach (($product['colors'] ?? []) as $index => $color)
                @php
                    $swatchStyle = trim((string) ($color['swatch_style'] ?? ''));
                    $colorHoverImage = trim((string) ($color['hover_image'] ?? ''));

                    if ($colorHoverImage === '' && ! empty($color['gallery'][1])) {
                        $colorHoverImage = (string) $color['gallery'][1];
                    }
                @endphp
                <li class="list-color-item color-swatch {{ $index === 0 ? 'active' : '' }}" data-color-id="{{ $color['id'] ?? '' }}" data-color-name="{{ $color['name'] ?? '' }}" data-color-code="{{ $color['color_code'] ?? '' }}" data-color-class="{{ $color['class_name'] ?? '' }}" data-color-hex="{{ $color['hex'] ?? '' }}" data-color-swatch="{{ $color['swatch_image'] ?? '' }}" data-color-image="{{ $color['image'] ?? '' }}" data-color-hover-image="{{ $colorHoverImage }}">
                    <span class="tooltip">{{ $color['name'] ?? '' }}</span>
                    <span class="swatch-value {{ $color['class_name'] ?? 'four-Black' }}" @if ($swatchStyle !== '') style="{{ $swatchStyle }}" @endif></span>
                    <img class="lazyload" data-src="{{ $color['image'] ?? '' }}" src="{{ $color['image'] ?? '' }}" alt="image-product">
                </li>
            @endforeach
        </ul>
    </div>
</article>
