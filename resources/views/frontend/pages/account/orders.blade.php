@extends('frontend.pages.account.base')

@php($accountCurrency = session('selectedCurrency') ?? 'SYP')

@section('account_content')
    <div class="account-card">
        <h5 class="account-card-title">{{ __('front.account.orders') }}</h5>
        @if ($orders->isEmpty())
            <div class="text-center py-5">
                <h6>{{ __('front.account.no_orders') }}</h6>
                <p class="text-muted">{{ __('front.account.no_orders_message') }}</p>
                <a href="{{ route('front.products.index') }}" class="tf-btn btn-fill radius-3 d-inline-flex">{{ __('front.account.start_shopping') }}</a>
            </div>
        @else
            <div class="account-table-wrap">
                <table class="account-table">
                    <thead>
                    <tr>
                        <th>{{ __('front.account.order_number') }}</th>
                        <th>{{ __('front.account.order_date') }}</th>
                        <th>{{ __('front.account.items') }}</th>
                        <th>{{ __('front.account.status') }}</th>
                        <th>{{ __('front.account.payment_status') }}</th>
                        <th>{{ __('front.account.total') }}</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($orders as $order)
                        <tr>
                            <td dir="ltr">{{ $order->order_no }}</td>
                            <td>{{ optional($order->created_at)->format('Y-m-d H:i') }}</td>
                            <td>{{ $order->items_count }}</td>
                            <td><span class="account-badge">{{ __('front.account.order_status.' . $order->status) }}</span></td>
                            <td><span class="account-badge {{ $order->payment_status === 'paid' ? 'is-success' : 'is-warning' }}">{{ __('front.account.payment_statuses.' . $order->payment_status) }}</span></td>
                            <td><span class="js-currency-price" data-base-price="{{ (float) $order->total }}" data-base-currency="{{ $accountCurrency }}">{{ number_format((float) $order->total, 0) }} {{ $accountCurrency }}</span></td>
                            <td><a href="{{ route('front.account.orders.show', $order->order_no) }}" class="text-decoration-underline">{{ __('front.account.details') }}</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt_24">{{ $orders->links('pagination::bootstrap-5') }}</div>
        @endif
    </div>
@endsection
