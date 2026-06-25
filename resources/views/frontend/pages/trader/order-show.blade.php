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

@section('title', $page_title ?? $order->order_no)
@section('meta_description', $page_subtitle ?? __('front.brand'))

@push('styles')
    <style>
        .trader-order-show { padding: 56px 0 72px; background: #fff; font-family: "Albert Sans", sans-serif; }
        .trader-order-top { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 22px; }
        .trader-order-top h2 { margin: 0; color: #111; font-size: clamp(24px, 3vw, 36px); font-weight: 600; }
        .trader-btn { min-height: 42px; display: inline-flex; align-items: center; justify-content: center; border: 1px solid #d8d8d8; background: #fff; color: #111; padding: 8px 18px; border-radius: 3px; font-weight: 700; }
        .trader-btn:hover { color: #111; background: #f7f7f7; border-color: #111; }
        .trader-summary-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; margin-bottom: 22px; }
        .trader-panel, .trader-summary-card { border: 1px solid #e7e7e7; border-radius: 8px; background: #fff; }
        .trader-summary-card { padding: 16px; }
        .trader-label { display: block; color: #777; font-size: 13px; margin-bottom: 7px; }
        .trader-value { color: #111; font-weight: 800; overflow-wrap: anywhere; }
        .trader-badge { display: inline-flex; align-items: center; min-height: 28px; padding: 4px 10px; border-radius: 999px; background: #f2f2f2; color: #333; font-size: 12px; font-weight: 800; white-space: nowrap; }
        .trader-badge--pending, .trader-badge--unpaid { background: #fff5d6; color: #8a5a00; }
        .trader-badge--confirmed { background: #e8f1ff; color: #174ea6; }
        .trader-badge--shipped { background: #f0eafe; color: #5b2db8; }
        .trader-badge--delivered, .trader-badge--paid { background: #e8f7ee; color: #137333; }
        .trader-badge--cancelled { background: #fdeaea; color: #a61b1b; }
        .trader-layout { display: grid; grid-template-columns: minmax(0, 1fr) 340px; gap: 22px; align-items: start; }
        .trader-panel { overflow: hidden; margin-bottom: 18px; }
        .trader-panel-head { padding: 15px 18px; border-bottom: 1px solid #eee; background: #fafafa; }
        .trader-panel-head h3 { margin: 0; color: #111; font-size: 18px; font-weight: 700; }
        .trader-panel-body { padding: 18px; }
        .trader-items { width: 100%; min-width: 760px; border-collapse: collapse; }
        .trader-items th, .trader-items td { padding: 13px 14px; border-bottom: 1px solid #eee; text-align: start; vertical-align: top; }
        .trader-items th { color: #777; background: #fafafa; font-size: 12px; font-weight: 800; }
        .trader-table-wrap { overflow-x: auto; }
        .trader-total-row { display: flex; justify-content: space-between; gap: 16px; padding: 11px 0; border-bottom: 1px solid #eee; }
        .trader-total-row:last-child { border-bottom: 0; }
        .trader-total-row strong { color: #111; }
        .trader-total-final { font-size: 18px; font-weight: 900; }
        .trader-history { display: grid; gap: 12px; }
        .trader-history-item { border: 1px solid #eee; border-radius: 8px; padding: 13px; }
        .trader-history-flow { display: flex; flex-wrap: wrap; gap: 7px; align-items: center; margin-bottom: 8px; }
        .trader-history-item small { display: block; color: #777; margin-top: 6px; }
        .trader-alert { margin-bottom: 18px; border: 1px solid rgba(25,135,84,.25); background: rgba(25,135,84,.08); color: #0f5132; border-radius: 8px; padding: 13px 16px; font-weight: 700; }
        @media (max-width: 991px) {
            .trader-layout, .trader-summary-grid { grid-template-columns: 1fr; }
            .trader-order-top { display: block; }
            .trader-order-top .trader-btn { margin-top: 14px; }
        }
    </style>
@endpush

@section('content')
    @include('frontend.pages.trader.partials.header', ['traderCartCount' => $trader_cart_count ?? 0])

    <main>
        @include('frontend.partials.page-title', [
            'title' => $page_title ?? $order->order_no,
            'subtitle' => $page_subtitle ?? '',
            'breadcrumbs' => $breadcrumb_items ?? [],
            'background' => $page_title_background ?? null,
        ])

        <section class="trader-order-show">
            <div class="container">
                @if (session('success'))
                    <div class="trader-alert">{{ session('success') }}</div>
                @endif

                <div class="trader-order-top">
                    <h2><span dir="ltr">{{ $order->order_no }}</span></h2>
                    <a href="{{ route('front.trader.orders.index') }}" class="trader-btn">{{ $isArabic ? 'العودة إلى طلباتي' : 'Back to Orders' }}</a>
                </div>

                <div class="trader-summary-grid">
                    <div class="trader-summary-card">
                        <span class="trader-label">{{ $isArabic ? 'تاريخ الطلب' : 'Order Date' }}</span>
                        <div class="trader-value" dir="ltr">{{ optional($order->created_at)->format('Y-m-d H:i') }}</div>
                    </div>
                    <div class="trader-summary-card">
                        <span class="trader-label">{{ $isArabic ? 'حالة الطلب' : 'Order Status' }}</span>
                        <span class="trader-badge trader-badge--{{ $order->status }}">{{ $statusLabels[$order->status] ?? $order->status }}</span>
                    </div>
                    <div class="trader-summary-card">
                        <span class="trader-label">{{ $isArabic ? 'حالة الدفع' : 'Payment Status' }}</span>
                        <span class="trader-badge trader-badge--{{ $order->payment_status }}">{{ $paymentLabels[$order->payment_status] ?? $order->payment_status }}</span>
                    </div>
                    <div class="trader-summary-card">
                        <span class="trader-label">{{ $isArabic ? 'الإجمالي' : 'Total' }}</span>
                        <div class="trader-value" dir="ltr">{{ number_format((float) $order->total, 0) }} {{ $currency }}</div>
                    </div>
                </div>

                <div class="trader-layout">
                    <div>
                        <section class="trader-panel">
                            <div class="trader-panel-head"><h3>{{ $isArabic ? 'بنود الطلب' : 'Order Items' }}</h3></div>
                            <div class="trader-table-wrap">
                                <table class="trader-items">
                                    <thead>
                                    <tr>
                                        <th>{{ $isArabic ? 'المنتج' : 'Product' }}</th>
                                        <th>{{ $isArabic ? 'اللون' : 'Color' }}</th>
                                        <th>{{ $isArabic ? 'السيرية' : 'Series' }}</th>
                                        <th>{{ $isArabic ? 'المقاس' : 'Size' }}</th>
                                        <th>{{ $isArabic ? 'الكمية' : 'Qty' }}</th>
                                        <th>{{ $isArabic ? 'السعر' : 'Price' }}</th>
                                        <th>{{ $isArabic ? 'الإجمالي' : 'Total' }}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach ($order->items as $item)
                                        <tr>
                                            <td>
                                                <strong>{{ $item->product_name_snapshot }}</strong>
                                                <div class="text-muted small" dir="ltr">{{ $item->product_model_no_snapshot }}</div>
                                            </td>
                                            <td>{{ $item->color_name_snapshot ?: '—' }}</td>
                                            <td>{{ $item->series_snapshot ?: $item->series_group }}</td>
                                            <td dir="ltr">{{ $item->size_text }}</td>
                                            <td dir="ltr">{{ number_format((int) $item->quantity) }}</td>
                                            <td dir="ltr">{{ number_format((float) $item->unit_price, 0) }}</td>
                                            <td><strong dir="ltr">{{ number_format((float) $item->line_total, 0) }} {{ $currency }}</strong></td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </section>

                        @if (filled($order->notes))
                            <section class="trader-panel">
                                <div class="trader-panel-head"><h3>{{ $isArabic ? 'ملاحظات الطلب' : 'Order Notes' }}</h3></div>
                                <div class="trader-panel-body">{{ $order->notes }}</div>
                            </section>
                        @endif
                    </div>

                    <aside>
                        <section class="trader-panel">
                            <div class="trader-panel-head"><h3>{{ $isArabic ? 'ملخص المبالغ' : 'Totals' }}</h3></div>
                            <div class="trader-panel-body">
                                <div class="trader-total-row">
                                    <span>{{ $isArabic ? 'قبل الخصم' : 'Before Discount' }}</span>
                                    <strong dir="ltr">{{ number_format((float) $order->total_before_discount, 0) }} {{ $currency }}</strong>
                                </div>
                                <div class="trader-total-row">
                                    <span>{{ $isArabic ? 'الخصم' : 'Discount' }}</span>
                                    <strong dir="ltr">{{ number_format((float) $order->discount_value, 0) }} {{ $currency }}</strong>
                                </div>
                                <div class="trader-total-row">
                                    <span>{{ $isArabic ? 'الشحن' : 'Shipping' }}</span>
                                    <strong dir="ltr">{{ number_format((float) $order->shipping_cost, 0) }} {{ $currency }}</strong>
                                </div>
                                <div class="trader-total-row trader-total-final">
                                    <span>{{ $isArabic ? 'الإجمالي النهائي' : 'Final Total' }}</span>
                                    <strong dir="ltr">{{ number_format((float) $order->total, 0) }} {{ $currency }}</strong>
                                </div>
                            </div>
                        </section>

                        <section class="trader-panel">
                            <div class="trader-panel-head"><h3>{{ $isArabic ? 'سجل الحالة' : 'Status History' }}</h3></div>
                            <div class="trader-panel-body">
                                <div class="trader-history">
                                    @forelse ($order->statusHistory->sortByDesc('created_at') as $history)
                                        @php
                                            $isPaymentChange = filled($history->from_payment_status) || filled($history->to_payment_status);
                                            $fromState = $isPaymentChange ? $history->from_payment_status : $history->from_status;
                                            $toState = $isPaymentChange ? $history->to_payment_status : $history->to_status;
                                            $fromLabel = $isPaymentChange
                                                ? ($paymentLabels[$fromState] ?? ($fromState ?: '—'))
                                                : ($statusLabels[$fromState] ?? ($fromState ?: '—'));
                                            $toLabel = $isPaymentChange
                                                ? ($paymentLabels[$toState] ?? ($toState ?: '—'))
                                                : ($statusLabels[$toState] ?? ($toState ?: '—'));
                                        @endphp
                                        <div class="trader-history-item">
                                            <div class="trader-history-flow">
                                                <span class="trader-badge">{{ $isPaymentChange ? ($isArabic ? 'الدفع' : 'Payment') : ($isArabic ? 'الطلب' : 'Order') }}</span>
                                                <span>{{ $isArabic ? 'من' : 'From' }}</span>
                                                <span class="trader-badge trader-badge--{{ $fromState ?: 'neutral' }}">{{ $fromLabel }}</span>
                                                <span>{{ $isArabic ? 'إلى' : 'To' }}</span>
                                                <span class="trader-badge trader-badge--{{ $toState ?: 'neutral' }}">{{ $toLabel }}</span>
                                            </div>
                                            <strong>{{ $history->note ?: ($isArabic ? 'تحديث حالة الطلب' : 'Order status update') }}</strong>
                                            <small dir="ltr">{{ optional($history->created_at)->format('Y-m-d H:i') }}</small>
                                        </div>
                                    @empty
                                        <div class="text-muted">{{ $isArabic ? 'لا يوجد سجل حالة حتى الآن.' : 'No history yet.' }}</div>
                                    @endforelse
                                </div>
                            </div>
                        </section>
                    </aside>
                </div>
            </div>
        </section>
    </main>
    @include('frontend.pages.trader.partials.footer')
@endsection
