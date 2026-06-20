@php
    $empty = '—';
    $number = static fn ($value): string => number_format((float) $value, 2, '.', ',');

    $usageMethodLabel = match ($redemption->usage_method) {
        'online' => 'الصرف عبر الموقع',
        'in_store' => 'الصرف داخل الصالات',
        default => filled($redemption->usage_method) ? $redemption->usage_method : $empty,
    };

    $statusLabel = match ($redemption->status) {
        'pending' => 'معلقة',
        'available' => 'متاحة',
        'redeemed' => 'مصروفة',
        'expired' => 'منتهية',
        'cancelled' => 'ملغاة',
        default => filled($redemption->status) ? $redemption->status : $empty,
    };
@endphp

<style>
    .pvr-details {
        direction: rtl;
        text-align: right;
        display: grid;
        gap: 16px;
    }

    .pvr-summary {
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        background: #f9fafb;
        padding: 18px;
        display: flex;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
    }

    .pvr-title {
        font-size: 17px;
        font-weight: 800;
        color: #111827;
    }

    .pvr-code {
        margin-top: 6px;
        direction: ltr;
        text-align: left;
        font-size: 15px;
        font-weight: 800;
        color: #111827;
    }

    .pvr-badges {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        align-items: start;
    }

    .pvr-badge {
        border-radius: 999px;
        padding: 7px 12px;
        font-size: 12px;
        font-weight: 800;
        background: #eef2ff;
        color: #3730a3;
        border: 1px solid #c7d2fe;
    }

    .pvr-badge.status {
        background: #fff7ed;
        color: #9a3412;
        border-color: #fed7aa;
    }

    .pvr-section {
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        background: #ffffff;
        overflow: hidden;
    }

    .pvr-section-title {
        padding: 13px 16px;
        border-bottom: 1px solid #e5e7eb;
        background: #f9fafb;
        font-size: 14px;
        font-weight: 800;
        color: #111827;
    }

    .pvr-grid {
        padding: 16px;
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
    }

    .pvr-item {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 12px 14px;
        min-height: 72px;
        background: #fff;
    }

    .pvr-label {
        font-size: 12px;
        color: #6b7280;
        font-weight: 700;
        margin-bottom: 6px;
    }

    .pvr-value {
        font-size: 14px;
        color: #111827;
        font-weight: 800;
        overflow-wrap: anywhere;
    }

    .pvr-value[dir="ltr"] {
        direction: ltr;
        text-align: left;
    }

    .pvr-notes {
        padding: 16px;
        white-space: pre-line;
        color: #111827;
        font-size: 14px;
        line-height: 1.8;
    }

    @media (max-width: 900px) {
        .pvr-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="pvr-details">
    <div class="pvr-summary">
        <div>
            <div class="pvr-title">طلب صرف قسيمة نقاط</div>
            <div class="pvr-code">{{ $redemption->order_no }}</div>
        </div>
        <div class="pvr-badges">
            <span class="pvr-badge">{{ $usageMethodLabel }}</span>
            <span class="pvr-badge status">{{ $statusLabel }}</span>
        </div>
    </div>

    <div class="pvr-section">
        <div class="pvr-section-title">بيانات الزبون</div>
        <div class="pvr-grid">
            <div class="pvr-item">
                <div class="pvr-label">اسم المستخدم</div>
                <div class="pvr-value">{{ $redemption->customer_name ?: $empty }}</div>
            </div>
            <div class="pvr-item">
                <div class="pvr-label">رقم الحساب</div>
                <div class="pvr-value" dir="ltr">{{ $redemption->account_no ?: $empty }}</div>
            </div>
            <div class="pvr-item">
                <div class="pvr-label">رقم الموبايل</div>
                <div class="pvr-value" dir="ltr">{{ $redemption->mobile ?: $empty }}</div>
            </div>
        </div>
    </div>

    <div class="pvr-section">
        <div class="pvr-section-title">بيانات القسيمة</div>
        <div class="pvr-grid">
            <div class="pvr-item">
                <div class="pvr-label">القسيمة</div>
                <div class="pvr-value">{{ $redemption->voucher?->name ?: $empty }}</div>
            </div>
            <div class="pvr-item">
                <div class="pvr-label">كود قالب القسيمة</div>
                <div class="pvr-value" dir="ltr">{{ $redemption->voucher?->code ?: $empty }}</div>
            </div>
            <div class="pvr-item">
                <div class="pvr-label">فئة الزبون</div>
                <div class="pvr-value">{{ $redemption->voucher?->customerGroup?->name ?: $empty }}</div>
            </div>
            <div class="pvr-item">
                <div class="pvr-label">قيمة القسيمة</div>
                <div class="pvr-value" dir="ltr">{{ $number($redemption->voucher_value) }}</div>
            </div>
            <div class="pvr-item">
                <div class="pvr-label">النقاط المصروفة</div>
                <div class="pvr-value" dir="ltr">{{ $number($redemption->points_spent) }}</div>
            </div>
            <div class="pvr-item">
                <div class="pvr-label">الفرع</div>
                <div class="pvr-value">{{ $redemption->branch ?: $empty }}</div>
            </div>
        </div>
    </div>

    <div class="pvr-section">
        <div class="pvr-section-title">التواريخ والحالة</div>
        <div class="pvr-grid">
            <div class="pvr-item">
                <div class="pvr-label">تاريخ الإصدار</div>
                <div class="pvr-value">{{ $redemption->issued_at?->format('Y-m-d H:i') ?: $empty }}</div>
            </div>
            <div class="pvr-item">
                <div class="pvr-label">تاريخ الانتهاء</div>
                <div class="pvr-value">{{ $redemption->expires_at?->format('Y-m-d H:i') ?: $empty }}</div>
            </div>
            <div class="pvr-item">
                <div class="pvr-label">تاريخ الإنشاء</div>
                <div class="pvr-value">{{ $redemption->created_at?->format('Y-m-d H:i') ?: $empty }}</div>
            </div>
        </div>
    </div>

    <div class="pvr-section">
        <div class="pvr-section-title">ملاحظات الإدارة</div>
        <div class="pvr-notes">{{ $redemption->notes ?: $empty }}</div>
    </div>
</div>
