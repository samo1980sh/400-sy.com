<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerLoyaltySetting;
use App\Models\CustomerLoyaltyTransaction;
use App\Models\CustomerLoyaltyWallet;
use App\Models\CustomerQrCode;
use App\Models\CustomerQrLog;
use App\Models\PointVoucherRedemption;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomerQrScanService
{
    public function recordScan(CustomerQrCode $qrCode, array $data = []): CustomerQrLog
    {
        return DB::transaction(function () use ($qrCode, $data): CustomerQrLog {
            $lockedQr = CustomerQrCode::query()
                ->with('customer')
                ->whereKey($qrCode->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertUsable($lockedQr);

            $branch = trim((string) Arr::get($data, 'branch', ''));
            $referenceNo = trim((string) Arr::get($data, 'reference_no', ''));
            $notes = trim((string) Arr::get($data, 'notes', ''));
            $pointsEarned = (float) Arr::get($data, 'points_earned', 0);
            $pointsSpent = (float) Arr::get($data, 'points_spent', 0);
            $discountAmount = (float) Arr::get($data, 'discount_amount', 0);

            $recentLogs = $lockedQr->logs()
                ->where('scanned_at', '>=', now()->subMinutes(30))
                ->orderByDesc('scanned_at')
                ->get(['branch', 'reference_no', 'scanned_at']);

            $rapidScans = $lockedQr->logs()
                ->where('scanned_at', '>=', now()->subMinutes(10))
                ->count();

            $reasons = [];

            if ($referenceNo === '') {
                $reasons[] = 'missing_reference';
            }

            if ($rapidScans >= 3) {
                $reasons[] = 'rapid_scans';
            }

            $branchNames = $recentLogs
                ->pluck('branch')
                ->filter()
                ->unique()
                ->values();

            if ($branch !== '' && $branchNames->isNotEmpty() && ! $branchNames->contains($branch)) {
                $reasons[] = 'branch_change';
            }

            if ($recentLogs->contains(fn ($log): bool => blank($log->reference_no))) {
                $reasons[] = 'previous_missing_reference';
            }

            $log = CustomerQrLog::create([
                'customer_id' => $lockedQr->customer_id,
                'customer_qr_code_id' => $lockedQr->getKey(),
                'action_type' => 'scan',
                'account_no' => $lockedQr->customer?->account_no,
                'customer_name' => $lockedQr->customer?->name ?? '—',
                'mobile' => $lockedQr->customer?->mobile,
                'branch' => $branch !== '' ? $branch : null,
                'reference_no' => $referenceNo !== '' ? $referenceNo : null,
                'points_earned' => $pointsEarned,
                'points_spent' => $pointsSpent,
                'discount_amount' => $discountAmount,
                'sale_amount' => 0,
                'net_amount' => 0,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'notes' => $this->composeNotes($notes, $reasons),
                'is_suspicious' => $reasons !== [],
                'suspicious_reason' => $reasons !== [] ? implode(', ', $reasons) : null,
                'scanned_at' => now(),
            ]);

            $this->touchQr($lockedQr);

            return $log;
        }, 3);
    }

    public function resolveIdentifier(string $identifier, bool $lockForUpdate = false): CustomerQrCode
    {
        $identifier = mb_strtoupper(trim($identifier));

        if ($identifier === '') {
            throw ValidationException::withMessages([
                'identifier' => 'يرجى مسح QR أو إدخال رقم حساب الزبون.',
            ]);
        }

        $query = CustomerQrCode::query()
            ->with(['customer.loyaltyWallet', 'customer.retailGroups'])
            ->where(function ($query) use ($identifier): void {
                $query->whereRaw('UPPER(token) = ?', [$identifier])
                    ->orWhereHas('customer', function ($customerQuery) use ($identifier): void {
                        $customerQuery->whereRaw('UPPER(account_no) = ?', [$identifier]);
                    });
            });

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        $qrCode = $query->first();

        if (! $qrCode instanceof CustomerQrCode) {
            throw ValidationException::withMessages([
                'identifier' => 'لم يتم العثور على حساب زبون مرتبط بهذا الرمز.',
            ]);
        }

        $this->assertUsable($qrCode);

        return $qrCode;
    }

    public function recordIdentification(
        string $identifier,
        Branch $branch,
        ?User $operator = null,
        array $data = [],
    ): CustomerQrLog {
        return DB::transaction(function () use ($identifier, $branch, $operator, $data): CustomerQrLog {
            $qrCode = $this->resolveIdentifier($identifier, lockForUpdate: true);
            $customer = $this->lockedCustomer($qrCode);
            $branch = $this->lockedBranch($branch);

            $referenceNo = trim((string) Arr::get($data, 'reference_no', ''));
            $notes = trim((string) Arr::get($data, 'notes', ''));
            $reasons = $this->suspiciousReasons($qrCode, $branch, $referenceNo);

            $log = $this->createLog(
                qrCode: $qrCode,
                customer: $customer,
                branch: $branch,
                operator: $operator,
                attributes: [
                    'action_type' => 'identify',
                    'reference_no' => $referenceNo !== '' ? $referenceNo : null,
                    'points_earned' => 0,
                    'points_spent' => 0,
                    'discount_amount' => 0,
                    'sale_amount' => 0,
                    'net_amount' => 0,
                    'notes' => $this->composeNotes($notes, $reasons),
                    'is_suspicious' => $reasons !== [],
                    'suspicious_reason' => $reasons !== [] ? implode(', ', $reasons) : null,
                ],
            );

            $this->touchQr($qrCode);

            return $log->loadMissing([
                'customer.loyaltyWallet',
                'customer.retailGroups',
                'branchRecord',
                'scannedBy',
            ]);
        }, 3);
    }

    public function recordHallSale(
        string $identifier,
        Branch $branch,
        array $data,
        ?User $operator = null,
    ): CustomerQrLog {
        return DB::transaction(function () use ($identifier, $branch, $data, $operator): CustomerQrLog {
            $qrCode = $this->resolveIdentifier($identifier, lockForUpdate: true);
            $customer = $this->lockedCustomer($qrCode);
            $branch = $this->lockedBranch($branch);

            $referenceNo = trim((string) Arr::get($data, 'reference_no', ''));

            if ($referenceNo === '') {
                throw ValidationException::withMessages([
                    'reference_no' => 'رقم مرجع فاتورة الصالة مطلوب.',
                ]);
            }

            if (
                CustomerQrLog::query()
                    ->where('action_type', 'hall_sale')
                    ->where('branch_id', $branch->getKey())
                    ->where('reference_no', $referenceNo)
                    ->exists()
            ) {
                throw ValidationException::withMessages([
                    'reference_no' => 'تم تسجيل هذا المرجع في الصالة نفسها مسبقاً.',
                ]);
            }

            $saleAmount = round((float) Arr::get($data, 'sale_amount', 0), 2);
            $additionalDiscount = round((float) Arr::get($data, 'additional_discount_amount', 0), 2);

            if ($saleAmount <= 0) {
                throw ValidationException::withMessages([
                    'sale_amount' => 'قيمة فاتورة الصالة يجب أن تكون أكبر من صفر.',
                ]);
            }

            if ($additionalDiscount < 0 || $additionalDiscount > $saleAmount) {
                throw ValidationException::withMessages([
                    'additional_discount_amount' => 'قيمة الحسم الإضافي غير صحيحة.',
                ]);
            }

            [$redemption, $voucherDiscount] = $this->resolveInStoreVoucher(
                customer: $customer,
                branch: $branch,
                code: trim((string) Arr::get($data, 'point_voucher_code', '')),
                saleAmount: $saleAmount,
            );

            if (($voucherDiscount + $additionalDiscount) > $saleAmount) {
                throw ValidationException::withMessages([
                    'additional_discount_amount' => 'مجموع الحسومات لا يمكن أن يتجاوز قيمة الفاتورة.',
                ]);
            }

            $discountAmount = round($voucherDiscount + $additionalDiscount, 2);
            $netAmount = round(max(0, $saleAmount - $discountAmount), 2);
            $wallet = $this->lockedWallet($customer);
            $pointsEarned = $this->calculateHallPoints($netAmount, $wallet);
            $pointsSpent = $redemption ? (float) $redemption->points_spent : 0.0;
            $notes = trim((string) Arr::get($data, 'notes', ''));
            $reasons = $this->suspiciousReasons($qrCode, $branch, $referenceNo);

            $log = $this->createLog(
                qrCode: $qrCode,
                customer: $customer,
                branch: $branch,
                operator: $operator,
                attributes: [
                    'point_voucher_redemption_id' => $redemption?->getKey(),
                    'action_type' => 'hall_sale',
                    'reference_no' => $referenceNo,
                    'points_earned' => $pointsEarned,
                    'points_spent' => $pointsSpent,
                    'discount_amount' => $discountAmount,
                    'sale_amount' => $saleAmount,
                    'net_amount' => $netAmount,
                    'notes' => $this->composeNotes($notes, $reasons),
                    'is_suspicious' => $reasons !== [],
                    'suspicious_reason' => $reasons !== [] ? implode(', ', $reasons) : null,
                ],
            );

            if ($redemption instanceof PointVoucherRedemption) {
                $redemption->forceFill([
                    'status' => 'redeemed',
                    'branch' => $this->branchName($branch),
                    'applied_at' => now(),
                    'notes' => $redemption->notes,
                ])->save();
            }

            if ($pointsEarned > 0) {
                $balanceBefore = (float) $wallet->points_balance;
                $balanceAfter = round($balanceBefore + $pointsEarned, 2);

                $wallet->forceFill([
                    'points_balance' => $balanceAfter,
                    'points_earned_total' => round(
                        (float) $wallet->points_earned_total + $pointsEarned,
                        2,
                    ),
                ])->save();

                CustomerLoyaltyTransaction::create([
                    'customer_id' => $customer->getKey(),
                    'customer_loyalty_wallet_id' => $wallet->getKey(),
                    'type' => 'earn',
                    'points' => $pointsEarned,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'source_type' => 'qr_hall_sale',
                    'source_id' => $log->getKey(),
                    'reference_no' => $referenceNo,
                    'occurred_at' => now(),
                    'notes' => 'نقاط عملية صالة مرتبطة بحساب الزبون ' . $customer->account_no,
                ]);
            }

            $this->touchQr($qrCode);

            return $log->loadMissing([
                'customer.loyaltyWallet',
                'customer.retailGroups',
                'branchRecord',
                'scannedBy',
                'pointVoucherRedemption.voucher',
            ]);
        }, 3);
    }

    protected function assertUsable(CustomerQrCode $qrCode): void
    {
        if (! $qrCode->isActive()) {
            throw ValidationException::withMessages([
                'identifier' => 'QR معطل ولا يمكن استخدامه.',
            ]);
        }

        if ($qrCode->customer === null || $qrCode->customer->status !== 'active') {
            throw ValidationException::withMessages([
                'identifier' => 'حساب الزبون غير فعال.',
            ]);
        }

        if (blank($qrCode->customer->account_no)) {
            throw ValidationException::withMessages([
                'identifier' => 'حساب الزبون لا يملك رقم حساب صالحاً.',
            ]);
        }
    }

    protected function lockedCustomer(CustomerQrCode $qrCode): Customer
    {
        $customer = Customer::query()
            ->with(['retailGroups'])
            ->whereKey($qrCode->customer_id)
            ->lockForUpdate()
            ->first();

        if (! $customer instanceof Customer || $customer->status !== 'active') {
            throw ValidationException::withMessages([
                'identifier' => 'حساب الزبون غير فعال.',
            ]);
        }

        $qrCode->setRelation('customer', $customer);

        return $customer;
    }

    protected function lockedBranch(Branch $branch): Branch
    {
        $lockedBranch = Branch::query()
            ->whereKey($branch->getKey())
            ->lockForUpdate()
            ->first();

        if (! $lockedBranch instanceof Branch || $lockedBranch->status !== 'active') {
            throw ValidationException::withMessages([
                'branch_id' => 'الصالة أو الفرع المحدد غير فعال.',
            ]);
        }

        return $lockedBranch;
    }

    protected function lockedWallet(Customer $customer): CustomerLoyaltyWallet
    {
        $wallet = CustomerLoyaltyWallet::query()
            ->where('customer_id', $customer->getKey())
            ->lockForUpdate()
            ->first();

        if (! $wallet instanceof CustomerLoyaltyWallet) {
            $wallet = CustomerLoyaltyWallet::create([
                'customer_id' => $customer->getKey(),
                'points_balance' => 0,
                'points_earned_total' => 0,
                'points_spent_total' => 0,
                'status' => 'active',
            ]);
        }

        if ($wallet->status !== 'active') {
            throw ValidationException::withMessages([
                'identifier' => 'محفظة نقاط الزبون غير فعالة حالياً.',
            ]);
        }

        return $wallet;
    }

    /**
     * @return array{0:?PointVoucherRedemption,1:float}
     */
    protected function resolveInStoreVoucher(
        Customer $customer,
        Branch $branch,
        string $code,
        float $saleAmount,
    ): array {
        if ($code === '') {
            return [null, 0.0];
        }

        $normalizedCode = mb_strtoupper($code);

        $redemption = PointVoucherRedemption::query()
            ->with('voucher')
            ->whereRaw('UPPER(order_no) = ?', [$normalizedCode])
            ->lockForUpdate()
            ->first();

        if (! $redemption instanceof PointVoucherRedemption) {
            throw ValidationException::withMessages([
                'point_voucher_code' => 'كود قسيمة النقاط غير موجود.',
            ]);
        }

        if ((int) $redemption->customer_id !== (int) $customer->getKey()) {
            throw ValidationException::withMessages([
                'point_voucher_code' => 'قسيمة النقاط لا تخص هذا الحساب.',
            ]);
        }

        if ($redemption->usage_method !== 'in_store') {
            throw ValidationException::withMessages([
                'point_voucher_code' => 'هذه القسيمة مخصصة للاستخدام عبر الموقع وليست داخل الصالات.',
            ]);
        }

        if ($redemption->status !== 'available' || filled($redemption->order_id)) {
            throw ValidationException::withMessages([
                'point_voucher_code' => 'قسيمة النقاط غير متاحة للاستخدام.',
            ]);
        }

        if (filled($redemption->expires_at) && now()->gt($redemption->expires_at)) {
            throw ValidationException::withMessages([
                'point_voucher_code' => 'انتهت صلاحية قسيمة النقاط.',
            ]);
        }

        if (filled($redemption->branch) && ! $this->redemptionMatchesBranch($redemption, $branch)) {
            throw ValidationException::withMessages([
                'point_voucher_code' => 'قسيمة النقاط صادرة لصالة أو فرع مختلف.',
            ]);
        }

        $discount = round(min($saleAmount, max(0, (float) $redemption->voucher_value)), 2);

        if ($discount <= 0) {
            throw ValidationException::withMessages([
                'point_voucher_code' => 'قيمة قسيمة النقاط غير صالحة.',
            ]);
        }

        return [$redemption, $discount];
    }

    protected function redemptionMatchesBranch(PointVoucherRedemption $redemption, Branch $branch): bool
    {
        $expected = mb_strtolower(trim((string) $redemption->branch));

        return collect([
            $branch->name_ar,
            $branch->name_en,
            $branch->slug,
        ])->filter()
            ->map(fn ($value): string => mb_strtolower(trim((string) $value)))
            ->contains($expected);
    }

    protected function calculateHallPoints(float $netAmount, CustomerLoyaltyWallet $wallet): float
    {
        if ($wallet->status !== 'active') {
            return 0.0;
        }

        $setting = CustomerLoyaltySetting::singleton();

        if (! $setting->enabled) {
            return 0.0;
        }

        return round(max(0, $netAmount) * max(0, (float) $setting->points_per_currency), 2);
    }

    protected function createLog(
        CustomerQrCode $qrCode,
        Customer $customer,
        Branch $branch,
        ?User $operator,
        array $attributes,
    ): CustomerQrLog {
        return CustomerQrLog::create(array_merge([
            'customer_id' => $customer->getKey(),
            'customer_qr_code_id' => $qrCode->getKey(),
            'branch_id' => $branch->getKey(),
            'scanned_by_user_id' => $operator?->getKey(),
            'account_no' => $customer->account_no,
            'customer_name' => $customer->name,
            'mobile' => $customer->mobile,
            'branch' => $this->branchName($branch),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'scanned_at' => now(),
        ], $attributes));
    }

    protected function touchQr(CustomerQrCode $qrCode): void
    {
        $qrCode->forceFill([
            'scan_count' => ((int) $qrCode->scan_count) + 1,
            'last_scanned_at' => now(),
        ])->save();
    }

    /**
     * @return array<int, string>
     */
    protected function suspiciousReasons(
        CustomerQrCode $qrCode,
        Branch $branch,
        string $referenceNo,
    ): array {
        $recentLogs = $qrCode->logs()
            ->where('scanned_at', '>=', now()->subMinutes(30))
            ->orderByDesc('scanned_at')
            ->get(['branch_id', 'reference_no', 'scanned_at']);

        $rapidScans = $recentLogs
            ->where('scanned_at', '>=', now()->subMinutes(10))
            ->count();

        $reasons = [];

        if ($referenceNo === '') {
            $reasons[] = 'missing_reference';
        }

        if ($rapidScans >= 3) {
            $reasons[] = 'rapid_scans';
        }

        $branchIds = $recentLogs
            ->pluck('branch_id')
            ->filter()
            ->unique()
            ->values();

        if ($branchIds->isNotEmpty() && ! $branchIds->contains($branch->getKey())) {
            $reasons[] = 'branch_change';
        }

        if ($recentLogs->contains(fn ($log): bool => blank($log->reference_no))) {
            $reasons[] = 'previous_missing_reference';
        }

        return $reasons;
    }

    protected function branchName(Branch $branch): string
    {
        return (string) ($branch->name_ar ?: $branch->name_en ?: $branch->slug ?: ('فرع #' . $branch->getKey()));
    }

    protected function composeNotes(string $notes, array $reasons): ?string
    {
        $parts = array_filter([
            $notes !== '' ? $notes : null,
            $reasons !== [] ? 'flags: ' . implode(', ', $reasons) : null,
        ]);

        return $parts === [] ? null : implode(' | ', $parts);
    }
}
