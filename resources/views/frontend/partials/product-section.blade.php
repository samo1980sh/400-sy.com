@php
    $products = collect($products ?? []);
    $title = $title ?? '';
@endphp

<section id="{{ $sectionId ?? 'featured-products' }}" class="flat-spacing-3 pt_3 flat-seller">
    <div class="container">
        <div class="flat-title d-flex align-items-center justify-content-between">
            <span class="title wow fadeInUp" data-wow-delay="0s">{{ $title }}</span>
            @if (! empty($viewAllHref))
                <a href="{{ $viewAllHref }}" class="tf-btn btn-line">{{ __('front.sections.view_all_categories') }}<i class="icon icon-arrow1-top-left"></i></a>
            @endif
        </div>
        <div class="grid-layout loadmore-item wow fadeInUp" data-wow-delay="0s" data-grid="grid-4">
            @foreach ($products as $product)
                @include('frontend.partials.product-card', ['product' => $product])
            @endforeach
        </div>
    </div>
</section>
