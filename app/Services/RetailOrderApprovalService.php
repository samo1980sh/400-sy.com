<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class RetailOrderApprovalService
{
    /**
     * @param array<int, array{item_id:mixed, approved_quantity:mixed}> $submittedItems
     */
    public function approve(
        Order $order,
        array $submittedItems,
        ?string $approvalNote = null,
    ): Order {
        $approvedOrder = DB::transaction(function () use (
            $order,
            $submittedItems,
            $approvalNote,
        ): Order {
            $lockedOrder = Order::query()
                ->lockForUpdate()
                ->findOrFail($order->getKey());

            if ($lockedOrder->status !== 'pending') {
                throw new RuntimeException(
                    'يمكن اعتماد كميات الطلب عندما تكون حالته بانتظار التأكيد فقط.'
                );
            }

            if ($lockedOrder->payment_status === 'paid') {
                throw new RuntimeException(
                    'لا يمكن تعديل كميات طلب مثبت كمدفوع قبل معالجة فرق المبلغ.'
                );
            }

            $orderItems = $lockedOrder->items()
                ->lockForUpdate()
                ->orderBy('id')
                ->get();

            if ($orderItems->isEmpty()) {
                throw new RuntimeException('لا يحتوي الطلب على منتجات قابلة للاعتماد.');
            }

            $submitted = collect($submittedItems)
                ->mapWithKeys(function (array $row): array {
                    $itemId = (int) ($row['item_id'] ?? 0);

                    return $itemId > 0
                        ? [$itemId => (int) ($row['approved_quantity'] ?? 0)]
                        : [];
                });

            if ($submitted->count() !== $orderItems->count()) {
                throw new RuntimeException(
                    'يجب تحديد الكمية المعتمدة لجميع منتجات الطلب.'
                );
            }

            $approvedUnits = 0;
            $requestedUnits = 0;
            $newSubtotal = 0.0;
            $summaryLines = [];

            foreach ($orderItems as $item) {
                $itemId = (int) $item->getKey();

                if (! $submitted->has($itemId)) {
                    throw new RuntimeException(
                        'بيانات اعتماد المنتجات غير مكتملة أو غير مطابقة للطلب.'
                    );
                }

                $requestedQuantity = max(0, (int) $item->quantity);
                $approvedQuantity = (int) $submitted->get($itemId);

                if ($approvedQuantity < 0 || $approvedQuantity > $requestedQuantity) {
                    throw new RuntimeException(
                        "الكمية المعتمدة للمنتج رقم {$itemId} يجب أن تكون بين 0 "
                        . "و{$requestedQuantity}."
                    );
                }

                $unitPrice = round((float) $item->unit_price, 2);
                $lineTotal = round($unitPrice * $approvedQuantity, 2);

                $item->forceFill([
                    'approved_quantity' => $approvedQuantity,
                    'line_total' => $lineTotal,
                ])->save();

                $requestedUnits += $requestedQuantity;
                $approvedUnits += $approvedQuantity;
                $newSubtotal += $lineTotal;

                $productName = trim((string) (
                    $item->product_name_snapshot
                    ?: $item->product?->name
                    ?: "المنتج رقم {$itemId}"
                ));

                $summaryLines[] = "{$productName}: طلب {$requestedQuantity} / اعتماد {$approvedQuantity}";
            }

            if ($approvedUnits < 1) {
                throw new RuntimeException(
                    'لا يمكن اعتماد الطلب عندما تكون جميع الكميات المعتمدة صفراً.'
                );
            }

            $oldSubtotal = max(
                0,
                (float) $lockedOrder->total_before_discount,
            );

            $ratio = $oldSubtotal > 0
                ? min(1, $newSubtotal / $oldSubtotal)
                : 1;

            $manualDiscount = $this->scaledDiscount(
                (float) $lockedOrder->discount_value,
                $ratio,
                $newSubtotal,
            );

            $remainingAfterManual = max(0, $newSubtotal - $manualDiscount);

            $couponDiscount = $this->scaledDiscount(
                (float) ($lockedOrder->coupon_discount_value ?? 0),
                $ratio,
                $remainingAfterManual,
            );

            $remainingAfterCoupon = max(
                0,
                $remainingAfterManual - $couponDiscount,
            );

            $pointVoucherDiscount = $this->scaledDiscount(
                (float) ($lockedOrder->point_voucher_discount_value ?? 0),
                $ratio,
                $remainingAfterCoupon,
            );

            $shippingCost = max(0, (float) $lockedOrder->shipping_cost);
            $newTotal = round(
                max(
                    0,
                    $newSubtotal
                    - $manualDiscount
                    - $couponDiscount
                    - $pointVoucherDiscount
                    + $shippingCost,
                ),
                2,
            );

            $wasPartial = $approvedUnits < $requestedUnits;
            $historyNote = $wasPartial
                ? 'تم اعتماد الطلب جزئياً.'
                : 'تم اعتماد كامل كميات الطلب.';

            if (filled($approvalNote)) {
                $historyNote .= ' ملاحظة الموظف: ' . trim((string) $approvalNote);
            }

            $historyNote .= "\n" . implode("\n", $summaryLines);

            $lockedOrder->forceFill([
                'requested_total_before_discount' =>
                    $lockedOrder->requested_total_before_discount
                    ?? $lockedOrder->total_before_discount,

                'requested_total' =>
                    $lockedOrder->requested_total
                    ?? $lockedOrder->total,

                'total_before_discount' => round($newSubtotal, 2),
                'discount_value' => $manualDiscount,
                'coupon_discount_value' => $couponDiscount,
                'point_voucher_discount_value' => $pointVoucherDiscount,
                'total' => $newTotal,
                'status' => 'confirmed',
                'confirmed_at' => $lockedOrder->confirmed_at ?: now(),
            ])->save();

            OrderStatusHistory::query()->create([
                'order_id' => $lockedOrder->getKey(),
                'from_status' => 'pending',
                'to_status' => 'confirmed',
                'from_payment_status' => null,
                'to_payment_status' => null,
                'note' => $historyNote,
                'changed_by' => auth()->id(),
            ]);

            return $lockedOrder->refresh();
        }, 3);

        app(CustomerLoyaltyService::class)->syncForOrder($approvedOrder);

        return $approvedOrder->refresh();
    }

    private function scaledDiscount(
        float $originalDiscount,
        float $ratio,
        float $maximum,
    ): float {
        return round(
            min(
                max(0, $maximum),
                max(0, $originalDiscount) * max(0, $ratio),
            ),
            2,
        );
    }
}
