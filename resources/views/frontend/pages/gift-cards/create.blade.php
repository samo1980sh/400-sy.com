@extends('frontend.layouts.app')

@section('title', $page_title ?? 'طلب بطاقة هدية')
@section('meta_description', $page_subtitle ?? '')

@php
    $customer = $customer ?? null;
    $branches = collect($branches ?? []);
    $paymentMethods = collect($payment_methods ?? []);
    $shippingMethods = collect($shipping_methods ?? []);

    $recordLabel = function ($item) {
        if (! $item) {
            return '';
        }

        return $item->name_ar
            ?? $item->name_en
            ?? $item->name
            ?? $item->title_ar
            ?? $item->title_en
            ?? $item->title
            ?? $item->code
            ?? ('#' . $item->getKey());
    };

    $shippingCost = function ($method): int {
        foreach (['cost', 'delivery_fee', 'price', 'amount'] as $attribute) {
            if (isset($method->{$attribute})) {
                return (int) round((float) $method->{$attribute});
            }
        }

        return 0;
    };

    $currencyCodes = collect($currency_options ?? [])
        ->map(function ($option) {
            if (is_array($option)) {
                return $option['code'] ?? $option['currency'] ?? null;
            }

            return $option->code ?? $option->currency ?? null;
        })
        ->filter()
        ->map(fn ($code) => strtoupper((string) $code))
        ->prepend('SYP')
        ->unique()
        ->values();
@endphp

