@extends('frontend.pages.account.base')

@section('account_content')
    @php
        $formatPoints = static fn ($value): string => number_format((float) $value, 2, '.', ',');
        $formatMoney = static fn ($value): string => number_format((float) $value, 0, '.', ',');
        $walletBalance = (float) ($wallet?->points_balance ?? 0);
        $statusLabels = [
            'pending' => 'معلقة',
            'available' => 'متاحة',
            'redeemed' => 'مصروفة',
            'expired' => 'منتهية',
            'cancelled' => 'ملغاة',
        ];
        $methodLabels = [
            'online' => 'الصرف عبر الموقع',
            'in_store' => 'الصرف داخل الصالات',
        ];
    @endphp

    <style>
        .points-voucher-hero {
            border: 1px solid rgba(17, 24, 39, .08);
            border-radius: 18px;
            padding: 22px;
            margin-bottom: 24px;
            background: #fff;
            display: flex;
            justify-content: space-between;
            gap: 18px;
            flex-wrap: wrap;
        }

        .points-voucher-balance {
            min-width: 190px;
            border-radius: 16px;
            padding: 16px 18px;
            background: #111827;
            color: #fff;
            text-align: center;
        }

        .points-voucher-balance strong {
            display: block;
            font-size: 28px;
            line-height: 1.2;
            direction: ltr;
        }

        .points-voucher-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .points-voucher-card {
            border: 1px solid rgba(17, 24, 39, .1);
            border-radius: 18px;
            padding: 18px;
            background: #fff;
            position: relative;
            overflow: hidden;
        }

        .points-voucher-card.is-disabled {
            opacity: .62;
            filter: grayscale(.75);
        }

        .points-voucher-card-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 14px;
        }

        .points-voucher-title {
            font-size: 17px;
            font-weight: 800;
            margin: 0 0 4px;
        }

        .points-voucher-code {
            font-size: 12px;
            color: #6b7280;
            direction: ltr;
            text-align: right;
        }

        .points-voucher-badge {
            border-radius: 999px;
            padding: 6px 10px;
            font-size: 12px;
            font-weight: 700;
            background: #ecfdf5;
            color: #047857;
            white-space: nowrap;
        }

        .points-voucher-badge.disabled {
            background: #f3f4f6;
            color: #6b7280;
        }

        .points-voucher-data {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 14px;
        }

        .points-voucher-data div {
            border: 1px solid #eef0f3;
            border-radius: 12px;
            padding: 10px 12px;
        }

        .points-voucher-data span {
            display: block;
            color: #6b7280;
            font-size: 12px;
            margin-bottom: 4px;
        }

        .points-voucher-data strong {
            display: block;
            font-size: 14px;
            direction: ltr;
            text-align: right;
        }

        .points-voucher-form {
            border-top: 1px solid #eef0f3;
            padding-top: 16px;
            margin-top: 16px;
        }

        .points-voucher-form .row {
            row-gap: 14px;
        }

        .points-voucher-form .form-select,
        .points-voucher-form .form-control {
            min-height: 44px;
            border-radius: 10px;
        }

        .points-voucher-branch-wrap.is-hidden {
            display: none;
        }

        .points-voucher-submit {
            margin-top: 20px !important;
        }

        .points-voucher-note {
            font-size: 12px;
            line-height: 1.8;
            color: #6b7280;
            margin-top: 10px;
        }

        .points-voucher-empty {
            border: 1px dashed #d1d5db;
            border-radius: 16px;
            padding: 28px;
            text-align: center;
            color: #6b7280;
            background: #fff;
        }

        @media (max-width: 991px) {
            .points-voucher-grid,
            .points-voucher-data {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <div class="points-voucher-hero">
        <div>
            <h4 class="mb_8">صرف النقاط</h4>
            <p class="text-muted mb-0">
                اختر القسيمة المناسبة لرصيدك. القسائم غير الكافية تظهر باللون الرمادي، ومدة صلاحية القسيمة شهر واحد افتراضيًا أو حسب إعداد الإدارة.
            </p>
        </div>
        <div class="points-voucher-balance">
            <span>رصيدك الحالي</span>
            <strong>{{ $formatPoints($walletBalance) }}</strong>
            <span>نقطة</span>
        </div>
    </div>

    <div class="account-card mb_24">
        <div class="account-card-title d-flex justify-content-between align-items-center gap-3">
            <h5 class="mb-0">القسائم المتاحة</h5>
            <span class="text-muted small">حسب فئة الزبون ورصيد النقاط</span>
        </div>

        @if ($vouchers->isEmpty())
            <div class="points-voucher-empty">
                لا توجد قسائم نقاط متاحة حاليًا.
            </div>
        @else
            <div class="points-voucher-grid">
                @foreach ($vouchers as $voucher)
                    @php
                        $pointsRequired = (float) $voucher->points_required;
                        $canRedeem = $walletBalance >= $pointsRequired;
                        $validDays = (int) ($voucher->valid_days ?: 30);
                    @endphp
                    <div class="points-voucher-card {{ $canRedeem ? '' : 'is-disabled' }}">
                        <div class="points-voucher-card-head">
                            <div>
                                <h6 class="points-voucher-title">{{ $voucher->name }}</h6>
                                <div class="points-voucher-code">{{ $voucher->code }}</div>
                            </div>
                            <span class="points-voucher-badge {{ $canRedeem ? '' : 'disabled' }}">
                                {{ $canRedeem ? 'متاحة' : 'نقاط غير كافية' }}
                            </span>
                        </div>

                        <div class="points-voucher-data">
                            <div>
                                <span>النقاط المطلوبة</span>
                                <strong>{{ $formatPoints($pointsRequired) }}</strong>
                            </div>
                            <div>
                                <span>قيمة القسيمة</span>
                                <strong>{{ $formatMoney($voucher->voucher_value) }}</strong>
                            </div>
                            <div>
                                <span>الصلاحية</span>
                                <strong>{{ $validDays }} يوم</strong>
                            </div>
                            <div>
                                <span>الفئة</span>
                                <strong>{{ $voucher->customerGroup?->name ?: 'كل الفئات' }}</strong>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('front.account.points-vouchers.redeem', $voucher) }}" class="points-voucher-form" data-points-voucher-form>
                            @csrf
                            @php
                                $oldVoucherId = (string) old('voucher_id');
                                $currentVoucherId = (string) $voucher->getKey();
                                $selectedUsageMethod = $oldVoucherId === $currentVoucherId ? old('usage_method', 'online') : 'online';
                                $selectedBranch = $oldVoucherId === $currentVoucherId ? old('branch') : null;
                            @endphp
                            <input type="hidden" name="voucher_id" value="{{ $voucher->getKey() }}">
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label">طريقة الصرف</label>
                                    <select name="usage_method" class="form-select" data-points-voucher-method {{ $canRedeem ? '' : 'disabled' }} required>
                                        <option value="online" @selected($selectedUsageMethod === 'online')>الصرف عبر الموقع</option>
                                        <option value="in_store" @selected($selectedUsageMethod === 'in_store')>الصرف داخل الصالات</option>
                                    </select>
                                </div>
                                <div class="col-md-6 points-voucher-branch-wrap {{ $selectedUsageMethod === 'in_store' ? '' : 'is-hidden' }}">
                                    <label class="form-label">الفرع عند الصرف داخل الصالة</label>
                                    <select name="branch" class="form-select" data-points-voucher-branch {{ $canRedeem && $selectedUsageMethod === 'in_store' ? '' : 'disabled' }}>
                                        <option value="">اختر الفرع</option>
                                        @foreach ($branches as $branchValue => $branchLabel)
                                            <option value="{{ $branchValue }}" @selected((string) $selectedBranch === (string) $branchValue)>{{ $branchLabel }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <button type="submit" class="tf-btn btn-fill radius-3 w-100 points-voucher-submit" {{ $canRedeem ? '' : 'disabled' }}>
                                صرف النقاط وإصدار القسيمة
                            </button>

                            <div class="points-voucher-note">
                                عند إصدار القسيمة يتم خصم النقاط مباشرة. إذا اخترت الصرف عبر الموقع يظهر لك كود القسيمة، وإذا اخترت الصرف داخل الصالات يتم استخدامه في الفرع المحدد.
                            </div>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="account-card">
        <div class="account-card-title d-flex justify-content-between align-items-center gap-3">
            <h5 class="mb-0">سجل قسائم النقاط</h5>
        </div>

        @if ($redemptions->isEmpty())
            <p class="text-muted mb-0">لا توجد قسائم مصروفة بعد.</p>
        @else
            <div class="account-table-wrap">
                <table class="account-table">
                    <thead>
                    <tr>
                        <th>الكود</th>
                        <th>القسيمة</th>
                        <th>القيمة</th>
                        <th>النقاط</th>
                        <th>طريقة الاستخدام</th>
                        <th>الحالة</th>
                        <th>الصلاحية</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($redemptions as $redemption)
                        <tr>
                            <td dir="ltr">{{ $redemption->order_no }}</td>
                            <td>{{ $redemption->voucher?->name ?: '—' }}</td>
                            <td>{{ $formatMoney($redemption->voucher_value) }}</td>
                            <td>{{ $formatPoints($redemption->points_spent) }}</td>
                            <td>{{ $methodLabels[$redemption->usage_method] ?? $redemption->usage_method }}</td>
                            <td><span class="account-badge">{{ $statusLabels[$redemption->status] ?? $redemption->status }}</span></td>
                            <td>{{ $redemption->expires_at?->format('Y-m-d') ?: '—' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt_16">
                {{ $redemptions->links() }}
            </div>
        @endif
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-points-voucher-form]').forEach(function (form) {
                const methodSelect = form.querySelector('[data-points-voucher-method]');
                const branchWrap = form.querySelector('.points-voucher-branch-wrap');
                const branchSelect = form.querySelector('[data-points-voucher-branch]');

                if (!methodSelect || !branchWrap || !branchSelect) {
                    return;
                }

                const syncBranchField = function () {
                    const needsBranch = methodSelect.value === 'in_store';
                    branchWrap.classList.toggle('is-hidden', !needsBranch);
                    branchSelect.disabled = !needsBranch || methodSelect.disabled;

                    if (!needsBranch) {
                        branchSelect.value = '';
                    }
                };

                methodSelect.addEventListener('change', syncBranchField);
                syncBranchField();
            });
        });
    </script>
@endsection
