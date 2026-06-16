@extends('frontend.pages.account.base')

@php($accountCurrency = session('selectedCurrency') ?? 'SYP')

@section('account_content')
    <div class="account-stat-grid mb_24">
        <div class="account-stat">
            <span class="text-muted">{{ __('front.account.total_orders') }}</span>
            <span class="account-stat-value">{{ $orders_count }}</span>
        </div>
        <div class="account-stat">
            <span class="text-muted">{{ __('front.account.active_orders') }}</span>
            <span class="account-stat-value">{{ $pending_orders_count }}</span>
        </div>
        <div class="account-stat">
            <span class="text-muted">{{ __('front.account.wishlist_items') }}</span>
            <span class="account-stat-value">{{ $wishlist_count ?? 0 }}</span>
        </div>
    </div>

    <div class="account-card mb_24">
        <div class="account-card-title d-flex justify-content-between align-items-center gap-3">
            <h5 class="mb-0">{{ __('front.account.recent_orders') }}</h5>
            <a href="{{ route('front.account.orders') }}" class="text-decoration-underline">{{ __('front.account.view_all') }}</a>
        </div>
        @if ($latest_orders->isEmpty())
            <p class="text-muted mb-0">{{ __('front.account.no_orders') }}</p>
        @else
            <div class="account-table-wrap">
                <table class="account-table">
                    <thead>
                    <tr>
                        <th>{{ __('front.account.order_number') }}</th>
                        <th>{{ __('front.account.order_date') }}</th>
                        <th>{{ __('front.account.status') }}</th>
                        <th>{{ __('front.account.total') }}</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($latest_orders as $order)
                        <tr>
                            <td dir="ltr">{{ $order->order_no }}</td>
                            <td>{{ optional($order->created_at)->format('Y-m-d') }}</td>
                            <td><span class="account-badge">{{ __('front.account.order_status.' . $order->status) }}</span></td>
                            <td><span class="js-currency-price" data-base-price="{{ (float) $order->total }}" data-base-currency="{{ $accountCurrency }}">{{ number_format((float) $order->total, 0) }} {{ $accountCurrency }}</span></td>
                            <td><a href="{{ route('front.account.orders.show', $order->order_no) }}" class="text-decoration-underline">{{ __('front.account.details') }}</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="account-card">
        <div class="account-card-title d-flex justify-content-between align-items-center gap-3">
            <h5 class="mb-0">{{ __('front.account.default_address') }}</h5>
            <a href="{{ route('front.account.addresses') }}" class="text-decoration-underline">{{ __('front.account.manage_addresses') }}</a>
        </div>
        @if ($default_address)
            <div class="fw-6 mb_8">{{ $default_address->label ?: __('front.checkout.address_types.' . $default_address->address_type) }}</div>
            <div>{{ $default_address->contact_name }} — <span dir="ltr">{{ $default_address->mobile }}</span></div>
            <div class="text-muted mt_6">{{ $default_address->city }}، {{ $default_address->area }}، {{ $default_address->address_line }}</div>
        @else
            <p class="text-muted mb_16">{{ __('front.account.no_saved_address') }}</p>
            <a href="{{ route('front.account.addresses') }}" class="tf-btn btn-fill radius-3 d-inline-flex">{{ __('front.account.add_address') }}</a>
        @endif
    </div>
@endsection
