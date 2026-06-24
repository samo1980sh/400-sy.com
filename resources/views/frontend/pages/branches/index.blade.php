@extends('frontend.layouts.app')

@section('title', $page_title ?? (app()->getLocale() === 'ar' ? 'الفروع والصالات' : 'Branches & Showrooms'))
@section('meta_description', $page_subtitle ?? __('front.brand'))

@push('styles')
    <style>
        .branches-page-wrap { padding: 64px 0; }
        .branches-category-filter { display: flex; gap: 10px; flex-wrap: wrap; justify-content: center; margin-bottom: 36px; }
        .branches-category-filter a { border: 1px solid rgba(0,0,0,.12); border-radius: 999px; padding: 10px 18px; font-weight: 600; background: #fff; transition: .2s ease; }
        .branches-category-filter a.active, .branches-category-filter a:hover { background: #111; color: #fff; border-color: #111; }
        .branches-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 28px; }
        .branch-card { background: #fff; border: 1px solid rgba(0,0,0,.08); border-radius: 18px; overflow: hidden; height: 100%; box-shadow: 0 14px 36px rgba(0,0,0,.06); transition: transform .2s ease, box-shadow .2s ease; }
        .branch-card:hover { transform: translateY(-4px); box-shadow: 0 18px 46px rgba(0,0,0,.1); }
        .branch-card__image { position: relative; display: block; aspect-ratio: 4 / 3; overflow: hidden; background: #f5f5f5; }
        .branch-card__image img { width: 100%; height: 100%; object-fit: cover; transition: transform .35s ease; }
        .branch-card:hover .branch-card__image img { transform: scale(1.04); }
        .branch-card__badge { position: absolute; top: 14px; inset-inline-start: 14px; background: rgba(0,0,0,.78); color: #fff; border-radius: 999px; padding: 6px 12px; font-size: 12px; }
        .branch-card__body { padding: 20px; }
        .branch-card__meta { color: #777; font-size: 13px; margin-bottom: 8px; }
        .branch-card__title { font-size: 20px; margin: 0 0 10px; line-height: 1.35; }
        .branch-card__address { color: #555; line-height: 1.75; min-height: 48px; margin-bottom: 16px; }
        .branch-card__link { display: inline-flex; align-items: center; justify-content: center; border: 1px solid #111; border-radius: 999px; padding: 9px 18px; font-weight: 700; }
        .branch-card__link:hover { background: #111; color: #fff; }
        .branches-empty { text-align: center; padding: 48px; background: #f7f7f7; border-radius: 16px; }
        @media (max-width: 991px) { .branches-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 575px) { .branches-page-wrap { padding: 42px 0; } .branches-grid { grid-template-columns: 1fr; gap: 20px; } }
    </style>
@endpush

@section('content')
    @include('frontend.partials.announcement-bar', [
        'tickerItems' => $ticker_items ?? [],
        'socialLinks' => $social_links ?? [],
    ])

    @include('frontend.partials.header', [
        'navCategories' => $nav_categories ?? [],
        'currencyOptions' => $currency_options ?? [],
        'siteName' => $site_name ?? __('front.brand'),
        'cartCount' => $cart_count ?? 0,
    ])

    <main>
        @include('frontend.partials.page-title', [
            'title' => $page_title ?? (app()->getLocale() === 'ar' ? 'الفروع والصالات' : 'Branches & Showrooms'),
            'subtitle' => $page_subtitle ?? '',
            'breadcrumbs' => $breadcrumb_items ?? [],
            'background' => $page_title_background ?? null,
        ])

        <section class="branches-page-wrap">
            <div class="container">
                @php
                    $locale = app()->getLocale();
                    $selectedSlug = $selected_branch_category_slug ?? null;
                @endphp

                <div class="branches-category-filter" aria-label="{{ $locale === 'ar' ? 'المحافظات' : 'Governorates' }}">
                    <a href="{{ route('front.branches.index') }}" class="{{ blank($selectedSlug) ? 'active' : '' }}">
                        {{ $locale === 'ar' ? 'كل المحافظات' : 'All governorates' }}
                    </a>
                    @foreach (($branch_categories ?? collect()) as $category)
                        @php
                            $categoryName = $locale === 'ar'
                                ? ($category->name_ar ?: $category->name_en)
                                : ($category->name_en ?: $category->name_ar);
                        @endphp
                        <a href="{{ route('front.branches.index', ['category' => $category->slug]) }}" class="{{ $selectedSlug === $category->slug ? 'active' : '' }}">
                            {{ $categoryName }}
                        </a>
                    @endforeach
                </div>

                @if (($branches ?? collect())->isEmpty())
                    <div class="branches-empty">
                        {{ $locale === 'ar' ? 'لا توجد فروع أو صالات متاحة حالياً.' : 'No branches or showrooms are available right now.' }}
                    </div>
                @else
                    <div class="branches-grid">
                        @foreach ($branches as $branch)
                            @php
                                $name = $locale === 'ar' ? ($branch->name_ar ?: $branch->name_en) : ($branch->name_en ?: $branch->name_ar);
                                $categoryName = $branch->category
                                    ? ($locale === 'ar' ? ($branch->category->name_ar ?: $branch->category->name_en) : ($branch->category->name_en ?: $branch->category->name_ar))
                                    : '';
                                $address = $locale === 'ar' ? ($branch->address_ar ?: $branch->address_en) : ($branch->address_en ?: $branch->address_ar);
                                $image = filled($branch->main_image) ? Storage::disk('public')->url($branch->main_image) : asset('images/shop/store/ourstore1.png');
                                $typeLabel = $branch->type === 'hall'
                                    ? ($locale === 'ar' ? 'صالة' : 'Showroom')
                                    : ($locale === 'ar' ? 'فرع' : 'Branch');
                            @endphp
                            <article class="branch-card">
                                <a href="{{ route('front.branches.show', $branch->slug) }}" class="branch-card__image">
                                    <img class="lazyload" data-src="{{ $image }}" src="{{ $image }}" alt="{{ $name }}">
                                    <span class="branch-card__badge">{{ $typeLabel }}</span>
                                </a>
                                <div class="branch-card__body">
                                    <div class="branch-card__meta">{{ $categoryName }}</div>
                                    <h2 class="branch-card__title">
                                        <a href="{{ route('front.branches.show', $branch->slug) }}">{{ $name }}</a>
                                    </h2>
                                    @if (filled($address))
                                        <div class="branch-card__address">{{ $address }}</div>
                                    @endif
                                    <a href="{{ route('front.branches.show', $branch->slug) }}" class="branch-card__link">
                                        {{ $locale === 'ar' ? 'عرض التفاصيل' : 'View details' }}
                                    </a>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
    </main>

    @include('frontend.partials.footer', [
        'contact' => $contact ?? null,
        'socialLinks' => $social_links ?? [],
        'footerPages' => $footer_pages ?? [],
        'collections' => $collections ?? [],
    ])

    @include('frontend.partials.toolbar-bottom', ['cartCount' => $cart_count ?? 0])
    @include('frontend.partials.mobile-menu', ['navCategories' => $nav_categories ?? [], 'quickLinks' => $quick_links ?? []])
    @include('frontend.partials.search-canvas', ['quickLinks' => $quick_links ?? []])
    @include('frontend.partials.shopping-cart', ['cartState' => $cart_state ?? []])
    @include('frontend.partials.auth-modals')
@endsection
