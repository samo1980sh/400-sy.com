@extends('frontend.layouts.app')

@section('title', $branch_name ?? (app()->getLocale() === 'ar' ? 'تفاصيل الفرع' : 'Branch details'))
@section('meta_description', $branch_address ?? __('front.brand'))

@push('styles')
    <style>
        .branch-detail-wrap { padding: 64px 0; }
        .branch-detail-grid { display: grid; grid-template-columns: minmax(0, 1.1fr) minmax(320px, .9fr); gap: 36px; align-items: start; }
        .branch-main-image { border-radius: 20px; overflow: hidden; background: #f6f6f6; }
        .branch-main-image img { width: 100%; display: block; aspect-ratio: 4 / 3; object-fit: cover; }
        .branch-gallery { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; margin-top: 16px; }
        .branch-gallery img { width: 100%; aspect-ratio: 1 / 1; object-fit: cover; border-radius: 14px; }
        .branch-info-card { border: 1px solid rgba(0,0,0,.08); border-radius: 20px; padding: 28px; background: #fff; box-shadow: 0 16px 42px rgba(0,0,0,.06); }
        .branch-info-badges { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 14px; }
        .branch-info-badges span { border-radius: 999px; padding: 6px 12px; background: #f2f2f2; font-size: 13px; font-weight: 700; }
        .branch-info-title { font-size: 30px; margin: 0 0 16px; }
        .branch-info-list { display: grid; gap: 12px; margin: 24px 0; }
        .branch-info-row { display: flex; gap: 12px; align-items: flex-start; color: #444; line-height: 1.7; }
        .branch-info-row strong { min-width: 90px; color: #111; }
        .branch-description { margin-top: 28px; line-height: 1.9; color: #444; }
        .branch-actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 24px; }
        .branch-actions a { border: 1px solid #111; border-radius: 999px; padding: 10px 18px; font-weight: 700; }
        .branch-actions a:hover { background: #111; color: #fff; }
        @media (max-width: 991px) { .branch-detail-grid { grid-template-columns: 1fr; } }
        @media (max-width: 575px) { .branch-detail-wrap { padding: 42px 0; } .branch-gallery { grid-template-columns: repeat(2, minmax(0, 1fr)); } .branch-info-card { padding: 22px; } }
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
            'title' => $branch_name ?? '',
            'subtitle' => $branch_category_name ?? '',
            'breadcrumbs' => $breadcrumb_items ?? [],
            'background' => $page_title_background ?? null,
        ])

        <section class="branch-detail-wrap">
            <div class="container">
                @php $locale = app()->getLocale(); @endphp
                <div class="branch-detail-grid">
                    <div>
                        <div class="branch-main-image">
                            <img class="lazyload" data-src="{{ $branch_image_url }}" src="{{ $branch_image_url }}" alt="{{ $branch_name }}">
                        </div>
                        @if (($branch_gallery_urls ?? collect())->isNotEmpty())
                            <div class="branch-gallery">
                                @foreach ($branch_gallery_urls as $galleryImage)
                                    <img class="lazyload" data-src="{{ $galleryImage }}" src="{{ $galleryImage }}" alt="{{ $branch_name }}">
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <aside class="branch-info-card">
                        <div class="branch-info-badges">
                            @if (filled($branch_category_name))<span>{{ $branch_category_name }}</span>@endif
                            @if (filled($branch_type_label))<span>{{ $branch_type_label }}</span>@endif
                        </div>
                        <h1 class="branch-info-title">{{ $branch_name }}</h1>

                        <div class="branch-info-list">
                            @if (filled($branch_address))
                                <div class="branch-info-row"><strong>{{ $locale === 'ar' ? 'العنوان' : 'Address' }}</strong><span>{{ $branch_address }}</span></div>
                            @endif
                            @if (filled($branch->phone))
                                <div class="branch-info-row"><strong>{{ $locale === 'ar' ? 'الهاتف' : 'Phone' }}</strong><span dir="ltr">{{ $branch->phone }}</span></div>
                            @endif
                            @if (filled($branch->mobile))
                                <div class="branch-info-row"><strong>{{ $locale === 'ar' ? 'الموبايل' : 'Mobile' }}</strong><span dir="ltr">{{ $branch->mobile }}</span></div>
                            @endif
                            @if (filled($branch->whatsapp))
                                <div class="branch-info-row"><strong>{{ $locale === 'ar' ? 'واتساب' : 'WhatsApp' }}</strong><span dir="ltr">{{ $branch->whatsapp }}</span></div>
                            @endif
                            @if (filled($branch->email))
                                <div class="branch-info-row"><strong>{{ $locale === 'ar' ? 'البريد' : 'Email' }}</strong><span dir="ltr">{{ $branch->email }}</span></div>
                            @endif
                        </div>

                        @if (filled($branch_description))
                            <div class="branch-description">{!! $branch_description !!}</div>
                        @endif

                        <div class="branch-actions">
                            @if (filled($branch->map_url))
                                <a href="{{ $branch->map_url }}" target="_blank" rel="noopener">{{ $locale === 'ar' ? 'عرض الموقع على الخريطة' : 'View on map' }}</a>
                            @endif
                            @if (filled($branch->whatsapp))
                                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $branch->whatsapp) }}" target="_blank" rel="noopener">{{ $locale === 'ar' ? 'تواصل واتساب' : 'WhatsApp' }}</a>
                            @endif
                        </div>
                    </aside>
                </div>
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
