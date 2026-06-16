@extends('frontend.pages.account.base')

@php
    $currency = session('selectedCurrency') ?? 'SYP';
    $paymentName = app()->getLocale() === 'ar'
        ? ($payment_method_record?->name_ar ?: $payment_method_record?->name_en ?: $order->payment_method)
        : ($payment_method_record?->name_en ?: $payment_method_record?->name_ar ?: $order->payment_method);
    $shippingName = app()->getLocale() === 'ar'
        ? ($order->shippingMethod?->name_ar ?: $order->shippingMethod?->name_en)
        : ($order->shippingMethod?->name_en ?: $order->shippingMethod?->name_ar);
@endphp

@section('account_content')
    <div class="account-card mb_24">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="text-muted mb_4">{{ __('front.account.order_number') }}</div>
                <h5 class="mb_6" dir="ltr">{{ $order->order_no }}</h5>
                <div class="text-muted">{{ optional($order->created_at)->format('Y-m-d H:i') }}</div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <span class="account-badge">{{ __('front.account.order_status.' . $order->status) }}</span>
                <span class="account-badge {{ $order->payment_status === 'paid' ? 'is-success' : 'is-warning' }}">{{ __('front.account.payment_statuses.' . $order->payment_status) }}</span>
            </div>
        </div>
    </div>

    <div class="account-card mb_24">
        <h5 class="account-card-title">{{ __('front.account.ordered_items') }}</h5>
        <div class="account-table-wrap">
            <table class="account-table">
                <thead>
                <tr>
                    <th>{{ __('front.account.product') }}</th>
                    <th>{{ __('front.products.color') }}</th>
                    <th>{{ __('front.products.size') }}</th>
                    <th>{{ __('front.account.quantity') }}</th>
                    <th>{{ __('front.account.unit_price') }}</th>
                    <th>{{ __('front.account.total') }}</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($order->items as $item)
                    <tr>
                        <td>
                            @if ($item->product?->slug)
                                <a href="{{ route('front.products.show', $item->product->slug) }}" class="link fw-6">{{ $item->product_name_snapshot }}</a>
                            @else
                                <span class="fw-6">{{ $item->product_name_snapshot }}</span>
                            @endif
                            @if ($item->product_sku_snapshot)
                                <small class="d-block text-muted" dir="ltr">{{ $item->product_sku_snapshot }}</small>
                            @endif
                        </td>
                        <td>{{ $item->color_name_snapshot ?: '—' }}</td>
                        <td>{{ $item->size_name_snapshot ?: '—' }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td><span class="js-currency-price" data-base-price="{{ (float) $item->unit_price }}" data-base-currency="{{ $currency }}">{{ number_format((float) $item->unit_price, 0) }} {{ $currency }}</span></td>
                        <td><span class="js-currency-price" data-base-price="{{ (float) $item->line_total }}" data-base-currency="{{ $currency }}">{{ number_format((float) $item->line_total, 0) }} {{ $currency }}</span></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="row g-4 mb_24">
        <div class="col-lg-7">
            <div class="account-card h-100">
                <h5 class="account-card-title">{{ __('front.account.delivery_details') }}</h5>
                <div class="mb_8"><strong>{{ $order->shipping_contact_name_snapshot }}</strong> — <span dir="ltr">{{ $order->shipping_mobile_snapshot }}</span></div>
                <div>{{ $order->shipping_city_snapshot }}، {{ $order->shipping_area_snapshot }}</div>
                <div class="mt_6">{{ $order->shipping_address_line_snapshot }}</div>
                <hr>
                <div class="d-flex justify-content-between gap-3 mb_8"><span>{{ __('front.checkout.shipping_method') }}</span><strong>{{ $shippingName ?: '—' }}</strong></div>
                <div class="d-flex justify-content-between gap-3"><span>{{ __('front.checkout.payment_method') }}</span><strong>{{ $paymentName ?: '—' }}</strong></div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="account-card h-100">
                <h5 class="account-card-title">{{ __('front.account.order_summary') }}</h5>
                <div class="d-flex justify-content-between gap-3 mb_10"><span>{{ __('front.cart.subtotal') }}</span><strong class="js-currency-price" data-base-price="{{ (float) $order->total_before_discount }}" data-base-currency="{{ $currency }}">{{ number_format((float) $order->total_before_discount, 0) }} {{ $currency }}</strong></div>
                @if ((float) $order->discount_value > 0 || (float) $order->coupon_discount_value > 0)
                    <div class="d-flex justify-content-between gap-3 mb_10"><span>{{ __('front.account.discount') }}</span><strong>-{{ number_format((float) $order->discount_value + (float) $order->coupon_discount_value, 0) }} {{ $currency }}</strong></div>
                @endif
                <div class="d-flex justify-content-between gap-3 mb_10"><span>{{ __('front.checkout.shipping_cost') }}</span><strong class="js-currency-price" data-base-price="{{ (float) $order->shipping_cost }}" data-base-currency="{{ $currency }}">{{ number_format((float) $order->shipping_cost, 0) }} {{ $currency }}</strong></div>
                <div class="d-flex justify-content-between gap-3 pt_14 border-top fs-5"><span>{{ __('front.checkout.grand_total') }}</span><strong class="js-currency-price" data-base-price="{{ (float) $order->total }}" data-base-currency="{{ $currency }}">{{ number_format((float) $order->total, 0) }} {{ $currency }}</strong></div>
            </div>
        </div>
    </div>

    @if ($order->statusHistory->isNotEmpty())
        <div class="account-card">
            <h5 class="account-card-title">{{ __('front.account.status_history') }}</h5>
            <div class="account-timeline">
                @foreach ($order->statusHistory as $history)
                    <div class="account-timeline-item">
                        <div class="fw-7">{{ __('front.account.order_status.' . $history->to_status) }}</div>
                        @if ($history->note)<div class="text-muted mt_4">{{ $history->note }}</div>@endif
                        <small class="text-muted d-block mt_4">{{ optional($history->created_at)->format('Y-m-d H:i') }}</small>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
@endsection
