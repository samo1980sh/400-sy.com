<?php

namespace Tests\Feature;

use App\Models\Order;
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

        $first = $order->items()->create([
            'quantity' => 2,
            'unit_price' => 100,
            'line_total' => 200,
            'product_name_snapshot' => 'القميص',
        ]);

        $second = $order->items()->create([
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
            'line_total' => 100,
        ]);

        $this->assertDatabaseHas('order_items', [
            'id' => $second->getKey(),
            'quantity' => 1,
            'approved_quantity' => 1,
            'line_total' => 100,
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

        $item = $order->items()->create([
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
        ]);

        $this->assertDatabaseHas('order_items', [
            'id' => $item->getKey(),
            'approved_quantity' => null,
            'line_total' => 200,
        ]);
    }

    public function test_it_rejects_an_approval_when_all_quantities_are_zero(): void
    {
        $order = $this->order();

        $item = $order->items()->create([
            'quantity' => 2,
            'unit_price' => 100,
            'line_total' => 200,
            'product_name_snapshot' => 'منتج تجريبي',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'لا يمكن اعتماد الطلب عندما تكون جميع الكميات المعتمدة صفراً.'
        );

        app(RetailOrderApprovalService::class)->approve($order, [
            ['item_id' => $item->getKey(), 'approved_quantity' => 0],
        ]);
    }

    public function test_it_rejects_reapproving_a_non_pending_order(): void
    {
        $order = $this->order(['status' => 'confirmed']);

        $item = $order->items()->create([
            'quantity' => 1,
            'approved_quantity' => 1,
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
