<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\RetailOrderApprovalService;
use App\Services\RetailOrderCancellationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use RuntimeException;
use Tests\TestCase;

class RetailOrderInventoryTest extends TestCase
{
    use DatabaseTransactions;

    public function test_full_approval_deducts_the_requested_variant_quantity(): void
    {
        [$product, $variant] = $this->productWithVariant(10);
        $order = $this->order(300);
        $item = $this->item($order, $product, $variant, 3, 100);

        $approved = $this->approve($order, [
            [$item, 3],
        ]);

        $this->assertSame('confirmed', $approved->status);
        $this->assertNotNull($approved->stock_deducted_at);
        $this->assertNull($approved->stock_restored_at);
        $this->assertSame(7, (int) $variant->refresh()->quantity);
        $this->assertDatabaseHas('order_items', [
            'id' => $item->getKey(),
            'quantity' => 3,
            'approved_quantity' => 3,
            'stock_deducted_quantity' => 3,
        ]);
    }

    public function test_partial_approval_deducts_only_the_approved_quantity(): void
    {
        [$product, $variant] = $this->productWithVariant(10);
        $order = $this->order(400);
        $item = $this->item($order, $product, $variant, 4, 100);

        $approved = $this->approve($order, [
            [$item, 2],
        ]);

        $this->assertSame(8, (int) $variant->refresh()->quantity);
        $this->assertEquals(200.0, (float) $approved->total_before_discount);
        $this->assertEquals(200.0, (float) $approved->total);
        $this->assertDatabaseHas('order_items', [
            'id' => $item->getKey(),
            'quantity' => 4,
            'approved_quantity' => 2,
            'stock_deducted_quantity' => 2,
            'line_total' => 200,
        ]);
    }

    public function test_approval_fails_without_changing_anything_when_stock_is_insufficient(): void
    {
        [$product, $variant] = $this->productWithVariant(1);
        $order = $this->order(200);
        $item = $this->item($order, $product, $variant, 2, 100);

        try {
            $this->approve($order, [
                [$item, 2],
            ]);

            $this->fail('The approval should have failed.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString(
                'المخزون غير كافٍ',
                $exception->getMessage(),
            );
        }

        $this->assertSame(1, (int) $variant->refresh()->quantity);
        $this->assertDatabaseHas('orders', [
            'id' => $order->getKey(),
            'status' => 'pending',
            'stock_deducted_at' => null,
        ]);
        $this->assertDatabaseHas('order_items', [
            'id' => $item->getKey(),
            'approved_quantity' => null,
            'stock_deducted_quantity' => 0,
            'line_total' => 200,
        ]);
    }

    public function test_multi_item_approval_deducts_each_variant_inside_one_transaction(): void
    {
        [$firstProduct, $firstVariant] = $this->productWithVariant(5);
        [$secondProduct, $secondVariant] = $this->productWithVariant(7);
        $order = $this->order(500);

        $firstItem = $this->item($order, $firstProduct, $firstVariant, 2, 100);
        $secondItem = $this->item($order, $secondProduct, $secondVariant, 3, 100);

        $this->approve($order, [
            [$firstItem, 2],
            [$secondItem, 3],
        ]);

        $this->assertSame(3, (int) $firstVariant->refresh()->quantity);
        $this->assertSame(4, (int) $secondVariant->refresh()->quantity);
    }

    public function test_multi_item_approval_rolls_back_all_rows_when_one_variant_is_short(): void
    {
        [$firstProduct, $firstVariant] = $this->productWithVariant(5);
        [$secondProduct, $secondVariant] = $this->productWithVariant(1);
        $order = $this->order(400);

        $firstItem = $this->item($order, $firstProduct, $firstVariant, 2, 100);
        $secondItem = $this->item($order, $secondProduct, $secondVariant, 2, 100);

        try {
            $this->approve($order, [
                [$firstItem, 2],
                [$secondItem, 2],
            ]);

            $this->fail('The approval should have failed.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString(
                'المخزون غير كافٍ',
                $exception->getMessage(),
            );
        }

        $this->assertSame(5, (int) $firstVariant->refresh()->quantity);
        $this->assertSame(1, (int) $secondVariant->refresh()->quantity);
        $this->assertDatabaseHas('order_items', [
            'id' => $firstItem->getKey(),
            'approved_quantity' => null,
            'stock_deducted_quantity' => 0,
        ]);
        $this->assertDatabaseHas('order_items', [
            'id' => $secondItem->getKey(),
            'approved_quantity' => null,
            'stock_deducted_quantity' => 0,
        ]);
    }

