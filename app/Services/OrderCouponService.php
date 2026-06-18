<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\CouponSetting;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OrderCouponService
{
    /**
     * @return array{code:string, discount_amount:float, discount_type:string, discount_value:float}
     */
    public function previewForCustomer(Customer $customer, float $subtotal, string $couponCode): array
    {
        $this->ensureCouponSystemEnabled();

        $coupon = $this->findCoupon($couponCode);
        $this->validateCouponForCustomer($coupon, (int) $customer->getKey());

        return [
            'code' => $coupon->code,
            'discount_amount' => $this->calculateDiscountFromSubtotal($subtotal, $coupon),
            'discount_type' => $coupon->discount_type,
            'discount_value' => (float) $coupon->discount_value,
        ];
    }

    public function applyCoupon(Order $order, string $couponCode, ?string $notes = null): CouponRedemption
    {
        return DB::transaction(function () use ($order, $couponCode, $notes): CouponRedemption {
            $order->loadMissing('customer', 'couponRedemption');

            $this->ensureCouponSystemEnabled();

            if (blank($order->customer_id) || ! $order->customer) {
                throw new RuntimeException(__('front.checkout.coupon_customer_required'));
            }

            $coupon = $this->findCoupon($couponCode, lockForUpdate: true);
            $this->validateCouponForCustomer(
                coupon: $coupon,
                customerId: (int) $order->customer_id,
                excludedOrderId: (int) $order->getKey(),
            );

            $discountAmount = $this->calculateDiscountFromSubtotal(
                (float) $order->total_before_discount,
                $coupon,
            );

            $redemption = CouponRedemption::query()->updateOrCreate(
                ['order_id' => $order->id],
                [
                    'coupon_id' => $coupon->id,
                    'customer_id' => $order->customer_id,
                    'order_no' => $order->order_no,
                    'customer_name' => $order->customer_name_snapshot ?: $order->customer?->name,
                    'account_no' => $order->customer_account_no_snapshot ?: $order->customer?->account_no,
                    'mobile' => $order->customer_mobile_snapshot ?: $order->customer?->mobile,
                    'discount_amount' => $discountAmount,
                    'currency' => $coupon->currency,
                    'status' => 'redeemed',
                    'applied_at' => now(),
                    'notes' => $notes,
                ]
            );

            $couponDiscount = round((float) $discountAmount, 2);
            $manualDiscount = round((float) $order->discount_value, 2);
            $shippingCost = round((float) $order->shipping_cost, 2);
            $subtotal = round((float) $order->total_before_discount, 2);
            $grandTotal = max(0, $subtotal - $manualDiscount - $couponDiscount + $shippingCost);

            $order->update([
                'coupon_code_snapshot' => $coupon->code,
                'coupon_discount_value' => $couponDiscount,
                'total' => $grandTotal,
            ]);

            app(CustomerLoyaltyService::class)->syncForOrder($order->refresh());

            return $redemption;
        });
    }

    protected function ensureCouponSystemEnabled(): void
    {
        if (! CouponSetting::singleton()->enabled) {
            throw new RuntimeException(__('front.checkout.coupon_system_disabled'));
        }
    }

    protected function findCoupon(string $couponCode, bool $lockForUpdate = false): Coupon
    {
        $normalizedCode = mb_strtoupper(trim($couponCode));

        $query = Coupon::query()
            ->whereRaw('UPPER(code) = ?', [$normalizedCode]);

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        $coupon = $query->first();

        if (! $coupon instanceof Coupon) {
            throw new RuntimeException(__('front.checkout.coupon_not_found'));
        }

        return $coupon;
    }

    protected function validateCouponForCustomer(
        Coupon $coupon,
        int $customerId,
        ?int $excludedOrderId = null,
    ): void {
        if ($coupon->status !== 'active') {
            throw new RuntimeException(__('front.checkout.coupon_inactive'));
        }

        if (filled($coupon->starts_at) && now()->lt($coupon->starts_at)) {
            throw new RuntimeException(__('front.checkout.coupon_not_started'));
        }

        if (filled($coupon->ends_at) && now()->gt($coupon->ends_at)) {
            throw new RuntimeException(__('front.checkout.coupon_expired'));
        }

        $usedCountQuery = CouponRedemption::query()
            ->where('coupon_id', $coupon->id)
            ->where('customer_id', $customerId)
            ->where('status', 'redeemed');

        if ($excludedOrderId !== null && $excludedOrderId > 0) {
            $usedCountQuery->where('order_id', '!=', $excludedOrderId);
        }

        if ($usedCountQuery->count() >= (int) $coupon->usage_limit_per_customer) {
            throw new RuntimeException(__('front.checkout.coupon_usage_limit_reached'));
        }
    }

    protected function calculateDiscountFromSubtotal(float $subtotal, Coupon $coupon): float
    {
        $subtotal = max(0, $subtotal);

        $discount = match ($coupon->discount_type) {
            'fixed' => (float) $coupon->discount_value,
            default => $subtotal * ((float) $coupon->discount_value / 100),
        };

        return round(min($subtotal, max(0, $discount)), 2);
    }
}
