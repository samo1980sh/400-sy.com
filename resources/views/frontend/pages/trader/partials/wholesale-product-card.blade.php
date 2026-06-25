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

    $cardImageUrl = (string) ($presentation['image'] ?? '');
@endphp

<article class="wholesale-product-card wholesale-product-card--simple">
    <a href="{{ route('front.trader.products.show', $product) }}" class="wholesale-product-card-simple__image-link">
        @if ($cardImageUrl !== '')
            <img src="{{ $cardImageUrl }}" alt="{{ $title }}" class="wholesale-product-card-simple__image">
        @else
            <div class="wholesale-product-card-simple__image-placeholder">{{ $title }}</div>
        @endif
    </a>

    <div class="wholesale-product-card-simple__body">
        <h3 class="wholesale-product-card-simple__title">{{ $title }}</h3>
        <div class="wholesale-product-card-simple__model" dir="ltr">{{ $product->model_no }}</div>

        <div class="wholesale-product-card-simple__price">
            <span>{{ $isArabic ? 'سعر القطعة' : 'Unit Price' }}</span>
            <strong dir="ltr">{{ $priceText }}</strong>
        </div>

        <a href="{{ route('front.trader.products.show', $product) }}" class="wholesale-product-card-simple__action">
            {{ $isArabic ? 'تفاصيل المنتج' : 'Product Details' }}
        </a>
    </div>
</article>

<style>
    .wholesale-product-card--simple {
        padding: 0 !important;
        overflow: hidden;
        border-radius: 24px !important;
        border: 1px solid rgba(185, 134, 25, .22) !important;
        background: #fff !important;
    }

    .wholesale-product-card--simple::before,
    .wholesale-product-card--simple .wholesale-product-card__head,
    .wholesale-product-card--simple .wholesale-product-card__quantity,
    .wholesale-product-card--simple .wholesale-product-card__colors,
    .wholesale-product-card--simple .wholesale-product-card__series,
    .wholesale-product-card--simple .wholesale-product-card__action {
        display: none !important;
    }

    .wholesale-product-card-simple__image-link {
        display: block;
        height: 220px;
        background: #f7f7f7;
        overflow: hidden;
        border-bottom: 1px solid #f0f0f0;
    }

    .wholesale-product-card-simple__image {
        width: 100%;
        height: 100%;
        object-fit: contain;
        display: block;
        background: #fff;
        transition: transform .25s ease;
    }

    .wholesale-product-card--simple:hover .wholesale-product-card-simple__image {
        transform: scale(1.025);
    }

    .wholesale-product-card-simple__image-placeholder {
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 18px;
        text-align: center;
        color: #fff;
        font-weight: 900;
        background: linear-gradient(135deg, #111 0%, #3d321d 100%);
    }

    .wholesale-product-card-simple__body {
        padding: 16px 18px 18px;
    }

    .wholesale-product-card-simple__title {
        margin: 0;
        color: #111;
        font-family: inherit;
        font-size: 19px;
        line-height: 1.45;
        font-weight: 900;
        text-align: start;
    }

    .wholesale-product-card-simple__model {
        margin-top: 5px;
        color: #6f7890;
        font-size: 13px;
        font-weight: 700;
        text-align: start;
    }

    .wholesale-product-card-simple__price {
        margin-top: 14px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 11px 13px;
        border-radius: 14px;
        background: #f7f0e2;
        color: #111;
    }

    .wholesale-product-card-simple__price span {
        color: #6f5a28;
        font-size: 12px;
        font-weight: 800;
    }

    .wholesale-product-card-simple__price strong {
        color: #111;
        font-size: 16px;
        font-weight: 900;
        white-space: nowrap;
    }

    .wholesale-product-card-simple__action {
        margin-top: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 42px;
        width: 100%;
        border-radius: 999px;
        background: #111;
        color: #fff;
        font-size: 13px;
        font-weight: 900;
        transition: transform .2s ease, box-shadow .2s ease, background .2s ease;
    }

    .wholesale-product-card-simple__action:hover {
        color: #fff;
        background: #2b2b2b;
        transform: translateY(-2px);
        box-shadow: 0 12px 24px rgba(0,0,0,.12);
    }
</style>
