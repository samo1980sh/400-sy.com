<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class RetailOrderCancellationService
{
    public function cancel(Order $order, ?string $cancellationNote = null): Order
    {
        $cancelledOrder = DB::transaction(function () use (
            $order,
            $cancellationNote,
        ): Order {
            $lockedOrder = Order::query()
                ->lockForUpdate()
                ->findOrFail($order->getKey());

            if (! in_array($lockedOrder->status, ['pending', 'confirmed'], true)) {
                throw new RuntimeException(
                    'يمكن إلغاء الطلب عندما تكون حالته قيد المراجعة أو مؤكدة فقط.'
                );
            }

            $fromStatus = (string) $lockedOrder->status;

            $orderItems = $lockedOrder->items()
                ->lockForUpdate()
                ->orderBy('id')
                ->get();

            $restoredUnits = 0;
            $stockRestoredAt = $lockedOrder->stock_restored_at;
            $stockNote = 'لم يكن الطلب قد خُصم من المخزون.';

            if (filled($lockedOrder->stock_deducted_at)) {
                if (filled($lockedOrder->stock_restored_at)) {
                    $stockNote = 'كان مخزون الطلب قد أُعيد سابقاً، ولم تتكرر الزيادة.';
                } else {
                    $requiredByVariant = [];
                    $restorationRows = [];

                    foreach ($orderItems as $item) {
                        $deductedQuantity = max(
                            0,
                            (int) ($item->stock_deducted_quantity ?? 0),
                        );

                        if ($deductedQuantity < 1) {
                            continue;
                        }

                        $variantId = (int) ($item->product_variant_id ?? 0);
                        $productId = (int) ($item->product_id ?? 0);
                        $productName = trim((string) (
                            $item->product_name_snapshot
                            ?: "المنتج رقم {$item->getKey()}"
                        ));

                        if ($variantId < 1 || $productId < 1) {
                            throw new RuntimeException(
                                "تعذر إعادة مخزون {$productName} لأن ارتباط القياس غير مكتمل."
                            );
                        }

                        $requiredByVariant[$variantId] =
                            ($requiredByVariant[$variantId] ?? 0) + $deductedQuantity;

                        $restorationRows[] = [
                            'variant_id' => $variantId,
                            'product_id' => $productId,
                            'product_name' => $productName,
                        ];
                    }

                    if ($requiredByVariant === []) {
                        throw new RuntimeException(
                            'تعذر إلغاء الطلب لأن علامة خصم المخزون موجودة دون كميات مخصومة مسجلة.'
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
                            'تعذر العثور على أحد قياسات المنتجات اللازمة لإعادة المخزون.'
                        );
                    }

                    foreach ($restorationRows as $row) {
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

                    foreach ($requiredByVariant as $variantId => $quantity) {
                        /** @var ProductVariant $variant */
                        $variant = $lockedVariants->get($variantId);

                        $variant->forceFill([
                            'quantity' => (int) $variant->quantity + $quantity,
                        ])->save();

                        $restoredUnits += $quantity;
                    }

                    $stockRestoredAt = now();
                    $stockNote = "تمت إعادة {$restoredUnits} قطعة إلى مخزون قياسات المنتجات.";
                }
            }

            $historyNote = 'تم إلغاء الطلب. ' . $stockNote;

            if (filled($cancellationNote)) {
                $historyNote .= ' ملاحظة الموظف: ' . trim((string) $cancellationNote);
            }

            $lockedOrder->forceFill([
                'status' => 'cancelled',
                'cancelled_at' => $lockedOrder->cancelled_at ?: now(),
                'stock_restored_at' => $stockRestoredAt,
            ])->save();

            OrderStatusHistory::query()->create([
                'order_id' => $lockedOrder->getKey(),
                'from_status' => $fromStatus,
                'to_status' => 'cancelled',
                'from_payment_status' => null,
                'to_payment_status' => null,
                'note' => $historyNote,
                'changed_by' => auth()->id(),
            ]);

            return $lockedOrder->refresh();
        }, 3);

        app(CustomerLoyaltyService::class)->syncForOrder($cancelledOrder);

        return $cancelledOrder->refresh();
    }
}
