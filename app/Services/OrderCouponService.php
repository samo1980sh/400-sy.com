<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\CouponSetting;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OrderCouponService
{
    public function applyCoupon(Order $order, string $couponCode, ?string $notes = null): CouponRedemption
    {
        $order->loadMissing('customer', 'couponRedemption');

        $setting = CouponSetting::singleton();

        if (! $setting->enabled) {
            throw new RuntimeException('نظام الكوبونات غير مفعل.');
        }

        if (blank($order->customer_id) || ! $order->customer) {
            throw new RuntimeException('الطلب غير مرتبط بزبون صالح.');
        }

        $coupon = Coupon::query()
            ->whereRaw('UPPER(code) = ?', [mb_strtoupper(trim($couponCode))])
            ->first();

        if (! $coupon) {
            throw new RuntimeException('الكوبون غير موجود.');
        }

        $this->validateCoupon($coupon, $order);

        $discountAmount = $this->calculateDiscountAmount($order, $coupon);

        return DB::transaction(function () use ($order, $coupon, $discountAmount, $notes): CouponRedemption {
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

    protected function validateCoupon(Coupon $coupon, Order $order): void
    {
        if ($coupon->status !== 'active') {
            throw new RuntimeException('الكوبون غير فعال.');
        }

        if (filled($coupon->starts_at) && now()->lt($coupon->starts_at)) {
            throw new RuntimeException('الكوبون لم يبدأ بعد.');
        }

        if (filled($coupon->ends_at) && now()->gt($coupon->ends_at)) {
            throw new RuntimeException('الكوبون منتهي الصلاحية.');
        }

        $usedCount = CouponRedemption::query()
            ->where('coupon_id', $coupon->id)
            ->where('customer_id', $order->customer_id)
            ->where('status', 'redeemed')
            ->where('order_id', '!=', $order->id)
            ->count();

        if ($usedCount >= (int) $coupon->usage_limit_per_customer) {
            throw new RuntimeException('تم استهلاك الحد المسموح لهذا الكوبون من قبل هذا الزبون.');
        }
    }

    protected function calculateDiscountAmount(Order $order, Coupon $coupon): float
    {
        $subtotal = max(0, (float) $order->total_before_discount);

        $discount = match ($coupon->discount_type) {
            'fixed' => (float) $coupon->discount_value,
            default => $subtotal * ((float) $coupon->discount_value / 100),
        };

        return round(min($subtotal, max(0, $discount)), 2);
    }
}
