@php
    $dash = '—';

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
@endphp

<style>
    .trader-status-history {
        --tsh-bg: #ffffff;
        --tsh-soft: #f8fafc;
        --tsh-border: #e5e7eb;
        --tsh-text: #111827;
        --tsh-muted: #6b7280;
        direction: rtl;
        color: var(--tsh-text);
        font-size: 14px;
        line-height: 1.65;
    }

    .dark .trader-status-history {
        --tsh-bg: #111827;
        --tsh-soft: rgba(255, 255, 255, .05);
        --tsh-border: rgba(255, 255, 255, .12);
        --tsh-text: #f9fafb;
        --tsh-muted: #9ca3af;
    }

    .tsh-panel {
        background: var(--tsh-bg);
        border: 1px solid var(--tsh-border);
        border-radius: 12px;
        overflow: hidden;
    }

    .tsh-head {
        background: var(--tsh-soft);
        border-bottom: 1px solid var(--tsh-border);
        padding: 14px 16px;
    }

    .tsh-title {
        font-size: 15px;
        font-weight: 850;
        margin: 0;
    }

    .tsh-desc {
        color: var(--tsh-muted);
        font-size: 12px;
        margin-top: 3px;
    }

    .tsh-empty {
        color: var(--tsh-muted);
        padding: 24px;
        text-align: center;
    }

    .tsh-list {
        padding: 8px 16px 16px;
    }

    .tsh-item {
        display: grid;
        gap: 12px;
        grid-template-columns: 26px minmax(0, 1fr);
        position: relative;
    }

    .tsh-item:not(:last-child)::before {
        background: var(--tsh-border);
        bottom: -2px;
        content: "";
        position: absolute;
        right: 12px;
        top: 34px;
        width: 2px;
    }

    .tsh-dot {
        background: #0f766e;
        border: 4px solid var(--tsh-bg);
        border-radius: 999px;
        box-shadow: 0 0 0 1px var(--tsh-border);
        height: 18px;
        margin-top: 19px;
        position: relative;
        width: 18px;
        z-index: 1;
    }

    .tsh-card {
        border-bottom: 1px dashed var(--tsh-border);
        padding: 14px 0;
    }

    .tsh-item:last-child .tsh-card {
        border-bottom: 0;
        padding-bottom: 0;
    }

    .tsh-top {
        align-items: flex-start;
        display: flex;
        gap: 12px;
        justify-content: space-between;
    }

    .tsh-flow {
        align-items: center;
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .tsh-meta {
        color: var(--tsh-muted);
        font-size: 12px;
        min-width: 130px;
        text-align: left;
    }

    .tsh-note {
        color: var(--tsh-text);
        margin-top: 9px;
    }

    .tsh-chip {
        align-items: center;
        border-radius: 999px;
        display: inline-flex;
        font-size: 12px;
        font-weight: 800;
        padding: 5px 10px;
        white-space: nowrap;
    }

    .tsh-chip--pending,
    .tsh-chip--unpaid { background: #fef3c7; color: #92400e; }
    .tsh-chip--confirmed { background: #dbeafe; color: #1e40af; }
    .tsh-chip--shipped { background: #ede9fe; color: #5b21b6; }
    .tsh-chip--delivered,
    .tsh-chip--paid { background: #dcfce7; color: #166534; }
    .tsh-chip--cancelled { background: #fee2e2; color: #991b1b; }
    .tsh-chip--neutral { background: #f3f4f6; color: #374151; }

    .dark .tsh-chip--pending,
    .dark .tsh-chip--unpaid { background: rgba(245, 158, 11, .18); color: #fcd34d; }
    .dark .tsh-chip--confirmed { background: rgba(59, 130, 246, .18); color: #93c5fd; }
    .dark .tsh-chip--shipped { background: rgba(139, 92, 246, .18); color: #c4b5fd; }
    .dark .tsh-chip--delivered,
    .dark .tsh-chip--paid { background: rgba(34, 197, 94, .18); color: #86efac; }
    .dark .tsh-chip--cancelled { background: rgba(239, 68, 68, .18); color: #fca5a5; }
    .dark .tsh-chip--neutral { background: rgba(255, 255, 255, .09); color: #d1d5db; }

    .tsh-ltr {
        direction: ltr;
        display: inline-block;
        text-align: left;
        unicode-bidi: isolate;
    }

    @media (max-width: 720px) {
        .tsh-top {
            display: block;
        }

        .tsh-meta {
            margin-top: 10px;
            text-align: right;
        }
    }
</style>

<div class="trader-status-history">
    <section class="tsh-panel">
        <div class="tsh-head">
            <h3 class="tsh-title">سجل الحالة والدفع</h3>
            <div class="tsh-desc">تسلسل تغييرات حالة الطلب وحالة الدفع من الأحدث إلى الأقدم.</div>
        </div>

        @if ($history->isEmpty())
            <div class="tsh-empty">لا يوجد سجل حالات لهذا الطلب حتى الآن.</div>
        @else
            <div class="tsh-list">
                @foreach ($history as $row)
                    @php
                        $isPaymentChange = filled($row->from_payment_status) || filled($row->to_payment_status);
                        $fromState = $isPaymentChange ? $row->from_payment_status : $row->from_status;
                        $toState = $isPaymentChange ? $row->to_payment_status : $row->to_status;
                        $fromLabel = $isPaymentChange ? ($paymentLabels[$fromState] ?? ($fromState ?: $dash)) : ($statusLabels[$fromState] ?? ($fromState ?: $dash));
                        $toLabel = $isPaymentChange ? ($paymentLabels[$toState] ?? ($toState ?: $dash)) : ($statusLabels[$toState] ?? ($toState ?: $dash));
                    @endphp

                    <article class="tsh-item">
                        <span class="tsh-dot"></span>

                        <div class="tsh-card">
                            <div class="tsh-top">
                                <div>
                                    <div class="tsh-flow">
                                        <span class="tsh-chip tsh-chip--neutral">{{ $isPaymentChange ? 'الدفع' : 'الطلب' }}</span>
                                        <span>من</span>
                                        <span class="tsh-chip tsh-chip--{{ $fromState ?: 'neutral' }}">{{ $fromLabel }}</span>
                                        <span>إلى</span>
                                        <span class="tsh-chip tsh-chip--{{ $toState ?: 'neutral' }}">{{ $toLabel }}</span>
                                    </div>

                                    @if (filled($row->note))
                                        <div class="tsh-note">{{ $row->note }}</div>
                                    @endif
                                </div>

                                <div class="tsh-meta">
                                    <div><span class="tsh-ltr">{{ optional($row->created_at)->format('Y-m-d H:i') ?: $dash }}</span></div>
                                    <div>{{ $row->changedBy?->name ?? 'النظام' }}</div>
                                </div>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
</div>