    public function test_two_order_rows_for_the_same_variant_are_aggregated_before_deduction(): void
    {
        [$product, $variant] = $this->productWithVariant(5);
        $order = $this->order(400);

        $firstItem = $this->item($order, $product, $variant, 2, 100);
        $secondItem = $this->item($order, $product, $variant, 2, 100);

        $this->approve($order, [
            [$firstItem, 2],
            [$secondItem, 2],
        ]);

        $this->assertSame(1, (int) $variant->refresh()->quantity);
    }

    public function test_aggregated_rows_cannot_oversell_the_same_variant(): void
    {
        [$product, $variant] = $this->productWithVariant(3);
        $order = $this->order(400);

        $firstItem = $this->item($order, $product, $variant, 2, 100);
        $secondItem = $this->item($order, $product, $variant, 2, 100);

        try {
            $this->approve($order, [
                [$firstItem, 2],
                [$secondItem, 2],
            ]);

            $this->fail('The approval should have failed.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString(
                'المتوفر 3 والمطلوب اعتماده 4',
                $exception->getMessage(),
            );
        }

        $this->assertSame(3, (int) $variant->refresh()->quantity);
    }

    public function test_reapproving_the_order_does_not_deduct_stock_twice(): void
    {
        [$product, $variant] = $this->productWithVariant(5);
        $order = $this->order(200);
        $item = $this->item($order, $product, $variant, 2, 100);

        $this->approve($order, [
            [$item, 2],
        ]);

        $this->assertSame(3, (int) $variant->refresh()->quantity);

        try {
            $this->approve($order->refresh(), [
                [$item, 2],
            ]);

            $this->fail('The second approval should have failed.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString(
                'حالته بانتظار التأكيد فقط',
                $exception->getMessage(),
            );
        }

        $this->assertSame(3, (int) $variant->refresh()->quantity);
    }

    public function test_positive_approval_requires_a_valid_variant_link(): void
    {
        [$product] = $this->productWithVariant(5);
        $order = $this->order(100);

        $item = $order->items()->create([
            'product_id' => $product->getKey(),
            'product_variant_id' => null,
            'quantity' => 1,
            'unit_price' => 100,
            'line_total' => 100,
            'product_name_snapshot' => 'منتج بلا قياس',
        ]);

        try {
            $this->approve($order, [
                [$item, 1],
            ]);

            $this->fail('The approval should have failed.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString(
                'غير مرتبط بقياس مخزون صالح',
                $exception->getMessage(),
            );
        }

        $this->assertDatabaseHas('orders', [
            'id' => $order->getKey(),
            'status' => 'pending',
        ]);
    }

    public function test_variant_must_belong_to_the_same_product_as_the_order_item(): void
    {
        [$firstProduct, $firstVariant] = $this->productWithVariant(5);
        [$secondProduct] = $this->productWithVariant(5);
        $order = $this->order(100);

        $item = $order->items()->create([
            'product_id' => $secondProduct->getKey(),
            'product_variant_id' => $firstVariant->getKey(),
            'quantity' => 1,
            'unit_price' => 100,
            'line_total' => 100,
            'product_name_snapshot' => 'منتج بربط غير صحيح',
        ]);

        try {
            $this->approve($order, [
                [$item, 1],
            ]);

            $this->fail('The approval should have failed.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString(
                'لا يتبع للمنتج نفسه',
                $exception->getMessage(),
            );
        }

        $this->assertSame(5, (int) $firstVariant->refresh()->quantity);
        $this->assertSame($firstProduct->getKey(), $firstVariant->product_id);
    }

    public function test_cancelling_a_confirmed_order_restores_the_exact_deducted_quantity(): void
    {
        [$product, $variant] = $this->productWithVariant(5);
        $order = $this->order(300);
        $item = $this->item($order, $product, $variant, 3, 100);

        $approved = $this->approve($order, [
            [$item, 2],
        ]);

        $this->assertSame(3, (int) $variant->refresh()->quantity);

        $cancelled = app(RetailOrderCancellationService::class)
            ->cancel($approved);

        $this->assertSame('cancelled', $cancelled->status);
        $this->assertNotNull($cancelled->stock_restored_at);
        $this->assertSame(5, (int) $variant->refresh()->quantity);
        $this->assertDatabaseHas('order_status_history', [
            'order_id' => $order->getKey(),
            'from_status' => 'confirmed',
            'to_status' => 'cancelled',
        ]);
    }

