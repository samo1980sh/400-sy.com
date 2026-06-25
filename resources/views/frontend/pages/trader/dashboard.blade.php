@extends('frontend.layouts.app')

@php
    $locale = app()->getLocale();
    $isArabic = $locale === 'ar';
    $groupName = $trader->wholesaleCustomerGroup
        ? ($isArabic ? ($trader->wholesaleCustomerGroup->name_ar ?? $trader->wholesaleCustomerGroup->name_en ?? '-') : ($trader->wholesaleCustomerGroup->name_en ?? $trader->wholesaleCustomerGroup->name_ar ?? '-'))
        : '-';
@endphp

@section('title', $page_title ?? ($isArabic ? 'لوحة التاجر' : 'Trader Dashboard'))
@section('meta_description', $page_subtitle ?? __('front.brand'))

@push('styles')
    <style>
        .trader-dashboard { padding: 56px 0 72px; background: #fff; font-family: "Albert Sans", sans-serif; }
        .trader-dashboard__head { display: grid; grid-template-columns: minmax(0, 1fr) 300px; gap: 24px; align-items: stretch; margin-bottom: 24px; }
        .trader-dashboard__welcome, .trader-dashboard__account, .trader-stat, .trader-action, .trader-orders-preview, .trader-account-details { border: 1px solid #e7e7e7; border-radius: 8px; background: #fff; }
        .trader-dashboard__welcome { padding: 28px; }
        .trader-dashboard__kicker { color: #777; font-size: 13px; font-weight: 800; margin-bottom: 10px; }
        .trader-dashboard__welcome h2 { margin: 0; color: #111; font-size: clamp(28px, 4vw, 44px); line-height: 1.25; font-weight: 600; }
        .trader-dashboard__welcome p { margin: 14px 0 0; color: #666; line-height: 1.9; max-width: 760px; }
        .trader-dashboard__account { padding: 24px; display: flex; flex-direction: column; justify-content: space-between; gap: 18px; background: #111; color: #fff; }
        .trader-dashboard__account span { color: rgba(255,255,255,.72); font-size: 13px; }
        .trader-dashboard__account strong { color: #fff; font-size: 24px; letter-spacing: .04em; }
        .trader-dashboard__stats { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; margin-bottom: 24px; }
        .trader-stat { padding: 20px; min-height: 112px; }
        .trader-stat span { display: block; color: #777; font-size: 13px; margin-bottom: 8px; }
        .trader-stat strong { display: block; color: #111; font-size: 24px; font-weight: 700; overflow-wrap: anywhere; }
        .trader-dashboard__actions { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; margin-bottom: 24px; }
        .trader-action { display: flex; flex-direction: column; justify-content: space-between; min-height: 154px; padding: 22px; color: #111; transition: border-color .2s ease, transform .2s ease; }
        .trader-action:hover { color: #111; border-color: #111; transform: translateY(-2px); }
        .trader-action h3 { margin: 0 0 8px; color: #111; font-size: 20px; font-weight: 700; }
        .trader-action p { margin: 0; color: #666; line-height: 1.75; font-size: 14px; }
        .trader-action span { margin-top: 18px; display: inline-flex; width: fit-content; min-height: 34px; align-items: center; justify-content: center; border-radius: 3px; background: #111; color: #fff; padding: 6px 14px; font-size: 13px; font-weight: 800; }
        .trader-action--line span { background: #f5f5f5; color: #111; }
        .trader-orders-preview { overflow: hidden; }
        .trader-orders-preview__head { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 16px 18px; border-bottom: 1px solid #eee; background: #fafafa; }
        .trader-orders-preview__head h3 { margin: 0; color: #111; font-size: 18px; font-weight: 700; }
        .trader-orders-preview__body { padding: 0; }
        .trader-order-row { display: grid; grid-template-columns: minmax(0, 1fr) auto auto; gap: 14px; align-items: center; padding: 15px 18px; border-bottom: 1px solid #eee; }
        .trader-order-row:last-child { border-bottom: 0; }
        .trader-order-row strong { color: #111; }
        .trader-order-row small { display: block; color: #777; margin-top: 3px; }
        .trader-badge { display: inline-flex; min-height: 28px; align-items: center; padding: 4px 10px; border-radius: 999px; background: #fff5d6; color: #8a5a00; font-size: 12px; font-weight: 800; white-space: nowrap; }
        .trader-account-details { margin-bottom: 24px; overflow: hidden; }
        .trader-account-details__head { padding: 16px 18px; border-bottom: 1px solid #eee; background: #fafafa; }
        .trader-account-details__head h3 { margin: 0; color: #111; font-size: 18px; font-weight: 700; }
        .trader-account-details__grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0; }
        .trader-account-details__item { padding: 16px 18px; border-right: 1px solid #eee; border-bottom: 1px solid #eee; }
        .trader-account-details__item:nth-child(4n) { border-right: 0; }
        .trader-account-details__item span { display: block; color: #777; font-size: 13px; margin-bottom: 6px; }
        .trader-account-details__item strong { display: block; color: #111; font-size: 15px; font-weight: 800; overflow-wrap: anywhere; }
        @media (max-width: 991px) {
            .trader-dashboard__head, .trader-dashboard__stats, .trader-dashboard__actions { grid-template-columns: 1fr; }
            .trader-order-row { grid-template-columns: 1fr; align-items: start; }
            .trader-account-details__grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .trader-account-details__item:nth-child(4n) { border-right: 1px solid #eee; }
            .trader-account-details__item:nth-child(2n) { border-right: 0; }
        }
        @media (max-width: 575px) {
            .trader-account-details__grid { grid-template-columns: 1fr; }
            .trader-account-details__item,
            .trader-account-details__item:nth-child(2n),
            .trader-account-details__item:nth-child(4n) { border-right: 0; }
        }
    </style>
@endpush

@section('content')
    @include('frontend.pages.trader.partials.header', ['traderCartCount' => $trader_cart_count ?? 0])

    <main>
        @include('frontend.partials.page-title', [
            'title' => $page_title ?? ($isArabic ? 'لوحة التاجر' : 'Trader Dashboard'),
            'subtitle' => $page_subtitle ?? '',
            'breadcrumbs' => $breadcrumb_items ?? [],
            'background' => $page_title_background ?? null,
        ])

        <section class="trader-dashboard">
            <div class="container">
                <div class="trader-dashboard__head">
                    <div class="trader-dashboard__welcome">
                        <div class="trader-dashboard__kicker">{{ $isArabic ? 'بوابة تجار الجملة' : 'Wholesale Trader Portal' }}</div>
                        <h2>{{ $isArabic ? 'مرحباً، ' : 'Welcome, ' }}{{ $trader->name }}</h2>
                        <p>{{ $isArabic ? 'من هذه اللوحة يمكنك تصفح منتجات الجملة المتاحة لمجموعتك، إنشاء طلب جديد، ومتابعة حالة الطلبات التي تم إرسالها.' : 'Use this dashboard to browse wholesale products, create orders, and track submitted order statuses.' }}</p>
                    </div>

                    <div class="trader-dashboard__account">
                        <span>{{ $isArabic ? 'رقم الحساب' : 'Account number' }}</span>
                        <strong dir="ltr">{{ $trader->account_no }}</strong>
                    </div>
                </div>

                <div class="trader-dashboard__stats">
                    <div class="trader-stat">
                        <span>{{ $isArabic ? 'مجموعة الجملة' : 'Wholesale group' }}</span>
                        <strong>{{ $groupName }}</strong>
                    </div>
                    <div class="trader-stat">
                        <span>{{ $isArabic ? 'عدد الطلبات' : 'Orders count' }}</span>
                        <strong dir="ltr">{{ $orders_count ?? 0 }}</strong>
                    </div>
                    <div class="trader-stat">
                        <span>{{ $isArabic ? 'حالة الحساب' : 'Account status' }}</span>
                        <strong>{{ $trader->status === 'active' ? ($isArabic ? 'نشط' : 'Active') : ($isArabic ? 'غير نشط' : 'Inactive') }}</strong>
                    </div>
                </div>

                <div class="trader-dashboard__actions">
                    <a href="{{ route('front.trader.products.index') }}" class="trader-action">
                        <div>
                            <h3>{{ $isArabic ? 'منتجات الجملة' : 'Wholesale products' }}</h3>
                            <p>{{ $isArabic ? 'عرض المنتجات والسيريات المتاحة لحسابك التجاري.' : 'Browse products and series available for your trader group.' }}</p>
                        </div>
                        <span>{{ $isArabic ? 'عرض المنتجات' : 'Browse products' }}</span>
                    </a>
                    <a href="{{ route('front.trader.cart.index') }}" class="trader-action trader-action--line">
                        <div>
                            <h3>{{ $isArabic ? 'طلب الجملة المؤقت' : 'Wholesale cart' }}</h3>
                            <p>
                                {{ $isArabic ? 'البنود التي أضفتها قبل إرسال الطلب النهائي.' : 'Items added before final order submission.' }}
                                <strong dir="ltr">({{ (int) ($trader_cart_count ?? 0) }})</strong>
                            </p>
                        </div>
                        <span>{{ $isArabic ? 'مراجعة الطلب' : 'Review cart' }}</span>
                    </a>
                    <a href="{{ route('front.trader.orders.index') }}" class="trader-action trader-action--line">
                        <div>
                            <h3>{{ $isArabic ? 'طلباتي' : 'My orders' }}</h3>
                            <p>{{ $isArabic ? 'متابعة طلبات الجملة وحالاتها بعد الإرسال.' : 'Track your wholesale orders and current status.' }}</p>
                        </div>
                        <span>{{ $isArabic ? 'عرض الطلبات' : 'View orders' }}</span>
                    </a>
                </div>

                <section class="trader-account-details" id="trader-account-summary">
                    <div class="trader-account-details__head">
                        <h3>{{ $isArabic ? 'بيانات الحساب' : 'Account details' }}</h3>
                    </div>
                    <div class="trader-account-details__grid">
                        <div class="trader-account-details__item">
                            <span>{{ $isArabic ? 'رقم الحساب' : 'Account number' }}</span>
                            <strong dir="ltr">{{ $trader->account_no ?: '-' }}</strong>
                        </div>
                        <div class="trader-account-details__item">
                            <span>{{ $isArabic ? 'اسم التاجر' : 'Trader name' }}</span>
                            <strong>{{ $trader->name ?: '-' }}</strong>
                        </div>
                        <div class="trader-account-details__item">
                            <span>{{ $isArabic ? 'الجوال' : 'Mobile' }}</span>
                            <strong dir="ltr">{{ $trader->mobile ?: '-' }}</strong>
                        </div>
                        <div class="trader-account-details__item">
                            <span>{{ $isArabic ? 'البريد الإلكتروني' : 'Email' }}</span>
                            <strong dir="ltr">{{ $trader->email ?: '-' }}</strong>
                        </div>
                        <div class="trader-account-details__item">
                            <span>{{ $isArabic ? 'مجموعة الجملة' : 'Wholesale group' }}</span>
                            <strong>{{ $groupName }}</strong>
                        </div>
                        <div class="trader-account-details__item">
                            <span>{{ $isArabic ? 'المدينة' : 'City' }}</span>
                            <strong>{{ $trader->city ?: '-' }}</strong>
                        </div>
                        <div class="trader-account-details__item">
                            <span>{{ $isArabic ? 'المنطقة' : 'Area' }}</span>
                            <strong>{{ $trader->area ?: '-' }}</strong>
                        </div>
                        <div class="trader-account-details__item">
                            <span>{{ $isArabic ? 'العنوان' : 'Address' }}</span>
                            <strong>{{ $trader->address_line ?: '-' }}</strong>
                        </div>
                    </div>
                </section>

                <section class="trader-orders-preview">
                    <div class="trader-orders-preview__head">
                        <h3>{{ $isArabic ? 'آخر الطلبات' : 'Latest orders' }}</h3>
                        <a href="{{ route('front.trader.orders.index') }}" class="text-decoration-underline">{{ $isArabic ? 'كل الطلبات' : 'All orders' }}</a>
                    </div>
                    <div class="trader-orders-preview__body">
                        @forelse ($latest_orders ?? [] as $order)
                            <a href="{{ route('front.trader.orders.show', $order->order_no) }}" class="trader-order-row">
                                <div>
                                    <strong dir="ltr">{{ $order->order_no }}</strong>
                                    <small dir="ltr">{{ optional($order->created_at)->format('Y-m-d H:i') }}</small>
                                </div>
                                <span class="trader-badge">{{ $order->status === 'pending' ? ($isArabic ? 'قيد المراجعة' : 'Pending') : $order->status }}</span>
                                <strong dir="ltr">{{ number_format((float) $order->total, 0) }}</strong>
                            </a>
                        @empty
                            <div class="p-4 text-muted">{{ $isArabic ? 'لا توجد طلبات حتى الآن.' : 'No orders yet.' }}</div>
                        @endforelse
                    </div>
                </section>
            </div>
        </section>
    </main>
    @include('frontend.pages.trader.partials.footer')
@endsection
