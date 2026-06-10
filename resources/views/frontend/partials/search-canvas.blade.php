@php
    $quickLinks = collect($quickLinks ?? []);
@endphp

<div class="offcanvas offcanvas-end canvas-search" id="canvasSearch">
    <div class="canvas-wrapper">
        <header class="tf-search-head">
            <div class="title fw-5">
                {{ __('front.search.title') }}
                <div class="close">
                    <span class="icon-close icon-close-popup" data-bs-dismiss="offcanvas" aria-label="Close"></span>
                </div>
            </div>
            <div class="tf-search-sticky">
                <form class="tf-mini-search-frm" method="GET" action="{{ route('front.products.index') }}">
                    <fieldset class="text">
                        <input type="text" placeholder="{{ __('front.search.input') }}" class="" name="q" tabindex="0" value="{{ request('q', request('text', request('search', ''))) }}" aria-required="true" required autocomplete="off" dir="auto">
                    </fieldset>
                    <button class="" type="submit" aria-label="{{ __('front.search.title') }}"><i class="icon-search"></i></button>
                </form>
            </div>
        </header>
        <div class="canvas-body p-0">
            <div class="tf-search-content">
                <div class="tf-cart-hide-has-results">
                    <div class="tf-col-quicklink">
                        <div class="tf-search-content-title fw-5">{{ __('front.search.quick_link') }}</div>
                        <ul class="tf-quicklink-list">
                            @foreach ($quickLinks as $link)
                                <li class="tf-quicklink-item">
                                    <a href="{{ $link['href'] ?? '#' }}" class="">{{ $link['label'] ?? '' }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
