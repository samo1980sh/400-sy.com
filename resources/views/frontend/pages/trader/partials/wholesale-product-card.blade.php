@php
    $locale = app()->getLocale();
    $isArabic = $locale === 'ar';
    $presentation = $product_presentations[$product->getKey()] ?? [];

    $title = (string) ($presentation['title'] ?? ($isArabic
        ? ($product->title_ar ?: $product->title_en ?: $product->model_no)
        : ($product->title_en ?: $product->title_ar ?: $product->model_no)));

    $currency = (string) ($presentation['base_currency'] ?? ($isArabic ? ($product->currency_ar ?: 'ل.س') : ($product->currency_en ?: 'SYP')));
    $unitPrice = (float) (
        $presentation['base_price']
        ?? $presentation['price_current']
        ?? $presentation['compare_price']
        ?? $product->price
        ?? $product->compare_price
        ?? 0
    );
    $priceText = (string) (
        $presentation['base_price_label']
        ?? $presentation['price_label']
        ?? $presentation['compare_price_label']
        ?? ($unitPrice > 0 ? number_format($unitPrice, 0).' '.$currency : ($isArabic ? 'السعر غير مضبوط' : 'Price not configured'))
    );

    $detailUrl = route('front.trader.products.show', $product);
    $cardImageUrl = (string) ($presentation['image'] ?? '');
    $hoverImageUrl = '';

    if (! empty($presentation['gallery'][1])) {
        $hoverImageUrl = (string) $presentation['gallery'][1];
    }

    $availableColors = ($product->wholesaleAvailabilities ?? collect())
        ->filter(fn ($availability) => (int) $availability->max_quantity > 0)
        ->values();
    $availableColorIds = $availableColors
        ->pluck('product_wholesale_color_id')
        ->filter()
        ->unique()
        ->values();
    $availableColorsCount = $availableColorIds->count();
@endphp

<article class="card-product trader-product-card card-product-skeleton">
    <div class="card-product-wrapper hover-img trader-product-card__media">
        <a href="{{ $detailUrl }}" class="collection-image img-style {{ $hoverImageUrl !== '' ? 'product-img has-card-hover-image' : 'product-img' }}">
            @if ($cardImageUrl !== '')
                <img class="lazyload img-product" data-src="{{ $cardImageUrl }}" src="{{ $cardImageUrl }}" alt="{{ $title }}">
                @if ($hoverImageUrl !== '')
                    <img class="lazyload img-hover" data-src="{{ $hoverImageUrl }}" src="{{ $hoverImageUrl }}" alt="{{ $title }}">
                @endif
            @else
                <span class="trader-product-card__placeholder">{{ $title }}</span>
            @endif
        </a>
    </div>

    <div class="card-product-info trader-product-card__info">
        <a href="{{ $detailUrl }}" class="title link trader-product-card__title">{{ $title }}</a>

        <div class="product-card-price trader-product-card__price">
            <span class="price" dir="ltr">{{ $priceText }}</span>
        </div>

        <div class="trader-product-card__meta">
            <span>{{ $isArabic ? 'ألوان متاحة' : 'Available colors' }}</span>
            <strong dir="ltr">{{ $availableColorsCount }}</strong>
        </div>
    </div>
</article>

@once
    <style>
        .trader-product-card.card-product {
            height: 100%;
            padding: 10px 10px 14px;
            border: 1px solid #eadfbe;
            border-radius: 16px;
            background: linear-gradient(180deg, #fff 0%, #fffdf7 100%);
            box-shadow: 0 14px 34px rgba(17, 17, 17, .07);
            overflow: hidden;
            transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease;
        }

        .trader-product-card.card-product:hover {
            transform: translateY(-5px);
            border-color: #d8bd67;
            box-shadow: 0 24px 58px rgba(17, 17, 17, .13);
        }

        .trader-product-card .card-product-wrapper {
            border-radius: 12px;
            background: #fffaf0;
            border: 1px solid #f0e4c5;
            overflow: hidden;
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .65);
        }

        .trader-product-card .card-product-wrapper::after {
            content: "";
            position: absolute;
            inset: auto 0 0;
            height: 34%;
            background: linear-gradient(180deg, transparent, rgba(17, 17, 17, .06));
            pointer-events: none;
        }

        .trader-product-card .collection-image {
            display: flex;
            align-items: center;
            justify-content: center;
            background:
                linear-gradient(145deg, rgba(255, 226, 147, .28), rgba(255, 255, 255, .95) 46%, rgba(17, 17, 17, .03)),
                #fff;
        }

        .trader-product-card .collection-image img {
            object-fit: contain;
            background: transparent;
            transition: transform .26s ease;
        }

        .trader-product-card:hover .collection-image img {
            transform: scale(1.035);
        }

        .trader-product-card__placeholder {
            width: 100%;
            min-height: 260px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 22px;
            color: #666;
            text-align: center;
            font-weight: 800;
            line-height: 1.6;
        }

        .trader-product-card__info.card-product-info {
            text-align: center;
            padding: 14px 6px 0;
        }

        .trader-product-card__title.title {
            min-height: 44px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            color: #111;
            line-height: 1.45;
            transition: color .18s ease;
        }

        .trader-product-card:hover .trader-product-card__title.title {
            color: #7a5b11;
        }

        .trader-product-card__price.product-card-price {
            justify-content: center;
            margin-top: 7px;
        }

        .trader-product-card__price .price {
            color: #111;
            font-size: 15px;
            font-weight: 800;
        }

        .trader-product-card__meta {
            width: fit-content;
            min-height: 30px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin: 10px auto 0;
            padding: 5px 11px;
            border-radius: 999px;
            background: #fff3c8;
            border: 1px solid #eedb9d;
            color: #4d3d12;
            font-size: 12px;
            font-weight: 800;
        }

        .trader-product-card__meta strong {
            color: #111;
        }

    </style>
@endonce
