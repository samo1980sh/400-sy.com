@php
    $collections = collect($collections ?? []);
@endphp

<section class="flat-spacing-22 bg_grey-3">
    <div class="container">
        <div class="flat-title flex-row justify-content-between align-items-center px-0 wow fadeInUp" data-wow-delay="0s">
            <h3 class="title">{{ __('front.sections.collections') }}</h3>
            <a href="{{ route('front.products.show', 'placeholder-product') }}" class="tf-btn btn-line">{{ __('front.sections.view_all_categories') }}<i class="icon icon-arrow1-top-left"></i></a>
        </div>
        <div class="hover-sw-nav hover-sw-2">
            <div dir="ltr" class="swiper tf-sw-collection" data-preview="4" data-tablet="3" data-mobile="2" data-space-lg="50" data-space-md="30" data-space="15" data-loop="false" data-auto-play="false">
                <div class="swiper-wrapper">
                    @foreach ($collections as $collection)
                    <div class="swiper-slide" lazy="true">
                        <div class="collection-item-circle hover-img">
                            <a href="{{ $collection['link'] ?? '#' }}" class="collection-image img-style">
                                <img class="lazyload" data-src="{{ $collection['image'] ?? '' }}" src="{{ $collection['image'] ?? '' }}" alt="collection-img">
                            </a>
                            <div class="collection-content text-center">
                                <a href="{{ $collection['link'] ?? '#' }}" class="link title fw-5">{{ $collection['title'] ?? '' }}</a>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            <div class="sw-dots style-2 sw-pagination-collection justify-content-center"></div>
            <div class="nav-sw nav-next-slider nav-next-collection"><span class="icon icon-arrow-left"></span></div>
            <div class="nav-sw nav-prev-slider nav-prev-collection"><span class="icon icon-arrow-right"></span></div>
        </div>
    </div>
</section>
