<?php

namespace App\Services;

use App\Models\CustomerLoyaltySetting;
use App\Models\CustomerLoyaltyTransaction;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class CustomerLoyaltyService
{
    public function syncForOrder(Order $order): ?CustomerLoyaltyTransaction
    {
        $order->loadMissing('customer');

        $setting = CustomerLoyaltySetting::singleton();

        if (! $setting->enabled || blank($order->customer_id) || ! $order->customer) {
            return null;
        }

        $targetPoints = $this->isEligible($order, $setting->award_on_status)
            ? $this->calculatePoints($order, $setting)
            : 0;

        return DB::transaction(function () use ($order, $targetPoints): ?CustomerLoyaltyTransaction {
            $wallet = $order->customer?->loyaltyWallet()
                ->lockForUpdate()
                ->first();

            if (! $wallet) {
                $wallet = $order->customer?->loyaltyWallet()->create([
                    'points_balance' => 0,
                    'points_earned_total' => 0,
                    'points_spent_total' => 0,
                    'status' => 'active',
                ]);
            }

            if (! $wallet) {
                return null;
            }

            $currentNet = (float) CustomerLoyaltyTransaction::query()
                ->where('customer_loyalty_wallet_id', $wallet->id)
                ->where('source_type', 'order')
                ->where('source_id', $order->id)
                ->selectRaw("COALESCE(SUM(CASE WHEN type = 'deduct' THEN -points ELSE points END), 0) as net_points")
                ->first()?->net_points ?? 0;

            $delta = round($targetPoints - $currentNet, 2);

            if (abs($delta) < 0.01) {
                return null;
            }

            $balanceBefore = (float) $wallet->points_balance;

            if ($delta > 0) {
                $wallet->points_balance = $balanceBefore + $delta;
                $wallet->points_earned_total = ((float) $wallet->points_earned_total) + $delta;

                $wallet->save();

                return CustomerLoyaltyTransaction::create([
                    'customer_id' => $order->customer_id,
                    'customer_loyalty_wallet_id' => $wallet->id,
                    'type' => 'earn',
                    'points' => $delta,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $wallet->points_balance,
                    'source_type' => 'order',
                    'source_id' => $order->id,
                    'reference_no' => $order->order_no,
                    'occurred_at' => now(),
                    'notes' => 'إضافة نقاط تلقائيًا للطلب ' . $order->order_no,
                ]);
            }

            $delta = abs($delta);
            $wallet->points_balance = max(0, $balanceBefore - $delta);
            $wallet->points_earned_total = max(0, ((float) $wallet->points_earned_total) - $delta);

            $wallet->save();

            return CustomerLoyaltyTransaction::create([
                'customer_id' => $order->customer_id,
                'customer_loyalty_wallet_id' => $wallet->id,
                'type' => 'deduct',
                'points' => $delta,
                'balance_before' => $balanceBefore,
                'balance_after' => $wallet->points_balance,
                'source_type' => 'order',
                'source_id' => $order->id,
                'reference_no' => $order->order_no,
                'occurred_at' => now(),
                'notes' => 'خصم نقاط تلقائيًا للطلب ' . $order->order_no,
            ]);
        });
    }

    protected function isEligible(Order $order, string $awardOnStatus): bool
    {
        return match ($awardOnStatus) {
            'confirmed' => $order->status === 'confirmed',
            'paid' => $order->payment_status === 'paid',
            default => $order->status === 'delivered',
        };
    }

    protected function calculatePoints(Order $order, CustomerLoyaltySetting $setting): float
    {
        $baseAmount = match ($setting->points_base) {
            'grand_total' => (float) $order->total,
            default => max(
                0,
                (float) $order->total_before_discount
                - (float) $order->discount_value
                - (float) ($order->coupon_discount_value ?? 0)
            ),
        };

        return round($baseAmount * (float) $setting->points_per_currency, 2);
    }
}
