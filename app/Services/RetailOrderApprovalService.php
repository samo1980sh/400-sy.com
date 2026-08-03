<?php

namespace App\Services;

use App\Mail\RetailOrderApprovedMail;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Throwable;

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

            if (filled($lockedOrder->stock_deducted_at)) {
                throw new RuntimeException(
                    'تم خصم مخزون هذا الطلب سابقاً، ولا يمكن تكرار عملية الاعتماد.'
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
            $approvalRows = [];
            $requiredByVariant = [];

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

                $productName = trim((string) (
                    $item->product_name_snapshot
                    ?: $item->product?->name
                    ?: "المنتج رقم {$itemId}"
                ));

                $variantId = (int) ($item->product_variant_id ?? 0);
                $productId = (int) ($item->product_id ?? 0);

                if ($approvedQuantity > 0 && ($variantId < 1 || $productId < 1)) {
                    throw new RuntimeException(
                        "لا يمكن اعتماد {$productName} بكمية موجبة لأنه غير مرتبط بقياس مخزون صالح."
                    );
                }

                $unitPrice = round((float) $item->unit_price, 2);
                $lineTotal = round($unitPrice * $approvedQuantity, 2);

                $approvalRows[$itemId] = [
                    'item' => $item,
                    'product_id' => $productId,
                    'variant_id' => $variantId,
                    'product_name' => $productName,
                    'requested_quantity' => $requestedQuantity,
                    'approved_quantity' => $approvedQuantity,
                    'line_total' => $lineTotal,
                ];

                if ($approvedQuantity > 0) {
                    $requiredByVariant[$variantId] =
                        ($requiredByVariant[$variantId] ?? 0) + $approvedQuantity;
                }

                $requestedUnits += $requestedQuantity;
                $approvedUnits += $approvedQuantity;
                $newSubtotal += $lineTotal;

                $summaryLines[] = "{$productName}: طلب {$requestedQuantity} / اعتماد {$approvedQuantity}";
            }

            if ($approvedUnits < 1) {
                throw new RuntimeException(
                    'لا يمكن اعتماد الطلب عندما تكون جميع الكميات المعتمدة صفراً.'
                );
            }

            $variantIds = array_keys($requiredByVariant);

            $lockedVariants = ProductVariant::query()
                ->whereKey($variantIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy(fn (ProductVariant $variant): int => (int) $variant->getKey());

            if ($lockedVariants->count() !== count($variantIds)) {
                throw new RuntimeException(
                    'تعذر العثور على أحد قياسات المنتجات المرتبطة بالطلب.'
                );
            }

            foreach ($approvalRows as $row) {
                if ($row['approved_quantity'] < 1) {
                    continue;
                }

                /** @var ProductVariant|null $variant */
                $variant = $lockedVariants->get($row['variant_id']);

                if (! $variant instanceof ProductVariant) {
                    throw new RuntimeException(
                        "تعذر العثور على قياس المخزون المرتبط بالمنتج {$row['product_name']}."
                    );
                }

                if ((int) $variant->product_id !== $row['product_id']) {
                    throw new RuntimeException(
                        "قياس المخزون المرتبط بالمنتج {$row['product_name']} لا يتبع للمنتج نفسه."
                    );
                }
            }

            foreach ($requiredByVariant as $variantId => $requiredQuantity) {
                /** @var ProductVariant $variant */
                $variant = $lockedVariants->get($variantId);
                $availableQuantity = (int) $variant->quantity;

                if ($availableQuantity < $requiredQuantity) {
                    $productNames = collect($approvalRows)
                        ->filter(fn (array $row): bool => $row['variant_id'] === $variantId)
                        ->pluck('product_name')
                        ->unique()
                        ->implode('، ');

                    throw new RuntimeException(
                        "المخزون غير كافٍ للمنتج {$productNames}. "
                        . "المتوفر {$availableQuantity} والمطلوب اعتماده {$requiredQuantity}."
                    );
                }
            }

            foreach ($approvalRows as $row) {
                $row['item']->forceFill([
                    'approved_quantity' => $row['approved_quantity'],
                    'stock_deducted_quantity' => $row['approved_quantity'],
                    'line_total' => $row['line_total'],
                ])->save();
            }

            foreach ($requiredByVariant as $variantId => $requiredQuantity) {
                /** @var ProductVariant $variant */
                $variant = $lockedVariants->get($variantId);

                $variant->forceFill([
                    'quantity' => (int) $variant->quantity - $requiredQuantity,
                ])->save();
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

            $historyNote .= " تم خصم {$approvedUnits} قطعة من مخزون قياسات المنتجات.";

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
                'stock_deducted_at' => now(),
                'stock_restored_at' => null,
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

        $approvedOrder = $approvedOrder->refresh();
        $this->notifyCustomer($approvedOrder);

        return $approvedOrder;
    }

    private function notifyCustomer(Order $order): void
    {
        $order->loadMissing(['items', 'customer']);

        $email = trim((string) (
            $order->customer_email_snapshot
            ?: $order->customer?->email
        ));

        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return;
        }

        try {
            Mail::to($email)->send(new RetailOrderApprovedMail($order));
        } catch (Throwable $exception) {
            Log::warning('Retail order approval email could not be sent.', [
                'order_id' => $order->getKey(),
                'order_no' => $order->order_no,
                'email' => $email,
                'exception' => $exception->getMessage(),
            ]);
        }
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