@push('styles')
    <style>
        .front-gift-card-page .gift-card-box {
            border: 1px solid var(--line, #e9e9e9);
            border-radius: 10px;
            background: #fff;
            padding: 24px;
        }
        .front-gift-card-page .gift-card-section-title {
            padding-bottom: 14px;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--line, #e9e9e9);
        }
        .front-gift-card-page .gift-card-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }
        .front-gift-card-page .gift-card-required::after {
            content: ' *';
            color: #dc3545;
        }
        .front-gift-card-page .gift-card-option {
            display: flex;
            gap: 12px;
            align-items: flex-start;
            padding: 14px;
            border: 1px solid var(--line, #e9e9e9);
            border-radius: 8px;
            cursor: pointer;
        }
        .front-gift-card-page .gift-card-option input { margin-top: 4px; }
        .front-gift-card-page .gift-card-option:has(input:checked) {
            border-color: var(--main, #000);
            background: #fafafa;
        }
        .front-gift-card-page .form-control,
        .front-gift-card-page .form-select { min-height: 48px; }
        .front-gift-card-page .gift-card-summary-list {
            margin: 0;
            padding: 0;
            list-style: none;
        }
        .front-gift-card-page .gift-card-summary-list li {
            padding: 8px 0;
            border-bottom: 1px solid #f1f1f1;
        }
        .front-gift-card-page .gift-card-summary-list li:last-child { border-bottom: 0; }
    </style>
@endpush

@section('content')
    @include('frontend.partials.announcement-bar', [
        'tickerItems' => $ticker_items ?? [],
        'socialLinks' => $social_links ?? [],
    ])

    @include('frontend.partials.header', [
        'navCategories' => $nav_categories ?? [],
        'currencyOptions' => $currency_options ?? [],
        'siteName' => $site_name ?? __('front.brand'),
        'cartCount' => $cart_count ?? 0,
        'wishlistCount' => $wishlist_count ?? 0,
        'wishlistUrl' => $wishlist_url ?? route('front.wishlist.index'),
    ])

    <main class="front-gift-card-page">
        @include('frontend.partials.page-title', [
            'title' => $page_title ?? 'طلب بطاقة هدية',
            'subtitle' => $page_subtitle ?? '',
            'breadcrumbs' => $breadcrumb_items ?? [],
        ])

        <section class="flat-spacing-2">
            <div class="container">
                @if (session('gift_card_success'))
                    <div class="alert alert-success mb_24">{{ session('gift_card_success') }}</div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger mb_24" role="alert">
                        <div class="fw-6 mb_8">يرجى تصحيح الأخطاء التالية:</div>
                        <ul class="mb-0 ps-4">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @unless ($customer)
                    <div class="gift-card-box text-center">
                        <h4 class="mb_12">تسجيل الدخول مطلوب</h4>
                        <p class="text-muted mb_24">
                            لطلب بطاقة هدية، يرجى تسجيل الدخول أو إنشاء حساب حتى يتم ربط الطلب بحسابك وإرساله إلى لوحة الإدارة.
                        </p>
                        <div class="d-flex flex-wrap justify-content-center gap-3">
                            <a href="#login" data-bs-toggle="modal" class="tf-btn btn-fill animate-hover-btn radius-3">تسجيل الدخول</a>
                            <a href="#register" data-bs-toggle="modal" class="tf-btn btn-outline animate-hover-btn radius-3">إنشاء حساب</a>
                        </div>
                    </div>
                @else
                    <form method="POST" action="{{ route('front.gift-cards.store') }}">
                        @csrf

                        <div class="row g-4 align-items-start">
                            <div class="col-lg-8">
                                <div class="gift-card-box mb_24">
                                    <h5 class="gift-card-section-title">بيانات البطاقة</h5>

                                    <div class="alert alert-light border mb_20">
                                        سيتم ربط الطلب بالحساب:
                                        <strong>{{ $customer->name }}</strong>
                                        <span class="ms-2" dir="ltr">{{ $customer->account_no }}</span>
                                    </div>

                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="gift-requester-name" class="gift-card-label gift-card-required">اسم طالب البطاقة</label>
                                            <input id="gift-requester-name" type="text" name="requester_name" value="{{ old('requester_name', $customer->name) }}" class="form-control @error('requester_name') is-invalid @enderror" required>
                                            @error('requester_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>

                                        <div class="col-md-6">
                                            <label for="gift-recipient-name" class="gift-card-label">اسم المستفيد</label>
                                            <input id="gift-recipient-name" type="text" name="recipient_name" value="{{ old('recipient_name') }}" class="form-control @error('recipient_name') is-invalid @enderror">
                                            @error('recipient_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>

                                        <div class="col-12">
                                            <div class="gift-card-label gift-card-required">الاسم الظاهر على البطاقة</div>
                                            <div class="d-grid gap-3">
                                                <label class="gift-card-option">
                                                    <input type="radio" name="display_name_type" value="requester" @checked(old('display_name_type', 'requester') === 'requester') required>
                                                    <span class="fw-6">اسم طالب البطاقة</span>
                                                </label>
                                                <label class="gift-card-option">
                                                    <input type="radio" name="display_name_type" value="recipient" @checked(old('display_name_type', 'requester') === 'recipient') required>
                                                    <span class="fw-6">اسم المستفيد</span>
                                                </label>
                                                <label class="gift-card-option">
                                                    <input type="radio" name="display_name_type" value="anonymous" @checked(old('display_name_type', 'requester') === 'anonymous') required>
                                                    <span class="fw-6">بدون اسم / مجهول</span>
                                                </label>
                                            </div>
                                            @error('display_name_type')<div class="text-danger mt-2">{{ $message }}</div>@enderror
                                        </div>

                                        <div class="col-md-4">
                                            <label for="gift-card-quantity" class="gift-card-label gift-card-required">عدد البطاقات</label>
                                            <input id="gift-card-quantity" type="number" name="card_quantity" min="1" max="50" step="1" value="{{ old('card_quantity', 1) }}" class="form-control @error('card_quantity') is-invalid @enderror" required>
                                            @error('card_quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>

                                        <div class="col-md-4">
                                            <label for="gift-card-amount" class="gift-card-label gift-card-required">قيمة البطاقة</label>
                                            <input id="gift-card-amount" type="number" name="card_amount" min="1" step="1" value="{{ old('card_amount', 100000) }}" class="form-control @error('card_amount') is-invalid @enderror" required>
                                            @error('card_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>

                                        <div class="col-md-4">
                                            <label for="gift-card-currency" class="gift-card-label gift-card-required">العملة</label>
                                            <select id="gift-card-currency" name="currency" class="form-select @error('currency') is-invalid @enderror" required>
                                                @foreach ($currencyCodes as $code)
                                                    <option value="{{ $code }}" @selected(old('currency', 'SYP') === $code)>{{ $code }}</option>
                                                @endforeach
                                            </select>
                                            @error('currency')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>

                                        <div class="col-md-6">
                                            <label for="gift-recipient-mobile" class="gift-card-label">جوال المستفيد</label>
                                            <input id="gift-recipient-mobile" type="tel" name="recipient_mobile" value="{{ old('recipient_mobile') }}" class="form-control @error('recipient_mobile') is-invalid @enderror" dir="ltr">
                                            @error('recipient_mobile')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>

                                        <div class="col-md-6">
                                            <label for="gift-redemption-branch" class="gift-card-label gift-card-required">فرع استخدام البطاقة</label>
                                            <select id="gift-redemption-branch" name="redemption_branch_id" class="form-select @error('redemption_branch_id') is-invalid @enderror" required>
                                                <option value="">اختر الفرع</option>
                                                @foreach ($branches as $branch)
                                                    <option value="{{ $branch->getKey() }}" @selected((string) old('redemption_branch_id') === (string) $branch->getKey())>{{ $recordLabel($branch) }}</option>
                                                @endforeach
                                            </select>
                                            @error('redemption_branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="gift-card-box mb_24">
                                    <h5 class="gift-card-section-title">طريقة الاستلام</h5>
                                    <div class="d-grid gap-3">
                                        <label class="gift-card-option">
                                            <input type="radio" name="fulfillment_method" value="branch_pickup" @checked(old('fulfillment_method', 'branch_pickup') === 'branch_pickup') required>
                                            <span class="flex-grow-1">
                                                <span class="d-block fw-6">استلام من الفرع</span>
                                                <select name="pickup_branch_id" class="form-select mt-2 @error('pickup_branch_id') is-invalid @enderror">
                                                    <option value="">اختر الفرع</option>
                                                    @foreach ($branches as $branch)
                                                        <option value="{{ $branch->getKey() }}" @selected((string) old('pickup_branch_id') === (string) $branch->getKey())>{{ $recordLabel($branch) }}</option>
                                                    @endforeach
                                                </select>
                                            </span>
                                        </label>
                                        @error('pickup_branch_id')<div class="text-danger">{{ $message }}</div>@enderror

                                        <label class="gift-card-option">
                                            <input type="radio" name="fulfillment_method" value="delivery" @checked(old('fulfillment_method') === 'delivery') required>
                                            <span class="flex-grow-1">
                                                <span class="d-block fw-6">توصيل</span>
                                                <select name="shipping_method_id" class="form-select mt-2 @error('shipping_method_id') is-invalid @enderror">
                                                    <option value="">اختر طريقة التوصيل</option>
                                                    @foreach ($shippingMethods as $method)
                                                        <option value="{{ $method->getKey() }}" @selected((string) old('shipping_method_id') === (string) $method->getKey())>
                                                            {{ $recordLabel($method) }} - {{ number_format($shippingCost($method), 0) }} SYP
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <textarea name="delivery_address" rows="3" class="form-control mt-2 @error('delivery_address') is-invalid @enderror" placeholder="عنوان التوصيل الكامل">{{ old('delivery_address') }}</textarea>
                                            </span>
                                        </label>
                                        @error('shipping_method_id')<div class="text-danger">{{ $message }}</div>@enderror
                                        @error('delivery_address')<div class="text-danger">{{ $message }}</div>@enderror
                                    </div>
                                </div>

                                <div class="gift-card-box mb_24">
                                    <h5 class="gift-card-section-title">طريقة الدفع والملاحظات</h5>
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label for="gift-payment-method" class="gift-card-label gift-card-required">طريقة الدفع</label>
                                            <select id="gift-payment-method" name="payment_method_id" class="form-select @error('payment_method_id') is-invalid @enderror" required>
                                                <option value="">اختر طريقة الدفع</option>
                                                @foreach ($paymentMethods as $method)
                                                    <option value="{{ $method->getKey() }}" @selected((string) old('payment_method_id') === (string) $method->getKey())>{{ $recordLabel($method) }}</option>
                                                @endforeach
                                            </select>
                                            @error('payment_method_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>

                                        <div class="col-12">
                                            <label for="gift-card-notes" class="gift-card-label">ملاحظات</label>
                                            <textarea id="gift-card-notes" name="customer_notes" rows="4" class="form-control @error('customer_notes') is-invalid @enderror" placeholder="أي ملاحظات إضافية حول الطلب">{{ old('customer_notes') }}</textarea>
                                            @error('customer_notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <div class="gift-card-box">
                                    <h5 class="gift-card-section-title">تعليمات بطاقة الهدية</h5>
                                    <ul class="gift-card-summary-list text-muted">
                                        <li>سيتم إرسال الطلب إلى لوحة الإدارة للمراجعة والمعالجة.</li>
                                        <li>لا تصبح البطاقة فعالة إلا بعد تأكيد الدفع وإصدارها من الإدارة.</li>
                                        <li>يمكن استخدام البطاقة في الفرع المحدد حسب شروط الاستخدام.</li>
                                        <li>سيتم احتساب رسوم التوصيل تلقائيًا عند اختيار التوصيل.</li>
                                    </ul>

                                    <div class="form-check mt_24 mb_20">
                                        <input class="form-check-input @error('terms') is-invalid @enderror" type="checkbox" name="terms" value="1" id="gift-card-terms" @checked(old('terms')) required>
                                        <label class="form-check-label" for="gift-card-terms">أوافق على تعليمات وشروط بطاقة الهدية</label>
                                        @error('terms')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>

                                    <button type="submit" class="tf-btn btn-fill animate-hover-btn radius-3 w-100 justify-content-center">
                                        إرسال طلب بطاقة الهدية
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                @endunless
            </div>
        </section>
    </main>

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
@endsection