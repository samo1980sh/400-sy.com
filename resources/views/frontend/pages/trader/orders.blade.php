@extends('frontend.layouts.app')

@php
    $locale = app()->getLocale();
    $isArabic = $locale === 'ar';
    $currency = $isArabic ? 'ل.س' : 'SYP';
    $statusLabels = [
        'pending' => $isArabic ? 'قيد المراجعة' : 'Pending',
        'confirmed' => $isArabic ? 'مؤكد' : 'Confirmed',
        'shipped' => $isArabic ? 'مشحون' : 'Shipped',
        'delivered' => $isArabic ? 'مسلم' : 'Delivered',
        'cancelled' => $isArabic ? 'ملغى' : 'Cancelled',
    ];
    $paymentLabels = [
        'unpaid' => $isArabic ? 'غير مدفوع' : 'Unpaid',
        'paid' => $isArabic ? 'مدفوع' : 'Paid',
    ];
@endphp

@section('title', $page_title ?? ($isArabic ? 'طلباتي' : 'My Orders'))
@section('meta_description', $page_subtitle ?? __('front.brand'))

@push('styles')
    <style>
        .trader-page { padding: 56px 0 72px; background: #fff; font-family: "Albert Sans", sans-serif; }
        .trader-page-head { display: flex; align-items: flex-end; justify-content: space-between; gap: 18px; margin-bottom: 24px; }
        .trader-page-head h2 { margin: 0; color: #111; font-size: clamp(24px, 3vw, 36px); font-weight: 600; line-height: 1.35; }
        .trader-page-head p { margin: 8px 0 0; color: #666; line-height: 1.8; }
        .trader-btn { min-height: 42px; display: inline-flex; align-items: center; justify-content: center; border: 1px solid #111; background: #111; color: #fff; padding: 8px 18px; border-radius: 3px; font-weight: 700; }
        .trader-btn:hover { color: #fff; background: #333; border-color: #333; }
        .trader-btn--line { background: #fff; color: #111; border-color: #d8d8d8; }
        .trader-btn--line:hover { color: #111; background: #f7f7f7; border-color: #111; }
        .trader-orders-filters { display: grid; grid-template-columns: minmax(180px, 240px) minmax(180px, 240px) auto auto; gap: 10px; align-items: end; border: 1px solid #e7e7e7; border-radius: 8px; padding: 14px; margin-bottom: 22px; }
        .trader-filter-field label { display: block; color: #666; font-size: 12px; font-weight: 800; margin-bottom: 6px; }
        .trader-filter-field select { width: 100%; min-height: 42px; border: 1px solid #ddd; border-radius: 3px; padding: 8px 10px; color: #111; background: #fff; }
        .trader-orders-card { border: 1px solid #e7e7e7; background: #fff; border-radius: 8px; overflow: hidden; }
        .trader-orders-table-wrap { overflow-x: auto; }
        .trader-orders-table { width: 100%; min-width: 860px; border-collapse: collapse; }
        .trader-orders-table th, .trader-orders-table td { padding: 16px; border-bottom: 1px solid #eee; text-align: start; vertical-align: middle; }
        .trader-orders-table th { color: #777; font-size: 13px; font-weight: 700; background: #fafafa; }
        .trader-orders-table tbody tr:last-child td { border-bottom: 0; }
        .trader-orders-table strong { color: #111; }
        .trader-badge { display: inline-flex; align-items: center; min-height: 28px; padding: 4px 10px; border-radius: 999px; background: #f2f2f2; color: #333; font-size: 12px; font-weight: 800; white-space: nowrap; }
        .trader-badge--pending, .trader-badge--unpaid { background: #fff5d6; color: #8a5a00; }
        .trader-badge--confirmed { background: #e8f1ff; color: #174ea6; }
        .trader-badge--shipped { background: #f0eafe; color: #5b2db8; }
        .trader-badge--delivered, .trader-badge--paid { background: #e8f7ee; color: #137333; }
        .trader-badge--cancelled { background: #fdeaea; color: #a61b1b; }
        .trader-empty { border: 1px solid #e7e7e7; border-radius: 8px; padding: 34px; text-align: center; color: #666; line-height: 1.9; }
        .trader-empty h2 { margin: 0 0 8px; color: #111; font-size: 24px; }
        @media (max-width: 767px) {
            .trader-page { padding: 42px 0 58px; }
            .trader-page-head { display: block; }
            .trader-page-head .trader-btn { margin-top: 16px; }
            .trader-orders-filters { grid-template-columns: 1fr; }
        }
    </style>
@endpush

@section('content')
    @include('frontend.pages.trader.partials.header', ['traderCartCount' => $trader_cart_count ?? 0])

    <main>
        @include('frontend.partials.page-title', [
            'title' => $page_title ?? ($isArabic ? 'طلباتي' : 'My Orders'),
            'subtitle' => $page_subtitle ?? '',
            'breadcrumbs' => $breadcrumb_items ?? [],
            'background' => $page_title_background ?? null,
        ])

        <section class="trader-page">
            <div class="container">
                <div class="trader-page-head">
                    <div>
                        <h2>{{ $isArabic ? 'طلبات الجملة' : 'Wholesale Orders' }}</h2>
                        <p>{{ $isArabic ? 'هنا تظهر كل طلبات الجملة التي أرسلتها من حسابك.' : 'All wholesale orders submitted from your account are listed here.' }}</p>
                    </div>
                    <a href="{{ route('front.trader.products.index') }}" class="trader-btn">
                        {{ $isArabic ? 'إضافة طلب جديد' : 'New Order' }}
                    </a>
                </div>

                <form method="GET" action="{{ route('front.trader.orders.index') }}" class="trader-orders-filters">
                    <div class="trader-filter-field">
                        <label for="trader_order_status">{{ $isArabic ? 'حالة الطلب' : 'Order status' }}</label>
                        <select id="trader_order_status" name="status">
                            <option value="">{{ $isArabic ? 'كل الحالات' : 'All statuses' }}</option>
                            @foreach ($statusLabels as $statusKey => $statusLabel)
                                <option value="{{ $statusKey }}" @selected(($filters['status'] ?? '') === $statusKey)>{{ $statusLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="trader-filter-field">
                        <label for="trader_payment_status">{{ $isArabic ? 'حالة الدفع' : 'Payment status' }}</label>
                        <select id="trader_payment_status" name="payment_status">
                            <option value="">{{ $isArabic ? 'كل حالات الدفع' : 'All payment statuses' }}</option>
                            @foreach ($paymentLabels as $paymentKey => $paymentLabel)
                                <option value="{{ $paymentKey }}" @selected(($filters['payment_status'] ?? '') === $paymentKey)>{{ $paymentLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="trader-btn">{{ $isArabic ? 'تطبيق' : 'Apply' }}</button>
                    <a href="{{ route('front.trader.orders.index') }}" class="trader-btn trader-btn--line">{{ $isArabic ? 'مسح' : 'Clear' }}</a>
                </form>

                @if ($orders->isEmpty())
                    <div class="trader-empty">
                        <h2>{{ $isArabic ? 'لا توجد طلبات بعد' : 'No orders yet' }}</h2>
                        <p>{{ $isArabic ? 'تصفح منتجات الجملة وأضف السيريات المطلوبة ثم أرسل أول طلب.' : 'Browse wholesale products, add series, then submit your first order.' }}</p>
                        <a href="{{ route('front.trader.products.index') }}" class="trader-btn">{{ $isArabic ? 'تصفح المنتجات' : 'Browse Products' }}</a>
                    </div>
                @else
                    <div class="trader-orders-card">
                        <div class="trader-orders-table-wrap">
                            <table class="trader-orders-table">
                                <thead>
                                <tr>
                                    <th>{{ $isArabic ? 'رقم الطلب' : 'Order No.' }}</th>
                                    <th>{{ $isArabic ? 'التاريخ' : 'Date' }}</th>
                                    <th>{{ $isArabic ? 'البنود' : 'Items' }}</th>
                                    <th>{{ $isArabic ? 'حالة الطلب' : 'Order Status' }}</th>
                                    <th>{{ $isArabic ? 'حالة الدفع' : 'Payment' }}</th>
                                    <th>{{ $isArabic ? 'الإجمالي' : 'Total' }}</th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach ($orders as $order)
                                    <tr>
                                        <td><strong dir="ltr">{{ $order->order_no }}</strong></td>
                                        <td dir="ltr">{{ optional($order->created_at)->format('Y-m-d H:i') }}</td>
                                        <td dir="ltr">{{ (int) $order->items_count }}</td>
                                        <td><span class="trader-badge trader-badge--{{ $order->status }}">{{ $statusLabels[$order->status] ?? $order->status }}</span></td>
                                        <td><span class="trader-badge trader-badge--{{ $order->payment_status }}">{{ $paymentLabels[$order->payment_status] ?? $order->payment_status }}</span></td>
                                        <td><strong dir="ltr">{{ number_format((float) $order->total, 0) }} {{ $currency }}</strong></td>
                                        <td>
                                            <a href="{{ route('front.trader.orders.show', $order->order_no) }}" class="trader-btn trader-btn--line">
                                                {{ $isArabic ? 'التفاصيل' : 'Details' }}
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mt-4">{{ $orders->links('pagination::bootstrap-5') }}</div>
                @endif
            </div>
        </section>
    </main>
    @include('frontend.pages.trader.partials.footer')
@endsection
