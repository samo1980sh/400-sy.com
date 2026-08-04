@extends('frontend.pages.account.base')

@php
    $statusLabels = [
        'active' => 'فعّال',
        'inactive' => 'غير فعّال',
    ];

    $pointsBalance = (float) ($wallet?->points_balance ?? 0);
    $pointsEarned = (float) ($wallet?->points_earned_total ?? 0);
    $pointsSpent = (float) ($wallet?->points_spent_total ?? 0);
@endphp

@section('account_content')
    <style>
        .customer-qr-shell {
            display: grid;
            grid-template-columns: minmax(280px, 420px) 1fr;
            gap: 24px;
            align-items: stretch;
        }

        .customer-qr-card {
            border: 1px solid rgba(0, 0, 0, .08);
            border-radius: 22px;
            background: #fff;
            box-shadow: 0 18px 45px rgba(0, 0, 0, .06);
            overflow: hidden;
        }

        .customer-qr-card-main {
            padding: 28px;
            text-align: center;
        }

        .customer-qr-frame {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            border-radius: 24px;
            background: #fff;
            border: 1px solid rgba(0, 0, 0, .08);
            box-shadow: none;
            max-width: 100%;
        }

        .customer-qr-frame svg {
            display: block;
            width: min(100%, 390px);
            max-width: 390px;
            height: auto;
            image-rendering: pixelated;
            shape-rendering: crispEdges;
        }

        .customer-qr-token {
            margin-top: 18px;
            padding: 12px 14px;
            border-radius: 14px;
            background: rgba(0, 0, 0, .04);
            font-size: 14px;
            font-weight: 700;
            letter-spacing: .04em;
            overflow-wrap: anywhere;
        }

        .customer-qr-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 18px;
            padding: 7px 12px;
            border-radius: 999px;
            background: rgba(25, 135, 84, .1);
            color: #198754;
            font-size: 13px;
            font-weight: 700;
        }

        .customer-qr-status.is-inactive {
            background: rgba(108, 117, 125, .12);
            color: #6c757d;
        }

        .customer-qr-muted-box {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 320px;
            padding: 24px;
            border-radius: 24px;
            background: rgba(0, 0, 0, .035);
            color: #6c757d;
            font-weight: 600;
        }

        .customer-qr-info {
            padding: 28px;
        }

        .customer-qr-title {
            margin-bottom: 8px;
            font-size: 22px;
            font-weight: 800;
        }

        .customer-qr-text {
            color: #6c757d;
            line-height: 1.8;
        }

        .customer-qr-data {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
            margin-top: 24px;
        }

        .customer-qr-data-item {
            padding: 15px;
            border-radius: 16px;
            background: rgba(0, 0, 0, .035);
        }

        .customer-qr-data-item span {
            display: block;
            margin-bottom: 5px;
            color: #6c757d;
            font-size: 13px;
        }

        .customer-qr-data-item strong {
            display: block;
            font-weight: 800;
        }

        .customer-qr-note {
            margin-top: 20px;
            padding: 16px 18px;
            border-radius: 18px;
            background: rgba(255, 193, 7, .14);
            color: #6b5605;
            line-height: 1.8;
        }

        .customer-qr-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 20px;
        }

        .customer-qr-copy {
            border: 0;
            border-radius: 999px;
            padding: 10px 18px;
            background: #111;
            color: #fff;
            font-weight: 700;
        }

        @media (max-width: 991px) {
            .customer-qr-shell {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 575px) {
            .customer-qr-card-main,
            .customer-qr-info {
                padding: 20px;
            }

            .customer-qr-data {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="customer-qr-shell">
        <div class="customer-qr-card">
            <div class="customer-qr-card-main">
                <span class="customer-qr-status {{ $qr_code->isActive() ? '' : 'is-inactive' }}">
                    {{ $statusLabels[$qr_code->status] ?? $qr_code->status }}
                </span>

                @if ($qr_code->isActive() && filled($qr_svg))
                    <div class="customer-qr-frame">
                        {!! $qr_svg !!}
                    </div>
                    <div class="customer-qr-token" dir="ltr" data-customer-qr-account>{{ $qr_value }}</div>
                @else
                    <div class="customer-qr-muted-box">
                        رمز QR غير متاح حاليًا. يرجى مراجعة الإدارة أو أحد فروع 400.
                    </div>
                    @if (filled($qr_value))
                        <div class="customer-qr-token" dir="ltr" data-customer-qr-account>{{ $qr_value }}</div>
                    @endif
                @endif
            </div>
        </div>

        <div class="customer-qr-card">
            <div class="customer-qr-info">
                <h5 class="customer-qr-title">QR الحساب والولاء</h5>
                <p class="customer-qr-text mb-0">
                    يحتوي هذا الرمز رقم حسابك مباشرة، ويمكن مسحه داخل الصالات للتعرف على حسابك وتطبيق قسائم النقاط وتسجيل نقاط الشراء على حساب الولاء.
                </p>

                <div class="customer-qr-data">
                    <div class="customer-qr-data-item">
                        <span>رقم الحساب</span>
                        <strong dir="ltr">{{ $customer->account_no ?: '—' }}</strong>
                    </div>
                    <div class="customer-qr-data-item">
                        <span>رصيد النقاط</span>
                        <strong dir="ltr">{{ number_format($pointsBalance, 2) }}</strong>
                    </div>
                    <div class="customer-qr-data-item">
                        <span>إجمالي النقاط المكتسبة</span>
                        <strong dir="ltr">{{ number_format($pointsEarned, 2) }}</strong>
                    </div>
                    <div class="customer-qr-data-item">
                        <span>إجمالي النقاط المصروفة</span>
                        <strong dir="ltr">{{ number_format($pointsSpent, 2) }}</strong>
                    </div>
                    <div class="customer-qr-data-item">
                        <span>عدد مرات المسح</span>
                        <strong dir="ltr">{{ number_format((int) $qr_code->scan_count) }}</strong>
                    </div>
                    <div class="customer-qr-data-item">
                        <span>آخر استخدام</span>
                        <strong>{{ $qr_code->last_scanned_at?->format('Y-m-d H:i') ?: 'لا يوجد' }}</strong>
                    </div>
                </div>

                <div class="customer-qr-note">
                    إذا لم يتمكن الماسح من قراءة الرمز، ارفع سطوع الشاشة وافتح الصفحة بوضع التكبير. لا تشارك هذا الرمز مع أشخاص آخرين. في حال لاحظت استخدامًا غير صحيح لحسابك، تواصل مع الإدارة لتعطيل الرمز وإعادة تفعيله عند الحاجة.
                </div>

                @if (filled($qr_value))
                    <div class="customer-qr-actions">
                        <button type="button" class="customer-qr-copy" data-copy-customer-account>
                            نسخ رقم الحساب
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('click', function (event) {
            const button = event.target.closest('[data-copy-customer-account]');

            if (!button) {
                return;
            }

            const accountNo = document.querySelector('[data-customer-qr-account]')?.textContent?.trim() || '';

            if (!accountNo || !navigator.clipboard) {
                return;
            }

            navigator.clipboard.writeText(accountNo).then(function () {
                const originalText = button.textContent;
                button.textContent = 'تم النسخ';

                window.setTimeout(function () {
                    button.textContent = originalText;
                }, 1800);
            });
        });
    </script>
@endpush
