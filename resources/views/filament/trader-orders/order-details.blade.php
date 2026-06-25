@php
    $dash = '—';
    $money = static fn ($value): string => number_format((float) $value, 2, '.', ',');

    $statusLabels = [
        'pending' => 'قيد المراجعة',
        'confirmed' => 'مؤكد',
        'shipped' => 'مشحون',
        'delivered' => 'مسلم',
        'cancelled' => 'ملغى',
    ];

    $paymentLabels = [
        'paid' => 'مدفوع',
        'unpaid' => 'غير مدفوع',
    ];

    $statusClass = $order->status ?: 'neutral';
    $paymentClass = $order->payment_status ?: 'neutral';
    $items = $order->items ?? collect();
    $hasZeroPrice = ((float) $order->total <= 0)
        || $items->contains(fn ($item): bool => (float) $item->unit_price <= 0 || (float) $item->line_total <= 0);
@endphp

<style>
    .trader-order-details {
        --tod-bg: #ffffff;
        --tod-soft: #f8fafc;
        --tod-border: #e5e7eb;
        --tod-text: #111827;
        --tod-muted: #6b7280;
        --tod-accent: #0f766e;
        direction: rtl;
        color: var(--tod-text);
        font-size: 14px;
        line-height: 1.65;
    }

    .dark .trader-order-details {
        --tod-bg: #111827;
        --tod-soft: rgba(255, 255, 255, .05);
        --tod-border: rgba(255, 255, 255, .12);
        --tod-text: #f9fafb;
        --tod-muted: #9ca3af;
    }

    .tod-grid {
        display: grid;
        gap: 14px;
    }

    .tod-summary {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    .tod-two {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        margin-top: 16px;
    }

    .tod-card,
    .tod-section {
        background: var(--tod-bg);
        border: 1px solid var(--tod-border);
        border-radius: 12px;
    }

    .tod-card {
        padding: 14px 16px;
    }

    .tod-label {
        color: var(--tod-muted);
        font-size: 12px;
        font-weight: 650;
    }

    .tod-value {
        font-weight: 750;
        margin-top: 4px;
        overflow-wrap: anywhere;
    }

    .tod-section {
        margin-top: 16px;
        overflow: hidden;
    }

    .tod-section:first-child {
        margin-top: 0;
    }

    .tod-section-head {
        align-items: center;
        background: var(--tod-soft);
        border-bottom: 1px solid var(--tod-border);
        display: flex;
        gap: 12px;
        justify-content: space-between;
        padding: 13px 16px;
    }

    .tod-title {
        font-size: 15px;
        font-weight: 800;
        margin: 0;
    }

    .tod-body {
        padding: 16px;
    }

    .tod-list {
        display: grid;
        gap: 0;
        margin: 0;
    }

    .tod-row {
        border-bottom: 1px dashed var(--tod-border);
        display: grid;
        gap: 14px;
        grid-template-columns: 140px minmax(0, 1fr);
        padding: 10px 0;
    }

    .tod-row:first-child {
        padding-top: 0;
    }

    .tod-row:last-child {
        border-bottom: 0;
        padding-bottom: 0;
    }

    .tod-chip {
        align-items: center;
        border-radius: 999px;
        display: inline-flex;
        font-size: 12px;
        font-weight: 800;
        padding: 5px 10px;
        white-space: nowrap;
    }

    .tod-chip--pending,
    .tod-chip--unpaid { background: #fef3c7; color: #92400e; }
    .tod-chip--confirmed { background: #dbeafe; color: #1e40af; }
    .tod-chip--shipped { background: #ede9fe; color: #5b21b6; }
    .tod-chip--delivered,
    .tod-chip--paid { background: #dcfce7; color: #166534; }
    .tod-chip--cancelled,
    .tod-chip--danger { background: #fee2e2; color: #991b1b; }
    .tod-chip--neutral { background: #f3f4f6; color: #374151; }

    .dark .tod-chip--pending,
    .dark .tod-chip--unpaid { background: rgba(245, 158, 11, .18); color: #fcd34d; }
    .dark .tod-chip--confirmed { background: rgba(59, 130, 246, .18); color: #93c5fd; }
    .dark .tod-chip--shipped { background: rgba(139, 92, 246, .18); color: #c4b5fd; }
    .dark .tod-chip--delivered,
    .dark .tod-chip--paid { background: rgba(34, 197, 94, .18); color: #86efac; }
    .dark .tod-chip--cancelled,
    .dark .tod-chip--danger { background: rgba(239, 68, 68, .18); color: #fca5a5; }
    .dark .tod-chip--neutral { background: rgba(255, 255, 255, .09); color: #d1d5db; }

    .tod-alert {
        background: #fffbeb;
        border: 1px solid #fde68a;
        border-radius: 12px;
        color: #92400e;
        margin-bottom: 16px;
        padding: 12px 14px;
    }

    .dark .tod-alert {
        background: rgba(245, 158, 11, .12);
        border-color: rgba(245, 158, 11, .35);
        color: #fcd34d;
    }

    .tod-table-wrap {
        overflow-x: auto;
    }

    .tod-table {
        border-collapse: collapse;
        min-width: 920px;
        width: 100%;
    }

    .tod-table th,
    .tod-table td {
        border-bottom: 1px solid var(--tod-border);
        padding: 12px 14px;
        text-align: right;
        vertical-align: top;
    }

    .tod-table th {
        background: var(--tod-soft);
        color: var(--tod-muted);
        font-size: 12px;
        font-weight: 800;
    }

    .tod-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .tod-product {
        font-weight: 800;
    }

    .tod-meta {
        color: var(--tod-muted);
        font-size: 11px;
        margin-top: 3px;
    }

    .tod-totals {
        margin-inline-start: auto;
        max-width: 460px;
    }

    .tod-total-row {
        align-items: center;
        border-bottom: 1px dashed var(--tod-border);
        display: flex;
        gap: 18px;
        justify-content: space-between;
        padding: 9px 0;
    }

    .tod-total-row:last-child {
        border-bottom: 0;
    }

    .tod-total-final {
        color: var(--tod-accent);
        font-size: 18px;
        font-weight: 900;
        padding-top: 13px;
    }

    .tod-note {
        background: var(--tod-soft);
        border: 1px solid var(--tod-border);
        border-radius: 10px;
        margin: 0;
        min-height: 54px;
        padding: 12px;
        white-space: pre-line;
    }

    .tod-ltr {
        direction: ltr;
        display: inline-block;
        text-align: left;
        unicode-bidi: isolate;
    }

    @media (max-width: 1024px) {
        .tod-summary {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 720px) {
        .tod-summary,
        .tod-two {
            grid-template-columns: minmax(0, 1fr);
        }

        .tod-row {
            gap: 3px;
            grid-template-columns: minmax(0, 1fr);
        }
    }
</style>

<div class="trader-order-details">
    @if ($hasZeroPrice)
        <div class="tod-alert">
            يوجد بند أو إجمالي بسعر صفر. راجع أسعار طلب الجملة قبل التأكيد أو اعتماد الدفع.
        </div>
    @endif

    <div class="tod-grid tod-summary">
        <div class="tod-card">
            <div class="tod-label">رقم الطلب</div>
            <div class="tod-value"><span class="tod-ltr">{{ $order->order_no ?: $dash }}</span></div>
        </div>

        <div class="tod-card">
            <div class="tod-label">حالة الطلب</div>
            <div class="tod-value">
                <span class="tod-chip tod-chip--{{ $statusClass }}">{{ $statusLabels[$order->status] ?? ($order->status ?: $dash) }}</span>
            </div>
        </div>

        <div class="tod-card">
            <div class="tod-label">حالة الدفع</div>
            <div class="tod-value">
                <span class="tod-chip tod-chip--{{ $paymentClass }}">{{ $paymentLabels[$order->payment_status] ?? ($order->payment_status ?: $dash) }}</span>
            </div>
        </div>

        <div class="tod-card">
            <div class="tod-label">تاريخ الطلب</div>
            <div class="tod-value"><span class="tod-ltr">{{ optional($order->created_at)->format('Y-m-d H:i') ?: $dash }}</span></div>
        </div>
    </div>

    <div class="tod-grid tod-two">
        <section class="tod-section">
            <div class="tod-section-head">
                <h3 class="tod-title">بيانات التاجر</h3>
            </div>
            <div class="tod-body">
                <dl class="tod-list">
                    <div class="tod-row">
                        <dt class="tod-label">اسم التاجر</dt>
                        <dd class="tod-value">{{ $order->trader_name_snapshot ?: $order->trader?->name ?: $dash }}</dd>
                    </div>
                    <div class="tod-row">
                        <dt class="tod-label">فئة التاجر</dt>
                        <dd class="tod-value">{{ $order->trader_group_snapshot ?: $order->trader?->wholesaleCustomerGroup?->name_ar ?: $dash }}</dd>
                    </div>
                    <div class="tod-row">
                        <dt class="tod-label">الموبايل</dt>
                        <dd class="tod-value"><span class="tod-ltr">{{ $order->trader_mobile_snapshot ?: $order->trader?->mobile ?: $dash }}</span></dd>
                    </div>
                    <div class="tod-row">
                        <dt class="tod-label">رقم الحساب</dt>
                        <dd class="tod-value"><span class="tod-ltr">{{ $order->trader_account_no_snapshot ?: $order->trader?->account_no ?: $dash }}</span></dd>
                    </div>
                </dl>
            </div>
        </section>

        <section class="tod-section">
            <div class="tod-section-head">
                <h3 class="tod-title">الشحن والاستلام</h3>
            </div>
            <div class="tod-body">
                <dl class="tod-list">
                    <div class="tod-row">
                        <dt class="tod-label">اسم المستلم</dt>
                        <dd class="tod-value">{{ $order->shipping_contact_name_snapshot ?: $dash }}</dd>
                    </div>
                    <div class="tod-row">
                        <dt class="tod-label">موبايل المستلم</dt>
                        <dd class="tod-value"><span class="tod-ltr">{{ $order->shipping_mobile_snapshot ?: $dash }}</span></dd>
                    </div>
                    <div class="tod-row">
                        <dt class="tod-label">المدينة والمنطقة</dt>
                        <dd class="tod-value">{{ collect([$order->shipping_city_snapshot, $order->shipping_area_snapshot])->filter()->implode(' - ') ?: $dash }}</dd>
                    </div>
                    <div class="tod-row">
                        <dt class="tod-label">الفرع</dt>
                        <dd class="tod-value">{{ $order->branch ?: $dash }}</dd>
                    </div>
                    <div class="tod-row">
                        <dt class="tod-label">العنوان</dt>
                        <dd class="tod-value">{{ $order->shipping_address_line_snapshot ?: $dash }}</dd>
                    </div>
                </dl>
            </div>
        </section>
    </div>

    <section class="tod-section">
        <div class="tod-section-head">
            <h3 class="tod-title">بنود طلب الجملة</h3>
            <span class="tod-chip tod-chip--neutral">{{ $items->count() }} بند</span>
        </div>

        <div class="tod-table-wrap">
            <table class="tod-table">
                <thead>
                    <tr>
                        <th>المنتج</th>
                        <th>اللون</th>
                        <th>السيرية</th>
                        <th>المقاس</th>
                        <th>الكمية</th>
                        <th>سعر القطعة</th>
                        <th>الإجمالي</th>
                        <th>ملاحظات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        @php
                            $itemHasZeroPrice = (float) $item->unit_price <= 0 || (float) $item->line_total <= 0;
                            $meta = collect([
                                filled($item->product_model_no_snapshot) ? 'Model: ' . $item->product_model_no_snapshot : null,
                                filled($item->product_sku_snapshot) ? 'SKU: ' . $item->product_sku_snapshot : null,
                                filled($item->product_barcode_snapshot) ? 'Barcode: ' . $item->product_barcode_snapshot : null,
                            ])->filter()->implode(' / ');
                        @endphp
                        <tr>
                            <td>
                                <div class="tod-product">{{ $item->product_name_snapshot ?: $dash }}</div>
                                @if (filled($meta))
                                    <div class="tod-meta tod-ltr">{{ $meta }}</div>
                                @endif
                                @if ($itemHasZeroPrice)
                                    <div style="margin-top: 8px;"><span class="tod-chip tod-chip--danger">سعر صفر</span></div>
                                @endif
                            </td>
                            <td>{{ $item->color_name_snapshot ?: $item->wholesaleColor?->color_name_ar ?: $dash }}</td>
                            <td>{{ $item->series_snapshot ?: $item->series_group ?: $dash }}</td>
                            <td>{{ $item->size_text ?: $dash }}</td>
                            <td><span class="tod-ltr">{{ number_format((int) $item->quantity) }}</span></td>
                            <td><span class="tod-ltr">{{ $money($item->unit_price) }}</span></td>
                            <td><strong class="tod-ltr">{{ $money($item->line_total) }}</strong></td>
                            <td>{{ $item->notes ?: $dash }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="padding: 28px; text-align: center; color: var(--tod-muted);">
                                لا توجد بنود في هذا الطلب.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="tod-grid tod-two">
        <section class="tod-section">
            <div class="tod-section-head">
                <h3 class="tod-title">ملاحظات الطلب</h3>
            </div>
            <div class="tod-body">
                <p class="tod-note">{{ $order->notes ?: 'لا توجد ملاحظات على الطلب.' }}</p>
            </div>
        </section>

        <section class="tod-section">
            <div class="tod-section-head">
                <h3 class="tod-title">الإجماليات والدفع</h3>
            </div>
            <div class="tod-body">
                <div class="tod-totals">
                    <div class="tod-total-row">
                        <span class="tod-label">طريقة الدفع</span>
                        <strong>{{ $order->payment_method ?: $dash }}</strong>
                    </div>
                    <div class="tod-total-row">
                        <span class="tod-label">قبل الخصم</span>
                        <strong class="tod-ltr">{{ $money($order->total_before_discount) }}</strong>
                    </div>
                    <div class="tod-total-row">
                        <span class="tod-label">الخصم</span>
                        <strong class="tod-ltr">{{ $money($order->discount_value) }}</strong>
                    </div>
                    <div class="tod-total-row">
                        <span class="tod-label">الشحن</span>
                        <strong class="tod-ltr">{{ $money($order->shipping_cost) }}</strong>
                    </div>
                    <div class="tod-total-row tod-total-final">
                        <span>الإجمالي النهائي</span>
                        <span class="tod-ltr">{{ $money($order->total) }}</span>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
