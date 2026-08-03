<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\RetailOrderApprovalService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use RuntimeException;
use Tests\TestCase;

class RetailOrderPartialApprovalTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_partially_approves_quantities_and_recalculates_totals(): void
    {
        $order = $this->order([
            'total_before_discount' => 300,
            'discount_value' => 30,
            'coupon_discount_value' => 30,
            'point_voucher_discount_value' => 0,
            'shipping_cost' => 20,
            'total' => 260,
        ]);

        [$firstProduct, $firstVariant] = $this->productWithVariant(5, 100);
        [$secondProduct, $secondVariant] = $this->productWithVariant(5, 100);

        $first = $order->items()->create([
            'product_id' => $firstProduct->getKey(),
            'product_variant_id' => $firstVariant->getKey(),
            'quantity' => 2,
            'unit_price' => 100,
            'line_total' => 200,
            'product_name_snapshot' => 'القميص',
        ]);

        $second = $order->items()->create([
            'product_id' => $secondProduct->getKey(),
            'product_variant_id' => $secondVariant->getKey(),
            'quantity' => 1,
            'unit_price' => 100,
            'line_total' => 100,
            'product_name_snapshot' => 'البنطال',
        ]);

        $approved = app(RetailOrderApprovalService::class)->approve($order, [
            ['item_id' => $first->getKey(), 'approved_quantity' => 1],
            ['item_id' => $second->getKey(), 'approved_quantity' => 1],
        ], 'اعتماد المتوفر فقط');

        $this->assertSame('confirmed', $approved->status);
        $this->assertNotNull($approved->stock_deducted_at);
        $this->assertEquals(300.0, (float) $approved->requested_total_before_discount);
        $this->assertEquals(260.0, (float) $approved->requested_total);
        $this->assertEquals(200.0, (float) $approved->total_before_discount);
        $this->assertEquals(20.0, (float) $approved->discount_value);
        $this->assertEquals(20.0, (float) $approved->coupon_discount_value);
        $this->assertEquals(180.0, (float) $approved->total);

        $this->assertDatabaseHas('order_items', [
            'id' => $first->getKey(),
            'quantity' => 2,
            'approved_quantity' => 1,
            'stock_deducted_quantity' => 1,
            'line_total' => 100,
        ]);

        $this->assertDatabaseHas('order_items', [
            'id' => $second->getKey(),
            'quantity' => 1,
            'approved_quantity' => 1,
            'stock_deducted_quantity' => 1,
            'line_total' => 100,
        ]);

        $this->assertDatabaseHas('product_variants', [
            'id' => $firstVariant->getKey(),
            'quantity' => 4,
        ]);

        $this->assertDatabaseHas('product_variants', [
            'id' => $secondVariant->getKey(),
            'quantity' => 4,
        ]);

        $this->assertDatabaseHas('order_status_history', [
            'order_id' => $order->getKey(),
            'from_status' => 'pending',
            'to_status' => 'confirmed',
        ]);
    }

    public function test_it_rejects_an_approved_quantity_above_the_requested_quantity(): void
    {
        $order = $this->order();
        [$product, $variant] = $this->productWithVariant(5, 100);

        $item = $order->items()->create([
            'product_id' => $product->getKey(),
            'product_variant_id' => $variant->getKey(),
            'quantity' => 2,
            'unit_price' => 100,
            'line_total' => 200,
            'product_name_snapshot' => 'منتج تجريبي',
        ]);

        try {
            app(RetailOrderApprovalService::class)->approve($order, [
                ['item_id' => $item->getKey(), 'approved_quantity' => 3],
            ]);

            $this->fail('The approval should have failed.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString(
                'يجب أن تكون بين 0 و2',
                $exception->getMessage(),
            );
        }

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

        $this->assertDatabaseHas('product_variants', [
            'id' => $variant->getKey(),
            'quantity' => 5,
        ]);
    }

    public function test_it_rejects_an_approval_when_all_quantities_are_zero(): void
    {
        $order = $this->order();
        [$product, $variant] = $this->productWithVariant(5, 100);

        $item = $order->items()->create([
            'product_id' => $product->getKey(),
            'product_variant_id' => $variant->getKey(),
            'quantity' => 2,
            'unit_price' => 100,
            'line_total' => 200,
            'product_name_snapshot' => 'منتج تجريبي',
        ]);

        try {
            app(RetailOrderApprovalService::class)->approve($order, [
                ['item_id' => $item->getKey(), 'approved_quantity' => 0],
            ]);

            $this->fail('The approval should have failed.');
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'لا يمكن اعتماد الطلب عندما تكون جميع الكميات المعتمدة صفراً.',
                $exception->getMessage(),
            );
        }

        $this->assertDatabaseHas('product_variants', [
            'id' => $variant->getKey(),
            'quantity' => 5,
        ]);
    }

    public function test_it_rejects_reapproving_a_non_pending_order(): void
    {
        $order = $this->order(['status' => 'confirmed']);
        [$product, $variant] = $this->productWithVariant(5, 100);

        $item = $order->items()->create([
            'product_id' => $product->getKey(),
            'product_variant_id' => $variant->getKey(),
            'quantity' => 1,
            'approved_quantity' => 1,
            'stock_deducted_quantity' => 1,
            'unit_price' => 100,
            'line_total' => 100,
            'product_name_snapshot' => 'منتج تجريبي',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'يمكن اعتماد كميات الطلب عندما تكون حالته بانتظار التأكيد فقط.'
        );

        app(RetailOrderApprovalService::class)->approve($order, [
            ['item_id' => $item->getKey(), 'approved_quantity' => 1],
        ]);
    }

    /**
     * @return array{0: Product, 1: ProductVariant}
     */
    private function productWithVariant(
        int $quantity,
        float $price,
    ): array {
        static $counter = 0;
        $counter++;

        $product = Product::query()->create([
            'model_no' => 'PARTIAL-PRODUCT-' . $counter,
            'title_ar' => 'منتج اعتماد جزئي ' . $counter,
            'title_en' => 'Partial approval product ' . $counter,
            'price' => $price,
            'show_web' => true,
            'show_retail' => true,
            'is_active' => true,
        ]);

        $variant = ProductVariant::query()->create([
            'product_id' => $product->getKey(),
            'sku' => 'PARTIAL-SKU-' . $counter,
            'barcode' => 'PARTIAL-BARCODE-' . $counter,
            'price' => $price,
            'quantity' => $quantity,
            'status' => 'active',
        ]);

        return [$product, $variant];
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function order(array $overrides = []): Order
    {
        static $counter = 0;
        $counter++;

        return Order::query()->create(array_merge([
            'order_no' => 'PARTIAL-' . $counter,
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'payment_method' => 'cash',
            'total_before_discount' => 200,
            'discount_value' => 0,
            'coupon_discount_value' => 0,
            'point_voucher_discount_value' => 0,
            'shipping_cost' => 0,
            'total' => 200,
        ], $overrides));
    }
}
