@extends('frontend.pages.account.base')

@php
    $request = $gift_card_request;
    $empty = '—';
    $money = static fn ($value, $currency): string => number_format((float) $value, 0) . ' ' . strtoupper((string) ($currency ?: 'SYP'));
    $statusLabel = \App\Models\GiftCardRequest::statusOptions()[$request->status] ?? $request->status;
    $paymentStatusLabel = \App\Models\GiftCardRequest::paymentStatusOptions()[$request->payment_status] ?? $request->payment_status;
    $fulfillmentLabel = \App\Models\GiftCardRequest::fulfillmentMethodOptions()[$request->fulfillment_method] ?? $request->fulfillment_method;
    $displayNameTypeLabel = \App\Models\GiftCardRequest::displayNameTypeOptions()[$request->display_name_type] ?? $request->display_name_type;

    $recordLabel = function ($item) use ($empty) {
        if (! $item) {
            return $empty;
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

    $field = static function (string $label, mixed $value, bool $ltr = false) use ($empty): string {
        $value = filled($value) ? e((string) $value) : $empty;
        $dir = $ltr ? ' dir="ltr"' : '';

        return <<<HTML
            <div class="gc-account-field">
                <span>{$label}</span>
                <strong{$dir}>{$value}</strong>
            </div>
        HTML;
    };
@endphp

@section('account_content')
    <style>
        .gc-account-detail {
            direction: rtl;
            text-align: right;
        }

        .gc-account-detail * {
            box-sizing: border-box;
        }

        .gc-account-hero {
            border: 1px solid #e7e7e7;
            border-radius: 18px;
            background: linear-gradient(135deg, #f8f8f8 0%, #ffffff 70%);
            padding: 24px;
            margin-bottom: 24px;
        }

        .gc-account-hero-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 18px;
            margin-bottom: 22px;
        }

        .gc-account-title h4 {
            margin: 0 0 8px;
            font-size: 28px;
            line-height: 1.35;
            font-weight: 700;
            color: #111;
        }

        .gc-account-title p {
            margin: 0;
            color: #666;
            font-size: 14px;
            direction: ltr;
            text-align: right;
        }

        .gc-account-actions {
            flex: 0 0 auto;
        }

        .gc-status-row {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
        }

        .gc-status-card {
            background: #fff;
            border: 1px solid #ececec;
            border-radius: 14px;
            padding: 16px 18px;
            min-height: 94px;
        }

        .gc-status-card span {
            display: block;
            font-size: 13px;
            color: #777;
            margin-bottom: 8px;
        }

        .gc-status-card strong {
            display: block;
            font-size: 16px;
            color: #111;
            font-weight: 700;
            line-height: 1.6;
        }

        .gc-account-section {
            border: 1px solid #e7e7e7;
            border-radius: 18px;
            background: #fff;
            margin-bottom: 20px;
            overflow: hidden;
        }

        .gc-account-section-title {
            padding: 16px 20px;
            border-bottom: 1px solid #e7e7e7;
            background: #fafafa;
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: center;
        }

        .gc-account-section-title h5 {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
            color: #111;
        }

        .gc-account-section-title small {
            color: #777;
            font-size: 13px;
        }

        .gc-account-section-body {
            padding: 20px;
        }

        .gc-account-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .gc-account-grid.three {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .gc-account-field {
            border: 1px solid #ededed;
            border-radius: 14px;
            padding: 14px 16px;
            background: #fff;
            min-height: 86px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            text-align: right;
        }

        .gc-account-field span {
            display: block;
            margin-bottom: 7px;
            color: #777;
            font-size: 13px;
            line-height: 1.5;
        }

        .gc-account-field strong {
            display: block;
            color: #111;
            font-size: 15px;
            line-height: 1.7;
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        .gc-account-field strong[dir="ltr"],
        .gc-ltr {
            direction: ltr;
            text-align: right;
            unicode-bidi: plaintext;
        }

        .gc-total-line {
            margin-top: 14px;
            border-radius: 16px;
            background: #111;
            color: #fff;
            padding: 17px 20px;
            display: flex;
            justify-content: space-between;
            gap: 15px;
            align-items: center;
        }

        .gc-total-line span {
            color: #d7d7d7;
            font-size: 14px;
        }

        .gc-total-line strong {
            font-size: 19px;
            font-weight: 800;
            color: #fff;
        }

        .gc-note-box {
            border: 1px solid #ededed;
            border-radius: 14px;
            padding: 16px;
            background: #fff;
            min-height: 90px;
            color: #333;
            line-height: 1.9;
            white-space: pre-line;
        }

        .gc-empty-issued {
            border: 1px dashed #d7d7d7;
            border-radius: 16px;
            background: #fafafa;
            padding: 18px 20px;
            color: #555;
            line-height: 1.8;
        }

        .gc-issued-table-wrap {
            width: 100%;
            overflow-x: auto;
            border: 1px solid #e7e7e7;
            border-radius: 14px;
        }

        .gc-issued-table {
            width: 100%;
            min-width: 720px;
            border-collapse: collapse;
        }

        .gc-issued-table th,
        .gc-issued-table td {
            padding: 13px 14px;
            border-bottom: 1px solid #ededed;
            text-align: right;
            vertical-align: middle;
            white-space: nowrap;
        }

        .gc-issued-table th {
            background: #fafafa;
            color: #333;
            font-size: 13px;
            font-weight: 700;
        }

        .gc-issued-table tr:last-child td {
            border-bottom: 0;
        }

        @media (max-width: 991px) {
            .gc-account-hero-head {
                display: block;
            }

            .gc-account-actions {
                margin-top: 16px;
            }

            .gc-status-row,
            .gc-account-grid,
            .gc-account-grid.three {
                grid-template-columns: 1fr;
            }

            .gc-account-title h4 {
                font-size: 24px;
            }

            .gc-total-line {
                display: block;
            }

            .gc-total-line strong {
                display: block;
                margin-top: 6px;
            }
        }
    </style>

    <div class="account-card gc-account-detail">
        <div class="gc-account-hero">
            <div class="gc-account-hero-head">
                <div class="gc-account-title">
                    <h4>تفاصيل طلب بطاقة الهدية</h4>
                    <p>{{ $request->request_no }}</p>
                </div>

                <div class="gc-account-actions">
                    <a href="{{ route('front.account.gift-card-requests.index') }}" class="tf-btn btn-outline animate-hover-btn radius-3">
                        العودة للطلبات
                    </a>
                </div>
            </div>

            <div class="gc-status-row">
                <div class="gc-status-card">
                    <span>حالة الطلب</span>
                    <strong>{{ $statusLabel }}</strong>
                </div>
                <div class="gc-status-card">
                    <span>حالة الدفع</span>
                    <strong>{{ $paymentStatusLabel }}</strong>
                </div>
                <div class="gc-status-card">
                    <span>الإجمالي</span>
                    <strong class="gc-ltr">{{ $money($request->total_amount, $request->currency) }}</strong>
                </div>
            </div>
        </div>

        <div class="gc-account-section">
            <div class="gc-account-section-title">
                <h5>بيانات البطاقة</h5>
                <small>معلومات الاسم والمستفيد والقيمة</small>
            </div>
            <div class="gc-account-section-body">
                <div class="gc-account-grid three">
                    {!! $field('الاسم الظاهر على البطاقة', $displayNameTypeLabel) !!}
                    {!! $field('الاسم المكتوب', $request->display_name ?: $empty) !!}
                    {!! $field('اسم طالب البطاقة', $request->requester_name) !!}
                    {!! $field('اسم المستفيد', $request->recipient_name) !!}
                    {!! $field('موبايل المستفيد', $request->recipient_mobile, true) !!}
                    {!! $field('عدد البطاقات', (int) $request->card_quantity) !!}
                    {!! $field('قيمة البطاقة الواحدة', $money($request->card_amount, $request->currency), true) !!}
                    {!! $field('قيمة البطاقات', $money($request->cards_subtotal, $request->currency), true) !!}
                    {!! $field('تاريخ الطلب', $request->submitted_at?->format('Y-m-d H:i'), true) !!}
                </div>
            </div>
        </div>

        <div class="gc-account-section">
            <div class="gc-account-section-title">
                <h5>الاستلام والدفع</h5>
                <small>الفروع وطريقة الدفع والتوصيل</small>
            </div>
            <div class="gc-account-section-body">
                <div class="gc-account-grid three">
                    {!! $field('طريقة الاستلام', $fulfillmentLabel) !!}
                    {!! $field('فرع الاستلام', $recordLabel($request->pickupBranch)) !!}
                    {!! $field('طريقة التوصيل', $recordLabel($request->shippingMethod)) !!}
                    {!! $field('رسوم التوصيل', $money($request->delivery_fee, $request->currency), true) !!}
                    {!! $field('طريقة الدفع', $recordLabel($request->paymentMethod)) !!}
                    {!! $field('فرع صرف البطاقة', $recordLabel($request->redemptionBranch)) !!}
                </div>

                <div style="margin-top: 14px;">
                    {!! $field('عنوان التوصيل', $request->delivery_address) !!}
                </div>

                <div class="gc-total-line">
                    <span>إجمالي الطلب بعد الرسوم</span>
                    <strong class="gc-ltr">{{ $money($request->total_amount, $request->currency) }}</strong>
                </div>
            </div>
        </div>

        <div class="gc-account-section">
            <div class="gc-account-section-title">
                <h5>الملاحظات</h5>
                <small>الملاحظات المدخلة أثناء إرسال الطلب</small>
            </div>
            <div class="gc-account-section-body">
                <div class="gc-note-box">{{ $request->customer_notes ?: $empty }}</div>
            </div>
        </div>

        @if ($request->giftCards->isNotEmpty())
            <div class="gc-account-section">
                <div class="gc-account-section-title">
                    <h5>البطاقات الصادرة</h5>
                    <small>تظهر بعد إصدار البطاقات من لوحة الإدارة</small>
                </div>
                <div class="gc-account-section-body">
                    <div class="gc-issued-table-wrap">
                        <table class="gc-issued-table">
                            <thead>
                                <tr>
                                    <th>الكود</th>
                                    <th>القيمة</th>
                                    <th>الرصيد</th>
                                    <th>الحالة</th>
                                    <th>تاريخ الإصدار</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($request->giftCards as $card)
                                    <tr>
                                        <td dir="ltr">{{ $card->code }}</td>
                                        <td dir="ltr">{{ $money($card->amount, $card->currency) }}</td>
                                        <td dir="ltr">{{ $money($card->balance, $card->currency) }}</td>
                                        <td>{{ $card->status }}</td>
                                        <td>{{ optional($card->issued_at)->format('Y-m-d') ?: $empty }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @else
            <div class="gc-empty-issued">
                لم يتم إصدار بطاقات لهذا الطلب بعد. ستظهر هنا بعد اعتماد الطلب وتثبيت الدفع وإصدار البطاقات من لوحة الإدارة.
            </div>
        @endif
    </div>
@endsection
