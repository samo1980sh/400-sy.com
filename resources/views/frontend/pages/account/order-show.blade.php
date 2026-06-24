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


    <style>
        .order-rating-card {
            border: 1px solid #eee;
            border-radius: 14px;
            padding: 24px;
            background: #fff;
        }

        .order-rating-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 22px;
            padding-bottom: 18px;
            border-bottom: 1px solid #eee;
        }

        .order-rating-title {
            margin: 0 0 8px;
            font-size: 22px;
            font-weight: 800;
        }

        .order-rating-subtitle {
            color: #6b7280;
            margin: 0;
            font-size: 15px;
        }

        .order-rating-icon {
            width: 42px;
            height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #fff5df;
            color: #f59e0b;
            font-size: 24px;
            flex: 0 0 auto;
        }

        .order-rating-options {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 14px;
            margin: 18px 0 22px;
        }

        .order-rating-option {
            position: relative;
            cursor: pointer;
            margin: 0;
        }

        .order-rating-option input {
            position: absolute;
            opacity: 0;
        }

        .order-rating-box {
            min-height: 128px;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 18px 10px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: #fff;
            text-align: center;
            transition: .2s ease;
        }

        .order-rating-stars {
            color: #f59e0b;
            font-size: 22px;
            line-height: 1;
            white-space: nowrap;
        }

        .order-rating-label {
            font-weight: 700;
            color: #111827;
        }

        .order-rating-count {
            color: #6b7280;
            font-size: 13px;
        }

        .order-rating-radio {
            width: 18px;
            height: 18px;
            border: 2px solid #cfd4dc;
            border-radius: 50%;
            position: relative;
            display: inline-block;
            margin-top: 4px;
        }

        .order-rating-option input:checked + .order-rating-box {
            border-color: #f59e0b;
            background: #fffaf0;
            box-shadow: 0 8px 22px rgba(245, 158, 11, .14);
        }

        .order-rating-option input:checked + .order-rating-box .order-rating-radio {
            border-color: #0d6efd;
        }

        .order-rating-option input:checked + .order-rating-box .order-rating-radio::after {
            content: '';
            position: absolute;
            inset: 4px;
            border-radius: 50%;
            background: #0d6efd;
        }

        .order-rating-note {
            border: 1px solid #ffe0a3;
            background: #fffaf0;
            color: #4b5563;
            border-radius: 12px;
            padding: 13px 16px;
            margin-bottom: 20px;
        }

        .order-rating-textarea {
            min-height: 125px;
            border-radius: 12px;
            resize: vertical;
        }

        .order-rating-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            margin-top: 18px;
        }

        .order-rating-privacy {
            color: #6b7280;
            font-size: 13px;
        }

        @media (max-width: 991.98px) {
            .order-rating-options {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 575.98px) {
            .order-rating-options {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="account-card mb_24 order-rating-card" data-order-rating-card>
        <div class="order-rating-head">
            <div>
                <h5 class="order-rating-title">{!! '&#1578;&#1602;&#1610;&#1610;&#1605; &#1575;&#1604;&#1591;&#1604;&#1576;' !!}</h5>
                <p class="order-rating-subtitle">{!! '&#1588;&#1575;&#1585;&#1603;&#1606;&#1575; &#1585;&#1571;&#1610;&#1603; &#1576;&#1593;&#1583; &#1575;&#1587;&#1578;&#1604;&#1575;&#1605; &#1575;&#1604;&#1591;&#1604;&#1576;&#1548; &#1578;&#1602;&#1610;&#1610;&#1605;&#1603; &#1610;&#1587;&#1575;&#1593;&#1583;&#1606;&#1575; &#1593;&#1604;&#1609; &#1578;&#1581;&#1587;&#1610;&#1606; &#1575;&#1604;&#1582;&#1583;&#1605;&#1577;.' !!}</p>
            </div>
            <span class="order-rating-icon">{!! '&#9734;' !!}</span>
        </div>

        @if ($order->rating)
            <div class="order-rating-note">
                <strong>{!! '&#1578;&#1602;&#1610;&#1610;&#1605;&#1603;:' !!}</strong>
                <span style="color:#f59e0b; font-size:22px;">
                    @for ($i = 1; $i <= 5; $i++)
                        {!! $i <= (int) $order->rating->rating ? '&#9733;' : '&#9734;' !!}
                    @endfor
                </span>
            </div>

            @if ($order->rating->comment)
                <div class="text-muted">{{ $order->rating->comment }}</div>
            @endif

            <small class="text-muted d-block mt_8">{{ optional($order->rating->created_at)->format('Y-m-d H:i') }}</small>
        @elseif ($order->status === 'delivered')
            <form method="POST" action="{{ route('front.account.orders.rating.store', $order->order_no) }}">
                @csrf

                <div class="text-center fw-7 mb_12">{!! '&#1575;&#1582;&#1578;&#1585; &#1593;&#1583;&#1583; &#1575;&#1604;&#1606;&#1580;&#1608;&#1605;' !!}</div>

                <div class="order-rating-options">
                    @foreach ([1 => '&#1590;&#1593;&#1610;&#1601;', 2 => '&#1605;&#1578;&#1608;&#1587;&#1591;', 3 => '&#1580;&#1610;&#1583;', 4 => '&#1580;&#1610;&#1583; &#1580;&#1583;&#1575;&#1611;', 5 => '&#1605;&#1605;&#1578;&#1575;&#1586;'] as $rate => $label)
                        <label class="order-rating-option">
                            <input type="radio" name="rating" value="{{ $rate }}" @checked((int) old('rating', 5) === $rate) required>
                            <span class="order-rating-box">
                                <span class="order-rating-stars">{!! str_repeat('&#9733;', $rate) !!}</span>
                                <span class="order-rating-label">{!! $label !!}</span>
                                <span class="order-rating-count">{!! $rate . ' ' . ($rate === 1 ? '&#1606;&#1580;&#1605;&#1577;' : '&#1606;&#1580;&#1608;&#1605;') !!}</span>
                                <span class="order-rating-radio"></span>
                            </span>
                        </label>
                    @endforeach
                </div>

                @error('rating')<div class="text-danger small mt_6">{{ $message }}</div>@enderror

                <div class="order-rating-note">
                    {!! '&#1606;&#1602;&#1583;&#1585; &#1608;&#1602;&#1578;&#1603; &#1608;&#1605;&#1604;&#1575;&#1581;&#1592;&#1575;&#1578;&#1603; &#1575;&#1604;&#1602;&#1610;&#1605;&#1577;&#1548; &#1603;&#1604; &#1578;&#1602;&#1610;&#1610;&#1605; &#1610;&#1581;&#1583;&#1579; &#1601;&#1585;&#1602;&#1575;&#1611; &#1601;&#1610; &#1578;&#1581;&#1587;&#1610;&#1606; &#1582;&#1583;&#1605;&#1575;&#1578;&#1606;&#1575;.' !!}
                </div>

                <div class="mb_16">
                    <label for="order-rating-comment" class="checkout-label">{!! '&#1605;&#1604;&#1575;&#1581;&#1592;&#1575;&#1578;&#1603;' !!} <span class="text-muted fw-normal">{!! '(&#1575;&#1582;&#1578;&#1610;&#1575;&#1585;&#1610;)' !!}</span></label>
                    <textarea
                        id="order-rating-comment"
                        name="comment"
                        rows="4"
                        maxlength="1000"
                        class="form-control order-rating-textarea @error('comment') is-invalid @enderror"
                        placeholder="{!! '&#1575;&#1603;&#1578;&#1576; &#1605;&#1604;&#1575;&#1581;&#1592;&#1575;&#1578;&#1603; &#1581;&#1608;&#1604; &#1575;&#1604;&#1591;&#1604;&#1576; &#1571;&#1608; &#1575;&#1604;&#1582;&#1583;&#1605;&#1577;...' !!}"
                    >{{ old('comment') }}</textarea>
                    @error('comment')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="order-rating-footer">
                    <button type="submit" class="tf-btn btn-fill animate-hover-btn radius-3 justify-content-center">
                        {!! '&#1573;&#1585;&#1587;&#1575;&#1604; &#1575;&#1604;&#1578;&#1602;&#1610;&#1610;&#1605;' !!}
                    </button>

                    <div class="order-rating-privacy">
                        {!! '&#1578;&#1602;&#1610;&#1610;&#1605;&#1603; &#1610;&#1576;&#1602;&#1609; &#1587;&#1585;&#1610;&#1575;&#1611; &#1608;&#1604;&#1606; &#1610;&#1578;&#1605; &#1606;&#1588;&#1585;&#1607; &#1601;&#1610; &#1575;&#1604;&#1605;&#1578;&#1580;&#1585;.' !!}
                    </div>
                </div>
            </form>
        @else
            <p class="text-muted mb-0">{!! '&#1587;&#1610;&#1592;&#1607;&#1585; &#1582;&#1610;&#1575;&#1585; &#1578;&#1602;&#1610;&#1610;&#1605; &#1575;&#1604;&#1591;&#1604;&#1576; &#1576;&#1593;&#1583; &#1575;&#1587;&#1578;&#1604;&#1575;&#1605;&#1607;.' !!}</p>
        @endif
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
