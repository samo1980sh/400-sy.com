<?php

namespace App\Services;

use App\Models\CustomerQrCode;
use App\Models\CustomerQrLog;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomerQrScanService
{
    public function recordScan(CustomerQrCode $qrCode, array $data = []): CustomerQrLog
    {
        return DB::transaction(function () use ($qrCode, $data): CustomerQrLog {
            $qrCode->loadMissing('customer');

            if (! $qrCode->isActive()) {
                throw ValidationException::withMessages([
                    'status' => 'QR معطل ولا يمكن استخدامه.',
                ]);
            }

            if ($qrCode->customer === null || $qrCode->customer->status !== 'active') {
                throw ValidationException::withMessages([
                    'customer_id' => 'حساب الزبون غير فعال.',
                ]);
            }

            $branch = trim((string) Arr::get($data, 'branch', ''));
            $referenceNo = trim((string) Arr::get($data, 'reference_no', ''));
            $notes = trim((string) Arr::get($data, 'notes', ''));
            $pointsEarned = (float) Arr::get($data, 'points_earned', 0);
            $pointsSpent = (float) Arr::get($data, 'points_spent', 0);
            $discountAmount = (float) Arr::get($data, 'discount_amount', 0);

            $recentLogs = $qrCode->logs()
                ->where('scanned_at', '>=', now()->subMinutes(30))
                ->orderByDesc('scanned_at')
                ->get(['branch', 'reference_no', 'scanned_at']);

            $rapidScans = $qrCode->logs()
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

            $isSuspicious = $reasons !== [];

            $log = CustomerQrLog::create([
                'customer_id' => $qrCode->customer_id,
                'customer_qr_code_id' => $qrCode->id,
                'action_type' => 'scan',
                'account_no' => $qrCode->customer->account_no,
                'customer_name' => $qrCode->customer->name,
                'mobile' => $qrCode->customer->mobile,
                'branch' => $branch !== '' ? $branch : null,
                'reference_no' => $referenceNo !== '' ? $referenceNo : null,
                'points_earned' => $pointsEarned,
                'points_spent' => $pointsSpent,
                'discount_amount' => $discountAmount,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'notes' => $this->composeNotes($notes, $reasons),
                'is_suspicious' => $isSuspicious,
                'suspicious_reason' => $isSuspicious ? implode(', ', $reasons) : null,
                'scanned_at' => now(),
            ]);

            $qrCode->forceFill([
                'scan_count' => $qrCode->scan_count + 1,
                'last_scanned_at' => now(),
            ])->save();

            return $log;
        });
    }

    private function composeNotes(string $notes, array $reasons): ?string
    {
        $parts = array_filter([
            $notes !== '' ? $notes : null,
            $reasons !== [] ? 'flags: ' . implode(', ', $reasons) : null,
        ]);

        return $parts === [] ? null : implode(' | ', $parts);
    }
}
