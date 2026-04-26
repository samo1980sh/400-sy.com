@php
    $navCategories = collect($navCategories ?? []);
    $quickLinks = collect($quickLinks ?? []);
    $locale = app()->getLocale();
@endphp

<div class="offcanvas offcanvas-start canvas-mb" id="mobileMenu">
    <span class="icon-close icon-close-popup" data-bs-dismiss="offcanvas" aria-label="Close"></span>
    <div class="mb-canvas-content">
        <div class="mb-body">
            <ul class="nav-ul-mb" id="wrapper-menu-navigation">
                <li class="nav-mb-item">
                    <a href="{{ route('front.home') }}" class="mb-menu-link">{{ __('front.nav.home') }}</a>
                </li>

                @if ($navCategories->isNotEmpty())
                    @foreach ($navCategories as $category)
                        @php
                            $label = $locale === 'ar'
                                ? ($category->title_ar ?: $category->title_en ?: '')
                                : ($category->title_en ?: $category->title_ar ?: '');
                        @endphp
                        <li class="nav-mb-item">
                            <a href="#mobile-category-{{ $category->id }}" class="collapsed mb-menu-link current" data-bs-toggle="collapse" aria-expanded="false" aria-controls="mobile-category-{{ $category->id }}">
                                <span>{{ $label }}</span>
                                <span class="btn-open-sub"></span>
                            </a>
                            <div id="mobile-category-{{ $category->id }}" class="collapse">
                                <ul class="sub-nav-menu" id="sub-menu-navigation">
                                    @foreach ($category->children as $child)
                                        @php
                                            $childLabel = $locale === 'ar'
                                                ? ($child->title_ar ?: $child->title_en ?: '')
                                                : ($child->title_en ?: $child->title_ar ?: '');
                                        @endphp
                                        <li>
                                            <a href="{{ route('front.category', $child->slug) }}" class="menu-link-text link">{{ $childLabel }}</a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </li>
                    @endforeach
                @endif

                <li class="nav-mb-item"><a href="{{ route('front.home') }}#featured-products" class="mb-menu-link">{{ __('front.nav.offers') }}</a></li>
                <li class="nav-mb-item"><a href="{{ route('front.home') }}#store-locations" class="mb-menu-link">{{ __('front.nav.branches') }}</a></li>
                <li class="nav-mb-item"><a href="{{ route('front.locale', $locale === 'ar' ? 'en' : 'ar') }}" class="mb-menu-link">{{ __('front.footer.language_link') }}</a></li>
            </ul>
            <div class="mb-bottom">
                <div class="mb-other-content">
                    <div class="tf-search-content-title fw-5">{{ __('front.search.quick_link') }}</div>
                    <ul class="tf-quicklink-list">
                        @foreach ($quickLinks as $link)
                            <li class="tf-quicklink-item"><a href="{{ $link['href'] ?? '#' }}" class="">{{ $link['label'] ?? '' }}</a></li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