    public function test_cancelling_the_same_order_again_cannot_restore_stock_twice(): void
    {
        [$product, $variant] = $this->productWithVariant(5);
        $order = $this->order(200);
        $item = $this->item($order, $product, $variant, 2, 100);

        $approved = $this->approve($order, [
            [$item, 2],
        ]);

        $cancelled = app(RetailOrderCancellationService::class)
            ->cancel($approved);

        $this->assertSame(5, (int) $variant->refresh()->quantity);

        try {
            app(RetailOrderCancellationService::class)
                ->cancel($cancelled->refresh());

            $this->fail('The second cancellation should have failed.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString(
                'قيد المراجعة أو مؤكدة فقط',
                $exception->getMessage(),
            );
        }

        $this->assertSame(5, (int) $variant->refresh()->quantity);
    }

    public function test_cancelling_a_pending_order_does_not_change_stock(): void
    {
        [$product, $variant] = $this->productWithVariant(5);
        $order = $this->order(100);
        $this->item($order, $product, $variant, 1, 100);

        $cancelled = app(RetailOrderCancellationService::class)
            ->cancel($order);

        $this->assertSame('cancelled', $cancelled->status);
        $this->assertNull($cancelled->stock_deducted_at);
        $this->assertNull($cancelled->stock_restored_at);
        $this->assertSame(5, (int) $variant->refresh()->quantity);
    }

    public function test_a_shipped_order_cannot_be_cancelled_directly(): void
    {
        [$product, $variant] = $this->productWithVariant(5);
        $order = $this->order(100, [
            'status' => 'shipped',
            'stock_deducted_at' => now(),
        ]);

        $this->item($order, $product, $variant, 1, 100, [
            'approved_quantity' => 1,
            'stock_deducted_quantity' => 1,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'يمكن إلغاء الطلب عندما تكون حالته قيد المراجعة أو مؤكدة فقط.'
        );

        app(RetailOrderCancellationService::class)->cancel($order);
    }

    public function test_legacy_confirmed_order_without_a_deduction_marker_is_cancelled_without_stock_increase(): void
    {
        [$product, $variant] = $this->productWithVariant(5);
        $order = $this->order(200, [
            'status' => 'confirmed',
            'confirmed_at' => now(),
            'stock_deducted_at' => null,
        ]);

        $this->item($order, $product, $variant, 2, 100, [
            'approved_quantity' => 2,
            'stock_deducted_quantity' => 0,
        ]);

        $cancelled = app(RetailOrderCancellationService::class)
            ->cancel($order);

        $this->assertSame('cancelled', $cancelled->status);
        $this->assertNull($cancelled->stock_restored_at);
        $this->assertSame(5, (int) $variant->refresh()->quantity);
    }

    /**
     * @param array<int, array{0: OrderItem, 1: int}> $rows
     */
    private function approve(Order $order, array $rows): Order
    {
        return app(RetailOrderApprovalService::class)->approve(
            $order,
            collect($rows)
                ->map(fn (array $row): array => [
                    'item_id' => $row[0]->getKey(),
                    'approved_quantity' => $row[1],
                ])
                ->all(),
        );
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function item(
        Order $order,
        Product $product,
        ProductVariant $variant,
        int $quantity,
        float $unitPrice,
        array $overrides = [],
    ): OrderItem {
        return $order->items()->create(array_merge([
            'product_id' => $product->getKey(),
            'product_variant_id' => $variant->getKey(),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'line_total' => $quantity * $unitPrice,
            'product_name_snapshot' => $product->title_ar,
        ], $overrides));
    }

    /**
     * @return array{0: Product, 1: ProductVariant}
     */
    private function productWithVariant(
        int $quantity,
        float $price = 100,
    ): array {
        static $counter = 0;
        $counter++;

        $product = Product::query()->create([
            'model_no' => 'INVENTORY-PRODUCT-' . $counter,
            'title_ar' => 'منتج مخزون ' . $counter,
            'title_en' => 'Inventory product ' . $counter,
            'price' => $price,
            'show_web' => true,
            'show_retail' => true,
            'is_active' => true,
        ]);

        $variant = ProductVariant::query()->create([
            'product_id' => $product->getKey(),
            'sku' => 'INVENTORY-SKU-' . $counter,
            'barcode' => 'INVENTORY-BARCODE-' . $counter,
            'price' => $price,
            'quantity' => $quantity,
            'status' => 'active',
        ]);

        return [$product, $variant];
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function order(
        float $subtotal,
        array $overrides = [],
    ): Order {
        static $counter = 0;
        $counter++;

        return Order::query()->create(array_merge([
            'order_no' => 'INVENTORY-ORDER-' . $counter,
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'payment_method' => 'cash',
            'total_before_discount' => $subtotal,
            'discount_value' => 0,
            'coupon_discount_value' => 0,
            'point_voucher_discount_value' => 0,
            'shipping_cost' => 0,
            'total' => $subtotal,
        ], $overrides));
    }
}
