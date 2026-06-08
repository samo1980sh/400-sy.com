@php
    $product = $product ?? [];
    $imageUrl = $product['image'] ?? '';
    $listUrl = $product['list_url'] ?? ($product['url'] ?? '#');
    $detailUrl = $product['detail_url'] ?? ($product['url'] ?? '#');
    $loadmoreHidden = $loadmore_hidden ?? true;
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
        <a href="{{ $listUrl }}" class="collection-image img-style">
            <img class="lazyload img-product" data-src="{{ $imageUrl }}" src="{{ $imageUrl }}" alt="{{ $product['title'] ?? '' }}">
        </a>
        @if (!empty($product['badge']))
            <span class="product-card-badge {{ $product['badge_class'] ?? '' }}">{{ $product['badge'] }}</span>
        @endif
        <div class="list-product-btn">
            <a href="#quick_add" class="box-icon bg_white quick-add tf-btn-loading" data-product-action="quick-add">
                <span class="icon icon-bag"></span>
                <span class="tooltip">{{ __('front.products.quick_add') }}</span>
            </a>
            <a href="javascript:void(0);" class="box-icon bg_white wishlist btn-icon-action">
                <span class="icon icon-heart"></span>
                <span class="tooltip">{{ __('front.products.add_to_wishlist') }}</span>
                <span class="icon icon-delete"></span>
            </a>
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
            @php
                $cardProductBaseCode = trim((string) ($product['product_code'] ?? ''));
                $cardFirstColor = collect($product['colors'] ?? [])->first();
                $cardFirstColorCode = is_array($cardFirstColor) ? trim((string) ($cardFirstColor['color_code'] ?? '')) : '';
                $cardProductDisplayCode = $cardProductBaseCode;

                if ($cardProductBaseCode !== '' && $cardFirstColorCode !== '' && ! str_ends_with($cardProductBaseCode, '-' . $cardFirstColorCode)) {
                    $cardProductDisplayCode = $cardProductBaseCode . '-' . $cardFirstColorCode;
                }
            @endphp
            <div class="product-card-code">
                {{ __('front.products.product_code') }}:
                <span data-card-product-code data-base-product-code="{{ $cardProductBaseCode }}">{{ $cardProductDisplayCode }}</span>
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
                @endphp
                <li class="list-color-item color-swatch {{ $index === 0 ? 'active' : '' }}" data-color-id="{{ $color['id'] ?? '' }}" data-color-name="{{ $color['name'] ?? '' }}" data-color-code="{{ $color['color_code'] ?? '' }}" data-color-class="{{ $color['class_name'] ?? '' }}" data-color-hex="{{ $color['hex'] ?? '' }}" data-color-swatch="{{ $color['swatch_image'] ?? '' }}" data-color-image="{{ $color['image'] ?? '' }}">
                    <span class="tooltip">{{ $color['name'] ?? '' }}</span>
                    <span class="swatch-value {{ $color['class_name'] ?? 'four-Black' }}" @if ($swatchStyle !== '') style="{{ $swatchStyle }}" @endif></span>
                    <img class="lazyload" data-src="{{ $color['image'] ?? '' }}" src="{{ $color['image'] ?? '' }}" alt="image-product">
                </li>
            @endforeach
        </ul>
    </div>
</article>
