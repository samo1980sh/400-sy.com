@extends('frontend.layouts.app')

@section('title', $title ?? __('front.brand'))

@section('content')
    @php
        $details = collect($details ?? []);
        $cartState = $cart_state ?? null;
        $items = collect($cartState['items'] ?? []);
    @endphp

    <main class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-xl-10">
                    <div class="card border-0 shadow-sm rounded-4 p-4 p-lg-5">
                        <div class="mb-3 text-uppercase fw-semibold small text-muted">{{ $eyebrow ?? __('front.brand') }}</div>
                        <h1 class="mb-3">{{ $title ?? __('front.brand') }}</h1>
                        <p class="mb-4 text-muted">{{ $message ?? __('front.products.page_placeholder_message') }}</p>

                        @if ($details->isNotEmpty())
                            <div class="row g-3 mb-4">
                                @foreach ($details as $detail)
                                    <div class="col-12 col-md-4">
                                        <div class="border rounded-3 p-3 h-100 bg-light">
                                            <div class="small text-uppercase text-muted mb-1">{{ $detail['label'] ?? '' }}</div>
                                            <div class="fw-semibold">{{ $detail['value'] ?? '-' }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @if ($items->isNotEmpty())
                            <div class="border rounded-3 p-3 mb-4">
                                <div class="fw-semibold mb-3">{{ __('front.cart.title') }}</div>
                                <div class="d-grid gap-3">
                                    @foreach ($items as $item)
                                        <div class="d-flex justify-content-between align-items-center gap-3">
                                            <div>
                                                <div class="fw-semibold">{{ $item['title'] ?? '' }}</div>
                                                <div class="small text-muted">{{ $item['meta_variant'] ?? '' }}</div>
                                            </div>
                                            <div class="text-end">
                                                <div class="fw-semibold">{{ $item['price_label'] ?? '' }}</div>
                                                <div class="small text-muted">x{{ $item['qty'] ?? 1 }}</div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="d-flex justify-content-between align-items-center border-top pt-3 mt-3">
                                    <span class="fw-semibold">{{ __('front.cart.subtotal') }}</span>
                                    <span class="fw-semibold">{{ $cartState['subtotal_label'] ?? '0 SYP' }}</span>
                                </div>
                            </div>
                        @endif

                        <a href="{{ $back_url ?? route('front.home') }}" class="btn btn-dark px-4">{{ __('front.nav.home') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection
