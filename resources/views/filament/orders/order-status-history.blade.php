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
@endphp

<style>
    .order-history-panel {
        --oh-bg: #ffffff;
        --oh-soft: #f8fafc;
        --oh-border: #e5e7eb;
        --oh-text: #111827;
        --oh-muted: #6b7280;
        color: var(--oh-text);
        direction: rtl;
        font-size: 14px;
        line-height: 1.65;
    }

    .dark .order-history-panel {
        --oh-bg: #111827;
        --oh-soft: rgba(255, 255, 255, .045);
        --oh-border: rgba(255, 255, 255, .12);
        --oh-text: #f9fafb;
        --oh-muted: #9ca3af;
    }

    .oh-list {
        display: grid;
        gap: 14px;
        position: relative;
    }

    .oh-item {
        background: var(--oh-bg);
        border: 1px solid var(--oh-border);
        border-radius: 14px;
        overflow: hidden;
    }

    .oh-head {
        align-items: center;
        background: var(--oh-soft);
        border-bottom: 1px solid var(--oh-border);
        display: flex;
        flex-wrap: wrap;
        gap: 10px 14px;
        justify-content: space-between;
        padding: 12px 14px;
    }

    .oh-title {
        font-size: 14px;
        font-weight: 800;
    }

    .oh-time {
        color: var(--oh-muted);
        direction: ltr;
        font-size: 12px;
        unicode-bidi: isolate;
    }

    .oh-body {
        padding: 14px;
    }

    .oh-changes {
        display: grid;
        gap: 10px;
    }

    .oh-change-row {
        align-items: center;
        display: grid;
        gap: 10px;
        grid-template-columns: 110px minmax(0, 1fr);
    }

    .oh-label {
        color: var(--oh-muted);
        font-size: 12px;
    }

    .oh-flow {
        align-items: center;
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .oh-arrow {
        color: var(--oh-muted);
        font-weight: 800;
    }

    .oh-chip {
        border-radius: 999px;
        display: inline-flex;
        font-size: 12px;
        font-weight: 750;
        padding: 4px 9px;
        white-space: nowrap;
    }

    .oh-chip--pending { background: #fef3c7; color: #92400e; }
    .oh-chip--confirmed { background: #dbeafe; color: #1e40af; }
    .oh-chip--shipped { background: #ede9fe; color: #5b21b6; }
    .oh-chip--delivered,
    .oh-chip--paid { background: #dcfce7; color: #166534; }
    .oh-chip--cancelled,
    .oh-chip--unpaid { background: #fee2e2; color: #991b1b; }
    .oh-chip--neutral { background: #f3f4f6; color: #374151; }

    .dark .oh-chip--pending { background: rgba(245, 158, 11, .18); color: #fcd34d; }
    .dark .oh-chip--confirmed { background: rgba(59, 130, 246, .18); color: #93c5fd; }
    .dark .oh-chip--shipped { background: rgba(139, 92, 246, .18); color: #c4b5fd; }
    .dark .oh-chip--delivered,
    .dark .oh-chip--paid { background: rgba(34, 197, 94, .18); color: #86efac; }
    .dark .oh-chip--cancelled,
    .dark .oh-chip--unpaid { background: rgba(239, 68, 68, .18); color: #fca5a5; }
    .dark .oh-chip--neutral { background: rgba(255, 255, 255, .09); color: #d1d5db; }

    .oh-note {
        background: var(--oh-soft);
        border: 1px solid var(--oh-border);
        border-radius: 10px;
        margin-top: 12px;
        padding: 10px 12px;
        white-space: pre-line;
    }

    .oh-footer {
        align-items: center;
        border-top: 1px dashed var(--oh-border);
        color: var(--oh-muted);
        display: flex;
        flex-wrap: wrap;
        font-size: 12px;
        gap: 8px 18px;
        margin-top: 12px;
        padding-top: 10px;
    }

    .oh-empty {
        background: var(--oh-soft);
        border: 1px dashed var(--oh-border);
        border-radius: 14px;
        color: var(--oh-muted);
        padding: 34px 18px;
        text-align: center;
    }

    @media (max-width: 620px) {
        .oh-change-row {
            grid-template-columns: minmax(0, 1fr);
        }
    }
</style>

<div class="order-history-panel">
    @if ($history->isEmpty())
        <div class="oh-empty">لا يوجد سجل حالة لهذا الطلب حتى الآن.</div>
    @else
        <div class="oh-list">
            @foreach ($history as $entry)
                @php
                    $hasOrderStatusChange = filled($entry->from_status) || filled($entry->to_status);
                    $hasPaymentStatusChange = filled($entry->from_payment_status) || filled($entry->to_payment_status);
                    $isInitialEntry = blank($entry->from_status)
                        && blank($entry->from_payment_status)
                        && filled($entry->to_status);

                    $entryTitle = match (true) {
                        $isInitialEntry => 'إنشاء الطلب',
                        $hasOrderStatusChange && $hasPaymentStatusChange => 'تحديث حالة الطلب والدفع',
                        $hasOrderStatusChange => 'تحديث حالة الطلب',
                        $hasPaymentStatusChange => 'تحديث حالة الدفع',
                        default => 'تحديث على الطلب',
                    };
                @endphp

                <article class="oh-item">
                    <div class="oh-head">
                        <div class="oh-title">{{ $entryTitle }}</div>
                        <div class="oh-time">{{ optional($entry->created_at)->format('Y-m-d H:i:s') ?: '—' }}</div>
                    </div>

                    <div class="oh-body">
                        <div class="oh-changes">
                            @if ($hasOrderStatusChange)
                                <div class="oh-change-row">
                                    <div class="oh-label">حالة الطلب</div>
                                    <div class="oh-flow">
                                        <span class="oh-chip oh-chip--{{ $entry->from_status ?: 'neutral' }}">
                                            {{ filled($entry->from_status)
                                                ? ($statusLabels[$entry->from_status] ?? $entry->from_status)
                                                : 'بداية الطلب' }}
                                        </span>
                                        <span class="oh-arrow">←</span>
                                        <span class="oh-chip oh-chip--{{ $entry->to_status ?: 'neutral' }}">
                                            {{ filled($entry->to_status)
                                                ? ($statusLabels[$entry->to_status] ?? $entry->to_status)
                                                : '—' }}
                                        </span>
                                    </div>
                                </div>
                            @endif

                            @if ($hasPaymentStatusChange)
                                <div class="oh-change-row">
                                    <div class="oh-label">حالة الدفع</div>
                                    <div class="oh-flow">
                                        <span class="oh-chip oh-chip--{{ $entry->from_payment_status ?: 'neutral' }}">
                                            {{ filled($entry->from_payment_status)
                                                ? ($paymentStatusLabels[$entry->from_payment_status] ?? $entry->from_payment_status)
                                                : 'بداية الطلب' }}
                                        </span>
                                        <span class="oh-arrow">←</span>
                                        <span class="oh-chip oh-chip--{{ $entry->to_payment_status ?: 'neutral' }}">
                                            {{ filled($entry->to_payment_status)
                                                ? ($paymentStatusLabels[$entry->to_payment_status] ?? $entry->to_payment_status)
                                                : '—' }}
                                        </span>
                                    </div>
                                </div>
                            @endif
                        </div>

                        @if (filled($entry->note))
                            <div class="oh-note">{{ $entry->note }}</div>
                        @endif

                        <div class="oh-footer">
                            <span>تم بواسطة: <strong>{{ $entry->changedBy?->name ?: 'النظام / واجهة المتجر' }}</strong></span>
                            @if (filled($entry->changed_by))
                                <span>معرّف المستخدم: <strong>{{ $entry->changed_by }}</strong></span>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</div>
