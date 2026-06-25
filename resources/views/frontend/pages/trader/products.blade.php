@extends('frontend.layouts.app')

@php
    $locale = app()->getLocale();
    $isArabic = $locale === 'ar';
    $groupName = $wholesale_group
        ? ($isArabic
            ? ($wholesale_group->name_ar ?? $wholesale_group->name_en ?? $wholesale_group->name ?? '#'.$wholesale_group->id)
            : ($wholesale_group->name_en ?? $wholesale_group->name_ar ?? $wholesale_group->name ?? '#'.$wholesale_group->id))
        : '-';
    $productsCount = method_exists($products, 'total') ? $products->total() : $products->count();
@endphp

@section('title', $page_title ?? ($isArabic ? 'منتجات الجملة' : 'Wholesale Products'))
@section('meta_description', $page_subtitle ?? __('front.brand'))

@push('styles')
    <style>
        .trader-products { padding: 56px 0 72px; background: #fff; font-family: "Albert Sans", sans-serif; }
        .trader-products__toolbar { display: flex; align-items: flex-end; justify-content: space-between; gap: 18px; margin-bottom: 24px; }
        .trader-products__toolbar h2 { margin: 0; color: #111; font-size: clamp(24px, 3vw, 36px); line-height: 1.3; font-weight: 600; }
        .trader-products__toolbar p { margin: 8px 0 0; color: #666; line-height: 1.8; }
        .trader-btn { min-height: 42px; display: inline-flex; align-items: center; justify-content: center; padding: 8px 18px; border-radius: 3px; border: 1px solid #d8d8d8; background: #fff; color: #111; font-weight: 800; }
        .trader-btn:hover { color: #111; border-color: #111; background: #f7f7f7; }
        .trader-btn--dark { background: #111; border-color: #111; color: #fff; }
        .trader-btn--dark:hover { background: #333; border-color: #333; color: #fff; }
        .trader-products__summary { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; margin-bottom: 28px; }
        .trader-products__summary-card { min-height: 92px; border: 1px solid #e7e7e7; border-radius: 8px; background: #fff; padding: 18px; }
        .trader-products__summary-card span { display: block; color: #777; font-size: 13px; margin-bottom: 8px; }
        .trader-products__summary-card strong { display: block; color: #111; font-size: 22px; line-height: 1.35; }
        .trader-products__filters { display: grid; grid-template-columns: minmax(180px, 1fr) minmax(180px, 260px) auto auto; gap: 10px; align-items: end; border: 1px solid #e7e7e7; border-radius: 8px; padding: 14px; margin-bottom: 22px; }
        .trader-filter-field label { display: block; color: #666; font-size: 12px; font-weight: 800; margin-bottom: 6px; }
        .trader-filter-field input, .trader-filter-field select { width: 100%; min-height: 42px; border: 1px solid #ddd; border-radius: 3px; padding: 8px 10px; color: #111; background: #fff; }
        .trader-products__grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 22px; }
        .trader-products__empty { border: 1px solid #e7e7e7; border-radius: 8px; background: #fff; padding: 28px; color: #333; line-height: 1.9; }
        @media (max-width: 1199px) { .trader-products__grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
        @media (max-width: 991px) {
            .trader-products__toolbar { align-items: stretch; flex-direction: column; }
            .trader-products__toolbar .trader-btn { width: fit-content; }
            .trader-products__filters { grid-template-columns: 1fr 1fr; }
            .trader-products__grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 575px) {
            .trader-products { padding: 42px 0 58px; }
            .trader-products__summary, .trader-products__filters, .trader-products__grid { grid-template-columns: 1fr; }
        }
    </style>
@endpush

@section('content')
    @include('frontend.pages.trader.partials.header', ['traderCartCount' => $trader_cart_count ?? 0])

    <main>
        @include('frontend.partials.page-title', [
            'title' => $page_title ?? ($isArabic ? 'منتجات الجملة' : 'Wholesale Products'),
            'subtitle' => $page_subtitle ?? '',
            'breadcrumbs' => $breadcrumb_items ?? [],
            'background' => $page_title_background ?? null,
        ])

        <section class="trader-products">
            <div class="container">
                <div class="trader-products__toolbar">
                    <div>
                        <h2>{{ $isArabic ? 'المنتجات المتاحة لحسابك' : 'Products Available for Your Account' }}</h2>
                        <p>{{ $isArabic ? 'هذه الصفحة تعرض فقط منتجات الجملة المرتبطة بمجموعة حسابك التجاري.' : 'This page only shows wholesale products assigned to your trader group.' }}</p>
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('front.trader.cart.index') }}" class="trader-btn">
                            {{ $isArabic ? 'طلب الجملة المؤقت' : 'Wholesale Cart' }}
                            @if (($trader_cart_count ?? 0) > 0)
                                <span class="ms-2" dir="ltr">({{ $trader_cart_count }})</span>
                            @endif
                        </a>
                        <a href="{{ route('front.trader.dashboard') }}" class="trader-btn trader-btn--dark">{{ $isArabic ? 'لوحة التاجر' : 'Trader Dashboard' }}</a>
                    </div>
                </div>

                <div class="trader-products__summary">
                    <div class="trader-products__summary-card">
                        <span>{{ $isArabic ? 'مجموعة الجملة' : 'Wholesale Group' }}</span>
                        <strong>{{ $groupName }}</strong>
                    </div>
                    <div class="trader-products__summary-card">
                        <span>{{ $isArabic ? 'عدد المنتجات المتاحة' : 'Available Products' }}</span>
                        <strong dir="ltr">{{ number_format($productsCount) }}</strong>
                    </div>
                </div>

                <form method="GET" action="{{ route('front.trader.products.index') }}" class="trader-products__filters">
                    <div class="trader-filter-field">
                        <label for="trader_product_q">{{ $isArabic ? 'بحث بالموديل أو الاسم' : 'Search model or name' }}</label>
                        <input id="trader_product_q" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ $isArabic ? 'مثال: 400' : 'Example: 400' }}">
                    </div>
                    <div class="trader-filter-field">
                        <label for="trader_category_id">{{ $isArabic ? 'التصنيف' : 'Category' }}</label>
                        <select id="trader_category_id" name="category_id">
                            <option value="">{{ $isArabic ? 'كل التصنيفات' : 'All categories' }}</option>
                            @foreach (($category_options ?? collect()) as $category)
                                @php($categoryLabel = $isArabic ? ($category->title_ar ?: $category->title_en) : ($category->title_en ?: $category->title_ar))
                                <option value="{{ $category->id }}" @selected((string) ($filters['category_id'] ?? '') === (string) $category->id)>{{ $categoryLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="trader-btn trader-btn--dark">{{ $isArabic ? 'تطبيق' : 'Apply' }}</button>
                    <a href="{{ route('front.trader.products.index') }}" class="trader-btn">{{ $isArabic ? 'مسح' : 'Clear' }}</a>
                </form>

                @if (! $wholesale_group)
                    <div class="trader-products__empty">
                        {{ $isArabic ? 'لم يتم ربط حسابك بمجموعة جملة بعد. يرجى التواصل مع الإدارة.' : 'Your account is not linked to a wholesale group yet. Please contact administration.' }}
                    </div>
                @elseif ($products->count() === 0)
                    <div class="trader-products__empty">
                        {{ $isArabic ? 'لا توجد منتجات جملة متاحة لمجموعتك حالياً.' : 'No wholesale products are currently available for your group.' }}
                    </div>
                @else
                    <div class="trader-products__grid">
                        @foreach ($products as $product)
                            @include('frontend.pages.trader.partials.wholesale-product-card', ['product' => $product])
                        @endforeach
                    </div>

                    <div class="mt-4">
                        {{ $products->links() }}
                    </div>
                @endif
            </div>
        </section>
    </main>
    @include('frontend.pages.trader.partials.footer')
@endsection
