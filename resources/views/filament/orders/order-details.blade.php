@php
    $statusLabels ??= [
        'pending' => 'قيد المراجعة',
        'confirmed' => 'مؤكد',
        'shipped' => 'مُشحن',
        'delivered' => 'مُسلم',
        'cancelled' => 'ملغى',
    ];

    $paymentStatusLabels ??= [
        'unpaid' => 'غير مدفوع',
        'paid' => 'مدفوع',
    ];

    $money = static fn ($value): string => 'SYP ' . number_format((float) $value, 0, '.', ',');
    $discountTotal = (float) $order->discount_value + (float) $order->coupon_discount_value;
@endphp

<style>
    .order-admin-details {
        --oad-bg: #ffffff;
        --oad-soft: #f8fafc;
        --oad-border: #e5e7eb;
        --oad-text: #111827;
        --oad-muted: #6b7280;
        --oad-primary: #d97706;
        color: var(--oad-text);
        direction: rtl;
        font-size: 14px;
        line-height: 1.65;
    }

    .dark .order-admin-details {
        --oad-bg: #111827;
        --oad-soft: rgba(255, 255, 255, .045);
        --oad-border: rgba(255, 255, 255, .12);
        --oad-text: #f9fafb;
        --oad-muted: #9ca3af;
    }

    .oad-summary-grid,
    .oad-two-columns {
        display: grid;
        gap: 14px;
    }

    .oad-summary-grid {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    .oad-two-columns {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .oad-card,
    .oad-section {
        background: var(--oad-bg);
        border: 1px solid var(--oad-border);
        border-radius: 14px;
        overflow: hidden;
    }

    .oad-card {
        padding: 15px 16px;
    }

    .oad-card-label,
    .oad-label {
        color: var(--oad-muted);
        font-size: 12px;
    }

    .oad-card-value {
        margin-top: 5px;
        font-size: 14px;
        font-weight: 700;
        overflow-wrap: anywhere;
    }

    .oad-section {
        margin-top: 16px;
    }

    .oad-section-header {
        align-items: center;
        background: var(--oad-soft);
        border-bottom: 1px solid var(--oad-border);
        display: flex;
        justify-content: space-between;
        padding: 13px 16px;
    }

    .oad-section-title {
        font-size: 15px;
        font-weight: 800;
        margin: 0;
    }

    .oad-section-body {
        padding: 16px;
    }

    .oad-data-list {
        display: grid;
        gap: 0;
        margin: 0;
    }

    .oad-data-row {
        align-items: start;
        border-bottom: 1px dashed var(--oad-border);
        display: grid;
        gap: 16px;
        grid-template-columns: 145px minmax(0, 1fr);
        padding: 10px 0;
    }

    .oad-data-row:first-child {
        padding-top: 0;
    }

    .oad-data-row:last-child {
        border-bottom: 0;
        padding-bottom: 0;
    }

    .oad-value {
        font-weight: 650;
        min-width: 0;
        overflow-wrap: anywhere;
    }

    .oad-chip {
        align-items: center;
        border-radius: 999px;
        display: inline-flex;
        font-size: 12px;
        font-weight: 700;
        gap: 6px;
        padding: 5px 10px;
        white-space: nowrap;
    }

    .oad-chip--pending { background: #fef3c7; color: #92400e; }
    .oad-chip--confirmed { background: #dbeafe; color: #1e40af; }
    .oad-chip--shipped { background: #ede9fe; color: #5b21b6; }
    .oad-chip--delivered,
    .oad-chip--paid { background: #dcfce7; color: #166534; }
    .oad-chip--cancelled,
    .oad-chip--unpaid { background: #fee2e2; color: #991b1b; }
    .oad-chip--neutral { background: #f3f4f6; color: #374151; }

    .dark .oad-chip--pending { background: rgba(245, 158, 11, .18); color: #fcd34d; }
    .dark .oad-chip--confirmed { background: rgba(59, 130, 246, .18); color: #93c5fd; }
    .dark .oad-chip--shipped { background: rgba(139, 92, 246, .18); color: #c4b5fd; }
    .dark .oad-chip--delivered,
    .dark .oad-chip--paid { background: rgba(34, 197, 94, .18); color: #86efac; }
    .dark .oad-chip--cancelled,
    .dark .oad-chip--unpaid { background: rgba(239, 68, 68, .18); color: #fca5a5; }
    .dark .oad-chip--neutral { background: rgba(255, 255, 255, .09); color: #d1d5db; }

    .oad-table-wrap {
        overflow-x: auto;
    }

    .oad-table {
        border-collapse: collapse;
        min-width: 820px;
        width: 100%;
    }

    .oad-table th,
    .oad-table td {
        border-bottom: 1px solid var(--oad-border);
        padding: 12px 14px;
        text-align: right;
        vertical-align: middle;
    }

    .oad-table th {
        background: var(--oad-soft);
        color: var(--oad-muted);
        font-size: 12px;
        font-weight: 750;
    }

    .oad-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .oad-product-title {
        font-weight: 750;
    }

    .oad-product-meta {
        color: var(--oad-muted);
        font-size: 11px;
        margin-top: 3px;
    }

    .oad-totals {
        margin-inline-start: auto;
        max-width: 460px;
    }

    .oad-total-row {
        align-items: center;
        border-bottom: 1px dashed var(--oad-border);
        display: flex;
        gap: 20px;
        justify-content: space-between;
        padding: 9px 0;
    }

    .oad-total-row:last-child {
        border-bottom: 0;
    }

    .oad-total-row--final {
        font-size: 16px;
        font-weight: 900;
        padding-top: 13px;
    }

    .oad-note {
        background: var(--oad-soft);
        border: 1px solid var(--oad-border);
        border-radius: 10px;
        color: var(--oad-text);
        margin: 0;
        min-height: 52px;
        padding: 12px;
        white-space: pre-line;
    }

    .oad-ltr {
        direction: ltr;
        display: inline-block;
        text-align: left;
        unicode-bidi: isolate;
    }

    @media (max-width: 1024px) {
        .oad-summary-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 700px) {
        .oad-summary-grid,
        .oad-two-columns {
            grid-template-columns: minmax(0, 1fr);
        }

        .oad-data-row {
            gap: 4px;
            grid-template-columns: minmax(0, 1fr);
        }
    }
</style>

<div class="order-admin-details">
    <div class="oad-summary-grid">
        <div class="oad-card">
            <div class="oad-card-label">رقم الطلب</div>
            <div class="oad-card-value"><span class="oad-ltr">{{ $order->order_no }}</span></div>
        </div>

        <div class="oad-card">
            <div class="oad-card-label">حالة الطلب</div>
            <div class="oad-card-value">
                <span class="oad-chip oad-chip--{{ $order->status }}">
                    {{ $statusLabels[$order->status] ?? $order->status }}
                </span>
            </div>
        </div>

        <div class="oad-card">
            <div class="oad-card-label">حالة الدفع</div>
            <div class="oad-card-value">
                <span class="oad-chip oad-chip--{{ $order->payment_status }}">
                    {{ $paymentStatusLabels[$order->payment_status] ?? $order->payment_status }}
                </span>
            </div>
        </div>

        <div class="oad-card">
            <div class="oad-card-label">تاريخ الطلب</div>
            <div class="oad-card-value"><span class="oad-ltr">{{ optional($order->created_at)->format('Y-m-d H:i') ?: '—' }}</span></div>
        </div>
    </div>

    <div class="oad-two-columns">
        <section class="oad-section">
            <div class="oad-section-header">
                <h3 class="oad-section-title">بيانات الزبون</h3>
            </div>
            <div class="oad-section-body">
                <dl class="oad-data-list">
                    <div class="oad-data-row">
                        <dt class="oad-label">الاسم الكامل</dt>
                        <dd class="oad-value">{{ $order->customer_name_snapshot ?: $order->customer?->name ?: '—' }}</dd>
                    </div>
                    <div class="oad-data-row">
                        <dt class="oad-label">رقم الموبايل</dt>
                        <dd class="oad-value"><span class="oad-ltr">{{ $order->customer_mobile_snapshot ?: $order->customer?->mobile ?: '—' }}</span></dd>
                    </div>
                    <div class="oad-data-row">
                        <dt class="oad-label">البريد الإلكتروني</dt>
                        <dd class="oad-value"><span class="oad-ltr">{{ $order->customer_email_snapshot ?: $order->customer?->email ?: '—' }}</span></dd>
                    </div>
                    <div class="oad-data-row">
                        <dt class="oad-label">رقم الحساب</dt>
                        <dd class="oad-value"><span class="oad-ltr">{{ $order->customer_account_no_snapshot ?: $order->customer?->account_no ?: '—' }}</span></dd>
                    </div>
                </dl>
            </div>
        </section>

        <section class="oad-section">
            <div class="oad-section-header">
                <h3 class="oad-section-title">بيانات التوصيل والدفع</h3>
            </div>
            <div class="oad-section-body">
                <dl class="oad-data-list">
                    <div class="oad-data-row">
                        <dt class="oad-label">اسم المستلم</dt>
                        <dd class="oad-value">{{ $order->shipping_contact_name_snapshot ?: '—' }}</dd>
                    </div>
                    <div class="oad-data-row">
                        <dt class="oad-label">موبايل المستلم</dt>
                        <dd class="oad-value"><span class="oad-ltr">{{ $order->shipping_mobile_snapshot ?: '—' }}</span></dd>
                    </div>
                    <div class="oad-data-row">
                        <dt class="oad-label">المدينة والمنطقة</dt>
                        <dd class="oad-value">{{ collect([$order->shipping_city_snapshot, $order->shipping_area_snapshot])->filter()->implode(' - ') ?: '—' }}</dd>
                    </div>
                    <div class="oad-data-row">
                        <dt class="oad-label">العنوان التفصيلي</dt>
                        <dd class="oad-value">{{ $order->shipping_address_line_snapshot ?: '—' }}</dd>
                    </div>
                    <div class="oad-data-row">
                        <dt class="oad-label">طريقة الشحن</dt>
                        <dd class="oad-value">{{ $order->shippingMethod?->name_ar ?: $order->shipping_label_snapshot ?: '—' }}</dd>
                    </div>
                    <div class="oad-data-row">
                        <dt class="oad-label">طريقة الدفع</dt>
                        <dd class="oad-value">{{ $paymentMethodLabel ?: '—' }}</dd>
                    </div>
                </dl>
            </div>
        </section>
    </div>

    <section class="oad-section">
        <div class="oad-section-header">
            <h3 class="oad-section-title">عناصر الطلب</h3>
            <span class="oad-chip oad-chip--neutral">{{ $order->items->count() }} عنصر</span>
        </div>

        <div class="oad-table-wrap">
            <table class="oad-table">
                <thead>
                    <tr>
                        <th>المنتج</th>
                        <th>اللون</th>
                        <th>المقاس</th>
                        <th>الكمية</th>
                        <th>سعر الوحدة</th>
                        <th>الإجمالي</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($order->items as $item)
                        <tr>
                            <td>
                                <div class="oad-product-title">{{ $item->product_name_snapshot ?: '—' }}</div>
                                @if (filled($item->product_model_no_snapshot) || filled($item->product_sku_snapshot) || filled($item->product_barcode_snapshot))
                                    <div class="oad-product-meta oad-ltr">
                                        {{ collect([$item->product_model_no_snapshot, $item->product_sku_snapshot, $item->product_barcode_snapshot])->filter()->implode(' / ') }}
                                    </div>
                                @endif
                            </td>
                            <td>{{ $item->color_name_snapshot ?: '—' }}</td>
                            <td>{{ $item->size_name_snapshot ?: '—' }}</td>
                            <td><span class="oad-ltr">{{ $item->quantity }}</span></td>
                            <td><span class="oad-ltr">{{ $money($item->unit_price) }}</span></td>
                            <td><strong class="oad-ltr">{{ $money($item->line_total) }}</strong></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding: 28px; text-align: center; color: var(--oad-muted);">
                                لا توجد عناصر في هذا الطلب.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="oad-two-columns">
        <section class="oad-section">
            <div class="oad-section-header">
                <h3 class="oad-section-title">الملاحظات</h3>
            </div>
            <div class="oad-section-body">
                <p class="oad-note">{{ $order->notes ?: 'لا توجد ملاحظات على الطلب.' }}</p>

                @if ($order->rating)
                    <div class="oad-section" data-admin-order-rating>
                        <h3 class="oad-section-title">{!! '&#1578;&#1602;&#1610;&#1610;&#1605; &#1575;&#1604;&#1593;&#1605;&#1610;&#1604;' !!}</h3>
                        <div class="oad-card">
                            <div style="color:#f5a623; font-size:22px; margin-bottom:8px;">
                                @for ($i = 1; $i <= 5; $i++)
                                    {!! $i <= (int) $order->rating->rating ? '&#9733;' : '&#9734;' !!}
                                @endfor
                            </div>
                            @if ($order->rating->comment)
                                <p class="oad-note">{{ $order->rating->comment }}</p>
                            @endif
                            <small>{{ optional($order->rating->created_at)->format('Y-m-d H:i') }}</small>
                        </div>
                    </div>
                @endif
                @if ($order->is_gift)
                    <div style="margin-top: 12px;">
                        <div class="oad-label" style="margin-bottom: 5px;">رسالة الهدية</div>
                        <p class="oad-note">{{ $order->gift_message ?: 'لم تتم إضافة رسالة.' }}</p>
                    </div>
                @endif
            </div>
        </section>

        <section class="oad-section">
            <div class="oad-section-header">
                <h3 class="oad-section-title">ملخص المبالغ</h3>
            </div>
            <div class="oad-section-body">
                <div class="oad-totals">
                    <div class="oad-total-row">
                        <span class="oad-label">المجموع قبل الحسم</span>
                        <strong class="oad-ltr">{{ $money($order->total_before_discount) }}</strong>
                    </div>
                    <div class="oad-total-row">
                        <span class="oad-label">إجمالي الحسومات</span>
                        <strong class="oad-ltr">{{ $money($discountTotal) }}</strong>
                    </div>
                    <div class="oad-total-row">
                        <span class="oad-label">تكلفة الشحن</span>
                        <strong class="oad-ltr">{{ $money($order->shipping_cost) }}</strong>
                    </div>
                    <div class="oad-total-row oad-total-row--final">
                        <span>الإجمالي النهائي</span>
                        <span class="oad-ltr">{{ $money($order->total) }}</span>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
