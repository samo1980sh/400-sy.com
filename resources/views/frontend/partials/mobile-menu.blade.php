@php
    $navCategories = collect($navCategories ?? []);
    $quickLinks = collect($quickLinks ?? []);
    $locale = app()->getLocale();
    $languageSwitchLocale = $locale === 'ar' ? 'en' : 'ar';
    $languageSwitchLabel = $locale === 'ar' ? __('front.ui.english') : __('front.ui.arabic');
    $languageSwitchUrl = route('front.locale', $languageSwitchLocale);
    $customer = auth('customer')->user();

    $categoryLabel = static function ($category) use ($locale): string {
        return $locale === 'ar'
            ? ($category->title_ar ?: $category->title_en ?: '')
            : ($category->title_en ?: $category->title_ar ?: '');
    };

    $renderMobileCategoryItems = null;
    $renderMobileCategoryItems = function ($items, int $level = 0) use (&$renderMobileCategoryItems, $categoryLabel): string {
        $html = '';

        foreach (collect($items) as $category) {
            $children = collect($category->children ?? []);
            $hasChildren = $children->isNotEmpty();
            $label = $categoryLabel($category);
            $collapseId = 'mobile-category-' . $category->id;

            if ($level === 0) {
                $html .= '<li class="nav-mb-item">';
            } else {
                $html .= '<li class="sub-menu-level-2">';
            }

            if ($hasChildren) {
                $linkClass = $level === 0 ? 'mb-menu-link current' : 'sub-nav-link current';
                $html .= '<a href="#' . e($collapseId) . '" class="collapsed ' . e($linkClass) . '" data-bs-toggle="collapse" aria-expanded="false" aria-controls="' . e($collapseId) . '">';
                $html .= '<span>' . e($label) . '</span>';
                $html .= '<span class="btn-open-sub"></span>';
                $html .= '</a>';
                $html .= '<div id="' . e($collapseId) . '" class="collapse">';
                $html .= '<ul class="sub-nav-menu">';
                $html .= $renderMobileCategoryItems($children, $level + 1);
                $html .= '</ul>';
                $html .= '</div>';
            } else {
                $html .= '<a href="' . e(route('front.category', $category->slug)) . '" class="' . ($level === 0 ? 'mb-menu-link' : 'menu-link-text link') . '">' . e($label) . '</a>';
            }

            $html .= '</li>';
        }

        return $html;
    };
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
                    {!! $renderMobileCategoryItems($navCategories) !!}
                @endif

                <li class="nav-mb-item"><a href="{{ route('front.offers') }}" class="mb-menu-link">{{ __('front.nav.offers') }}</a></li>
                <li class="nav-mb-item"><a href="{{ route('front.home') }}#store-locations" class="mb-menu-link">{{ __('front.nav.branches') }}</a></li>
                <li class="nav-mb-item">
                    <a href="{{ $customer ? route('front.account.index') : '#login' }}" class="mb-menu-link" @unless($customer) data-bs-toggle="modal" @endunless>
                        {{ $customer ? __('front.account.title') : __('front.auth.login_title') }}
                    </a>
                </li>
                <li class="nav-mb-item"><a href="{{ $languageSwitchUrl }}" class="mb-menu-link mobile-language-switch-link"><span class="language-switch-icon" aria-hidden="true">🌐</span><span class="language-switch-label">{{ $languageSwitchLabel }}</span></a></li>
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
