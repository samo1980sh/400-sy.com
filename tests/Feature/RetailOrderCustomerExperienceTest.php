<?php

namespace Tests\Feature;

use App\Mail\RetailOrderApprovedMail;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\RetailOrderApprovalService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

class RetailOrderCustomerExperienceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_customer_order_blade_views_compile_to_valid_php(): void
    {
        foreach ([
            'frontend/pages/account/orders.blade.php',
            'frontend/pages/account/order-show.blade.php',
        ] as $relativePath) {
            $source = file_get_contents(resource_path('views/' . $relativePath));

            $this->assertIsString($source, "Unable to read Blade view: {$relativePath}");

            $compiled = app('blade.compiler')->compileString($source);

            token_get_all($compiled, TOKEN_PARSE);

            $this->addToAssertionCount(1);
        }
    }

    public function test_it_emails_requested_and_approved_quantities_after_approval(): void
    {
        Mail::fake();

        $order = $this->order([
            'customer_email_snapshot' => 'customer@example.com',
        ]);

        [$product, $variant] = $this->productWithVariant(5);

        $item = $order->items()->create([
            'product_id' => $product->getKey(),
            'product_variant_id' => $variant->getKey(),
            'quantity' => 2,
            'unit_price' => 100,
            'line_total' => 200,
            'product_name_snapshot' => 'قميص تجريبي',
        ]);

        $approved = app(RetailOrderApprovalService::class)->approve($order, [
            ['item_id' => $item->getKey(), 'approved_quantity' => 1],
        ]);

        Mail::assertSent(RetailOrderApprovedMail::class, function (
            RetailOrderApprovedMail $mail,
        ): bool {
            return $mail->hasTo('customer@example.com')
                && $mail->order->status === 'confirmed';
        });

        $html = (new RetailOrderApprovedMail(
            $approved->load('items'),
        ))->render();

        $this->assertStringContainsString('قميص تجريبي', $html);
        $this->assertStringContainsString('تم اعتماد طلبك', $html);
        $this->assertStringContainsString('Your order has been confirmed', $html);
        $this->assertSame(1, (int) $approved->items->first()->approved_quantity);
        $this->assertSame(1, (int) $approved->items->first()->stock_deducted_quantity);
        $this->assertSame(4, (int) $variant->refresh()->quantity);
    }

    public function test_it_skips_email_when_the_order_has_no_valid_address(): void
    {
        Mail::fake();

        $order = $this->order([
            'customer_email_snapshot' => 'not-an-email',
        ]);

        [$product, $variant] = $this->productWithVariant(5);

        $item = $order->items()->create([
            'product_id' => $product->getKey(),
            'product_variant_id' => $variant->getKey(),
            'quantity' => 1,
            'unit_price' => 100,
            'line_total' => 100,
            'product_name_snapshot' => 'منتج تجريبي',
        ]);

        app(RetailOrderApprovalService::class)->approve($order, [
            ['item_id' => $item->getKey(), 'approved_quantity' => 1],
        ]);

        Mail::assertNothingSent();
        $this->assertSame(4, (int) $variant->refresh()->quantity);
    }

    public function test_mail_failure_does_not_roll_back_order_approval(): void
    {
        Mail::shouldReceive('to')
            ->once()
            ->with('customer@example.com')
            ->andThrow(new RuntimeException('SMTP unavailable'));

        $order = $this->order([
            'customer_email_snapshot' => 'customer@example.com',
        ]);

        [$product, $variant] = $this->productWithVariant(5);

        $item = $order->items()->create([
            'product_id' => $product->getKey(),
            'product_variant_id' => $variant->getKey(),
            'quantity' => 2,
            'unit_price' => 100,
            'line_total' => 200,
            'product_name_snapshot' => 'منتج تجريبي',
        ]);

        $approved = app(RetailOrderApprovalService::class)->approve($order, [
            ['item_id' => $item->getKey(), 'approved_quantity' => 1],
        ]);

        $this->assertSame('confirmed', $approved->status);
        $this->assertNotNull($approved->stock_deducted_at);
        $this->assertSame(4, (int) $variant->refresh()->quantity);
        $this->assertDatabaseHas('orders', [
            'id' => $order->getKey(),
            'status' => 'confirmed',
            'total' => 100,
        ]);
    }

    /**
     * @return array{0: Product, 1: ProductVariant}
     */
    private function productWithVariant(int $quantity): array
    {
        static $counter = 0;
        $counter++;

        $product = Product::query()->create([
            'model_no' => 'CUSTOMER-EXPERIENCE-PRODUCT-' . $counter,
            'title_ar' => 'منتج تجربة الزبون ' . $counter,
            'title_en' => 'Customer experience product ' . $counter,
            'price' => 100,
            'show_web' => true,
            'show_retail' => true,
            'is_active' => true,
        ]);

        $variant = ProductVariant::query()->create([
            'product_id' => $product->getKey(),
            'sku' => 'CUSTOMER-EXPERIENCE-SKU-' . $counter,
            'barcode' => 'CUSTOMER-EXPERIENCE-BARCODE-' . $counter,
            'price' => 100,
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
            'order_no' => 'CUSTOMER-EXPERIENCE-' . $counter,
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
