@php
    $slides = collect($slides ?? []);
@endphp

<section>
    <div class="tf-slideshow slider-home-2 slider-effect-fade position-relative">
        <div dir="ltr" class="swiper tf-sw-slideshow" data-preview="1" data-tablet="1" data-mobile="1" data-centered="false" data-space="0" data-loop="true" data-auto-play="true" data-delay="3800" data-speed="1400">
            <div class="swiper-wrapper">
                @foreach ($slides as $slide)
                    <div class="swiper-slide" lazy="true">
                        <div class="wrap-slider">
                            @if (($slide['type'] ?? 'image') === 'video')
                                <video src="{{ $slide['video'] }}" poster="{{ $slide['poster'] ?? '' }}" autoplay muted playsinline loop></video>
                            @else
                                <img class="lazyload" data-src="{{ $slide['image'] }}" src="{{ $slide['image'] }}" alt="{{ $slide['title'] ?? __('front.brand') }}">
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="wrap-pagination sw-absolute-2">
            <div class="container">
                <div class="sw-dots sw-pagination-slider"></div>
            </div>
        </div>
    </div>
</section>
