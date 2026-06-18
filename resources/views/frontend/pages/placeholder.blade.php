@extends('frontend.layouts.app')

@php
    $pageTitle = $page_title ?? __('front.brand');
    $pageContent = trim((string) ($company_page_content ?? ''));
    $faqItems = collect($faq_items ?? []);
    $isFaqPage = array_key_exists('faq_items', get_defined_vars());
    $metaDescription = trim(strip_tags((string) ($page_meta_description ?? $pageContent)));
@endphp

@section('title', $pageTitle)
@section('meta_description', $metaDescription !== '' ? \Illuminate\Support\Str::limit($metaDescription, 160) : $pageTitle)

@section('content')
    @include('frontend.partials.announcement-bar', [
        'tickerItems' => $ticker_items ?? [],
        'socialLinks' => $social_links ?? [],
    ])

    @include('frontend.partials.header', [
        'navCategories' => $nav_categories ?? [],
        'currencyOptions' => $currency_options ?? [],
        'cartCount' => $cart_count ?? 0,
        'wishlistCount' => $wishlist_count ?? 0,
        'wishlistUrl' => $wishlist_url ?? route('front.wishlist.index'),
        'siteName' => $site_name ?? __('front.brand'),
    ])

    @include('frontend.partials.page-title', [
        'title' => $pageTitle,
        'subtitle' => $page_subtitle ?? '',
        'breadcrumbs' => $breadcrumb_items ?? [],
        'background' => $page_title_background ?? null,
    ])

    <section class="flat-spacing-10">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-lg-10">
                    <div class="tf-page-privacy-policy">
                        @if ($isFaqPage)
                            @if ($faqItems->isNotEmpty())
                                <div class="accordion" id="faqAccordion">
                                    @foreach ($faqItems as $index => $faq)
                                        @php
                                            $faqId = 'faq-item-' . ($faq['id'] ?? $index);
                                            $headingId = $faqId . '-heading';
                                            $collapseId = $faqId . '-collapse';
                                            $isOpen = $index === 0;
                                        @endphp

                                        <div class="accordion-item">
                                            <h2 class="accordion-header" id="{{ $headingId }}">
                                                <button
                                                    class="accordion-button {{ $isOpen ? '' : 'collapsed' }}"
                                                    type="button"
                                                    data-bs-toggle="collapse"
                                                    data-bs-target="#{{ $collapseId }}"
                                                    aria-expanded="{{ $isOpen ? 'true' : 'false' }}"
                                                    aria-controls="{{ $collapseId }}"
                                                >
                                                    {{ $faq['question'] ?? '' }}
                                                </button>
                                            </h2>

                                            <div
                                                id="{{ $collapseId }}"
                                                class="accordion-collapse collapse {{ $isOpen ? 'show' : '' }}"
                                                aria-labelledby="{{ $headingId }}"
                                                data-bs-parent="#faqAccordion"
                                            >
                                                <div class="accordion-body">
                                                    {!! $faq['answer'] ?? '' !!}
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-5">
                                    <p class="mb-0 text-muted">{{ $faq_empty_message ?? '' }}</p>
                                </div>
                            @endif
                        @elseif ($pageContent !== '')
                            {!! $pageContent !!}
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    @include('frontend.partials.footer', [
        'contact' => $contact ?? null,
        'socialLinks' => $social_links ?? [],
        'footerPages' => $footer_pages ?? [],
        'collections' => $collections ?? [],
    ])

    @include('frontend.partials.toolbar-bottom', [
        'cartCount' => $cart_count ?? 0,
        'wishlistCount' => $wishlist_count ?? 0,
        'wishlistUrl' => $wishlist_url ?? route('front.wishlist.index'),
    ])

    @include('frontend.partials.mobile-menu', [
        'navCategories' => $nav_categories ?? [],
        'quickLinks' => $quick_links ?? [],
    ])

    @include('frontend.partials.search-canvas', ['quickLinks' => $quick_links ?? []])
    @include('frontend.partials.shopping-cart', ['cartState' => $cart_state ?? []])
    @include('frontend.partials.auth-modals')
    @include('frontend.partials.quick-add')
    @include('frontend.partials.quick-view')
    @include('frontend.partials.find-size')
@endsection

@push('scripts')
    @include('frontend.partials.product-scripts')
@endpush
