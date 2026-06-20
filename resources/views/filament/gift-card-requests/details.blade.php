@php
    $money = static fn ($value, $currency): string => number_format((float) $value, 2, '.', ',') . ' ' . strtoupper((string) ($currency ?: 'SYP'));
    $empty = '—';

    $statusLabel = \App\Models\GiftCardRequest::statusOptions()[$request->status] ?? $request->status;
    $paymentStatusLabel = \App\Models\GiftCardRequest::paymentStatusOptions()[$request->payment_status] ?? $request->payment_status;
    $displayNameTypeLabel = \App\Models\GiftCardRequest::displayNameTypeOptions()[$request->display_name_type] ?? $request->display_name_type;
    $fulfillmentLabel = \App\Models\GiftCardRequest::fulfillmentMethodOptions()[$request->fulfillment_method] ?? $request->fulfillment_method;

    $item = static function (string $label, mixed $value, bool $ltr = false) use ($empty): string {
        $value = filled($value) ? e((string) $value) : $empty;
        $dir = $ltr ? ' dir="ltr"' : '';

        return <<<HTML
            <div class="gc-info-item">
                <div class="gc-info-label">{$label}</div>
                <div class="gc-info-value"{$dir}>{$value}</div>
            </div>
        HTML;
    };
@endphp

<style>
    .gc-request-details {
        direction: rtl;
        text-align: right;
        display: grid;
        gap: 16px;
        color: #111827;
    }

    .gc-request-details * {
        box-sizing: border-box;
    }

    .gc-hero,
    .gc-section {
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        background: #ffffff;
        overflow: hidden;
    }

    .gc-hero {
        padding: 16px;
        background: #fafafa;
    }

    .gc-hero-row {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 14px;
        flex-wrap: wrap;
    }

    .gc-hero-title {
        margin: 0 0 6px;
        font-size: 18px;
        font-weight: 800;
        color: #111827;
    }

    .gc-hero-number {
        direction: ltr;
        text-align: left;
        display: inline-block;
        font-size: 15px;
        font-weight: 800;
        color: #111827;
    }

    .gc-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .gc-badge {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        border: 1px solid #fed7aa;
        background: #fff7ed;
        color: #9a3412;
        padding: 6px 12px;
        font-size: 12px;
        font-weight: 800;
        white-space: nowrap;
    }

    .gc-badge.payment {
        border-color: #bfdbfe;
        background: #eff6ff;
        color: #1d4ed8;
    }

    .gc-section-title {
        padding: 13px 16px;
        border-bottom: 1px solid #e5e7eb;
        background: #f9fafb;
        font-size: 15px;
        font-weight: 800;
        color: #111827;
    }

    .gc-section-body {
        padding: 16px;
    }

    .gc-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
    }

    .gc-grid.three {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .gc-info-item {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        background: #ffffff;
        padding: 10px 12px;
        min-height: 68px;
    }

    .gc-info-label {
        display: block;
        margin-bottom: 5px;
        color: #6b7280;
        font-size: 12px;
        font-weight: 700;
        line-height: 1.5;
    }

    .gc-info-value {
        display: block;
        color: #111827;
        font-size: 14px;
        font-weight: 800;
        line-height: 1.7;
        overflow-wrap: anywhere;
    }

    .gc-info-value[dir="ltr"] {
        direction: ltr;
        text-align: left;
        unicode-bidi: plaintext;
    }

    .gc-address-item,
    .gc-note-item {
        grid-column: 1 / -1;
    }

    .gc-total {
        margin-top: 12px;
        border-radius: 12px;
        background: #111827;
        color: #ffffff;
        padding: 14px 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
    }

    .gc-total-label {
        color: #d1d5db;
        font-size: 13px;
        font-weight: 700;
    }

    .gc-total-value {
        direction: ltr;
        text-align: left;
        font-size: 18px;
        font-weight: 900;
        color: #ffffff;
        white-space: nowrap;
    }

    .gc-notes-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
    }

    .gc-note-text {
        white-space: pre-line;
        min-height: 48px;
        font-weight: 600;
    }

    .gc-issued-wrap {
        overflow-x: auto;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
    }

    .gc-issued-table {
        width: 100%;
        min-width: 720px;
        border-collapse: collapse;
        font-size: 13px;
    }

    .gc-issued-table th,
    .gc-issued-table td {
        padding: 11px 12px;
        border-bottom: 1px solid #e5e7eb;
        text-align: right;
        vertical-align: middle;
    }

    .gc-issued-table th {
        background: #f9fafb;
        color: #374151;
        font-weight: 900;
    }

    .gc-issued-table tr:last-child td {
        border-bottom: 0;
    }

    @media (max-width: 900px) {
        .gc-grid,
        .gc-grid.three,
        .gc-notes-grid {
            grid-template-columns: 1fr;
        }

        .gc-total {
            display: block;
        }

        .gc-total-value {
            display: block;
            margin-top: 6px;
        }
    }
