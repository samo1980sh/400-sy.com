<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Models\PointVoucherRedemption;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OrderPointVoucherService
{
    public function looksLikePointVoucherCode(string $code): bool
    {
        return str_starts_with(mb_strtoupper(trim($code)), 'PVR-');
    }

    /**
     * @return array{code:string, discount_amount:float, message:string}
     */
    public function previewForCustomer(Customer $customer, float $subtotal, string $redemptionCode): array
    {
        $redemption = $this->findRedemption($redemptionCode);
        $this->validateRedemptionForCustomer($redemption, $customer);

        return [
            'code' => $redemption->order_no,
            'discount_amount' => $this->calculateDiscountFromSubtotal($subtotal, $redemption),
            'message' => __('front.checkout.point_voucher_applied'),
        ];
    }

    public function applyToOrder(Order $order, string $redemptionCode, ?string $notes = null): PointVoucherRedemption
    {
        return DB::transaction(function () use ($order, $redemptionCode, $notes): PointVoucherRedemption {
            $order->loadMissing('customer', 'pointVoucherRedemption');

            if (blank($order->customer_id) || ! $order->customer) {
                throw new RuntimeException(__('front.checkout.coupon_customer_required'));
            }

            $redemption = $this->findRedemption($redemptionCode, lockForUpdate: true);
            $this->validateRedemptionForCustomer($redemption, $order->customer);

            if ($order->pointVoucherRedemption instanceof PointVoucherRedemption) {
                throw new RuntimeException(__('front.checkout.point_voucher_not_available'));
            }

            $pointVoucherDiscount = $this->calculateDiscountFromSubtotal(
                (float) $order->total_before_discount,
                $redemption,
            );

            $couponDiscount = round((float) $order->coupon_discount_value, 2);
            $manualDiscount = round((float) $order->discount_value, 2);
            $shippingCost = round((float) $order->shipping_cost, 2);
            $subtotal = round((float) $order->total_before_discount, 2);
            $grandTotal = max(0, $subtotal - $manualDiscount - $couponDiscount - $pointVoucherDiscount + $shippingCost);

            $redemption->forceFill([
                'order_id' => $order->getKey(),
                'status' => 'reserved',
                'applied_at' => now(),
                'notes' => $notes ?: $redemption->notes,
            ])->save();

            $order->update([
                'point_voucher_code_snapshot' => $redemption->order_no,
                'point_voucher_discount_value' => $pointVoucherDiscount,
                'total' => $grandTotal,
            ]);

            app(CustomerLoyaltyService::class)->syncForOrder($order->refresh());

            return $redemption->refresh();
        }, 3);
    }

    protected function findRedemption(string $redemptionCode, bool $lockForUpdate = false): PointVoucherRedemption
    {
        $normalizedCode = mb_strtoupper(trim($redemptionCode));

        $query = PointVoucherRedemption::query()
            ->whereRaw('UPPER(order_no) = ?', [$normalizedCode]);

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        $redemption = $query->first();

        if (! $redemption instanceof PointVoucherRedemption) {
            throw new RuntimeException(__('front.checkout.point_voucher_not_found'));
        }

        return $redemption;
    }

    protected function validateRedemptionForCustomer(PointVoucherRedemption $redemption, Customer $customer): void
    {
        if ((int) $redemption->customer_id !== (int) $customer->getKey()) {
            throw new RuntimeException(__('front.checkout.point_voucher_customer_mismatch'));
        }

        if ($redemption->usage_method !== 'online') {
            throw new RuntimeException(__('front.checkout.point_voucher_not_online'));
        }

        if ($redemption->status !== 'available' || filled($redemption->order_id)) {
            throw new RuntimeException(__('front.checkout.point_voucher_not_available'));
        }

        if (filled($redemption->expires_at) && now()->gt($redemption->expires_at)) {
            throw new RuntimeException(__('front.checkout.point_voucher_expired'));
        }

        if ((float) $redemption->voucher_value <= 0) {
            throw new RuntimeException(__('front.checkout.point_voucher_empty_value'));
        }
    }

    protected function calculateDiscountFromSubtotal(float $subtotal, PointVoucherRedemption $redemption): float
    {
        $subtotal = max(0, $subtotal);
        $discount = max(0, (float) $redemption->voucher_value);

        return round(min($subtotal, $discount), 2);
    }
}