</style>

<div class="gc-request-details">
    <div class="gc-hero">
        <div class="gc-hero-row">
            <div>
                <h3 class="gc-hero-title">طلب بطاقة هدية</h3>
                <span class="gc-hero-number">{{ $request->request_no }}</span>
            </div>

            <div class="gc-badges">
                <span class="gc-badge">{{ $statusLabel }}</span>
                <span class="gc-badge payment">{{ $paymentStatusLabel }}</span>
            </div>
        </div>

        <div class="gc-section-body" style="padding: 14px 0 0;">
            <div class="gc-grid three">
                {!! $item('تاريخ الطلب', $request->submitted_at?->format('Y-m-d H:i')) !!}
                {!! $item('رقم الطلب', $request->request_no, true) !!}
                {!! $item('عدد البطاقات المطلوبة', $request->card_quantity) !!}
            </div>
        </div>
    </div>

    <div class="gc-section">
        <div class="gc-section-title">بيانات الزبون</div>
        <div class="gc-section-body">
            <div class="gc-grid three">
                {!! $item('الزبون', $request->customer?->name) !!}
                {!! $item('رقم الحساب', $request->customer?->account_no, true) !!}
                {!! $item('الموبايل', $request->customer?->mobile, true) !!}
            </div>
        </div>
    </div>

    <div class="gc-section">
        <div class="gc-section-title">بيانات البطاقات المطلوبة</div>
        <div class="gc-section-body">
            <div class="gc-grid">
                {!! $item('الاسم المطلوب على البطاقة', $displayNameTypeLabel) !!}
                {!! $item('الاسم الظاهر', $request->display_name ?: 'مجهول') !!}
                {!! $item('اسم طالب البطاقة', $request->requester_name) !!}
                {!! $item('اسم المستفيد', $request->recipient_name) !!}
                {!! $item('موبايل المستفيد', $request->recipient_mobile, true) !!}
                {!! $item('عدد البطاقات', $request->card_quantity) !!}
                {!! $item('قيمة البطاقة الواحدة', $money($request->card_amount, $request->currency), true) !!}
                {!! $item('قيمة البطاقات', $money($request->cards_subtotal, $request->currency), true) !!}
            </div>
        </div>
    </div>

    <div class="gc-section">
        <div class="gc-section-title">الاستلام والدفع والصرف</div>
        <div class="gc-section-body">
            <div class="gc-grid">
                {!! $item('طريقة الاستلام', $fulfillmentLabel) !!}
                {!! $item('فرع الاستلام', $request->pickupBranch?->name_ar) !!}
                {!! $item('طريقة التوصيل', $request->shippingMethod?->name_ar) !!}
                {!! $item('رسوم التوصيل', $money($request->delivery_fee, $request->currency), true) !!}
                {!! $item('طريقة الدفع', $request->paymentMethod?->name_ar) !!}
                {!! $item('فرع صرف البطاقة', $request->redemptionBranch?->name_ar) !!}
                <div class="gc-address-item">
                    {!! $item('عنوان التوصيل', $request->delivery_address) !!}
                </div>
            </div>

            <div class="gc-total">
                <span class="gc-total-label">إجمالي الطلب</span>
                <strong class="gc-total-value">{{ $money($request->total_amount, $request->currency) }}</strong>
            </div>
        </div>
    </div>

    <div class="gc-section">
        <div class="gc-section-title">الملاحظات</div>
        <div class="gc-section-body">
            <div class="gc-notes-grid">
                <div class="gc-info-item">
                    <div class="gc-info-label">ملاحظات الزبون</div>
                    <div class="gc-info-value gc-note-text">{{ $request->customer_notes ?: $empty }}</div>
                </div>
                <div class="gc-info-item">
                    <div class="gc-info-label">ملاحظات الإدارة</div>
                    <div class="gc-info-value gc-note-text">{{ $request->admin_notes ?: $empty }}</div>
                </div>
            </div>
        </div>
    </div>

    @if ($request->giftCards->isNotEmpty())
        <div class="gc-section">
            <div class="gc-section-title">البطاقات الصادرة</div>
            <div class="gc-section-body">
                <div class="gc-issued-wrap">
                    <table class="gc-issued-table">
                        <thead>
                            <tr>
                                <th>الرمز</th>
                                <th>القيمة</th>
                                <th>الرصيد</th>
                                <th>الحالة</th>
                                <th>تاريخ الانتهاء</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($request->giftCards as $card)
                                <tr>
                                    <td dir="ltr">{{ $card->code }}</td>
                                    <td dir="ltr">{{ $money($card->amount, $card->currency) }}</td>
                                    <td dir="ltr">{{ $money($card->balance, $card->currency) }}</td>
                                    <td>{{ $card->status }}</td>
                                    <td>{{ $card->expires_at?->format('Y-m-d H:i') ?: $empty }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>
