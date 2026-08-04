<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\BranchCategory;
use App\Models\Customer;
use App\Models\CustomerLoyaltySetting;
use App\Models\CustomerLoyaltyTransaction;
use App\Models\PointVoucherRedemption;
use App\Models\PointsVoucher;
use App\Services\CustomerQrScanService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CustomerQrHallOperationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_new_customer_without_account_number_receives_a_unique_account_and_qr(): void
    {
        $customer = Customer::create([
            'name' => 'زبون دون رقم حساب',
            'mobile' => '0999888777',
            'email' => 'auto-account@example.test',
            'password' => 'password123',
            'status' => 'active',
        ]);

        $this->assertNotEmpty($customer->account_no);
        $this->assertStringStartsWith('CUST-', (string) $customer->account_no);
        $this->assertDatabaseHas('customers', [
            'id' => $customer->getKey(),
            'account_no' => $customer->account_no,
        ]);
        $this->assertDatabaseHas('customer_qr_codes', [
            'customer_id' => $customer->getKey(),
            'status' => 'active',
        ]);
    }

    public function test_customer_qr_page_encodes_and_displays_the_account_number_instead_of_the_legacy_token(): void
    {
        $customer = $this->customer();
        $qrCode = $customer->qrCode()->firstOrFail();

        $response = $this
            ->actingAs($customer, 'customer')
            ->get(route('front.account.qr-code'));

        $response->assertOk();
        $response->assertSee($customer->account_no, escape: false);
        $response->assertDontSee($qrCode->token, escape: false);
    }

    public function test_it_resolves_the_customer_by_account_number_and_legacy_token(): void
    {
        $customer = $this->customer();
        $qrCode = $customer->qrCode()->firstOrFail();
        $service = app(CustomerQrScanService::class);

        $byAccount = $service->resolveIdentifier((string) $customer->account_no);
        $byLegacyToken = $service->resolveIdentifier((string) $qrCode->token);

        $this->assertSame($qrCode->getKey(), $byAccount->getKey());
        $this->assertSame($qrCode->getKey(), $byLegacyToken->getKey());
    }

    public function test_identification_records_the_branch_and_increments_scan_count(): void
    {
        $customer = $this->customer();
        $branch = $this->branch('صالة المزة');
        $qrCode = $customer->qrCode()->firstOrFail();

        $log = app(CustomerQrScanService::class)->recordIdentification(
            identifier: (string) $customer->account_no,
            branch: $branch,
            data: ['reference_no' => 'LOOKUP-1001'],
        );

        $this->assertSame('identify', $log->action_type);
        $this->assertSame($branch->getKey(), (int) $log->branch_id);
        $this->assertSame($customer->account_no, $log->account_no);
        $this->assertSame(1, (int) $qrCode->refresh()->scan_count);
    }

    public function test_inactive_qr_cannot_identify_the_customer(): void
    {
        $customer = $this->customer();
        $branch = $this->branch();
        $customer->qrCode()->firstOrFail()->disable();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('QR معطل ولا يمكن استخدامه.');

        app(CustomerQrScanService::class)->recordIdentification(
            identifier: (string) $customer->account_no,
            branch: $branch,
        );
    }

    public function test_inactive_customer_cannot_be_used_in_a_hall_operation(): void
    {
        $customer = $this->customer();
        $branch = $this->branch();
        $customer->update(['status' => 'inactive']);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('حساب الزبون غير فعال.');

        app(CustomerQrScanService::class)->recordHallSale(
            identifier: (string) $customer->account_no,
            branch: $branch,
            data: [
                'reference_no' => 'SALE-INACTIVE',
                'sale_amount' => 1000,
            ],
        );
    }

    public function test_hall_sale_adds_points_from_the_net_amount_after_discount(): void
    {
        $this->loyaltySetting(0.01);
        $customer = $this->customer();
        $branch = $this->branch();

        $log = app(CustomerQrScanService::class)->recordHallSale(
            identifier: (string) $customer->account_no,
            branch: $branch,
            data: [
                'reference_no' => 'SALE-1001',
                'sale_amount' => 1000,
                'additional_discount_amount' => 100,
            ],
        );

        $this->assertEquals(1000.0, (float) $log->sale_amount);
        $this->assertEquals(100.0, (float) $log->discount_amount);
        $this->assertEquals(900.0, (float) $log->net_amount);
        $this->assertEquals(9.0, (float) $log->points_earned);
        $this->assertEquals(9.0, (float) $customer->loyaltyWallet()->firstOrFail()->points_balance);

        $this->assertDatabaseHas('customer_loyalty_transactions', [
            'customer_id' => $customer->getKey(),
            'type' => 'earn',
            'source_type' => 'qr_hall_sale',
            'source_id' => $log->getKey(),
            'reference_no' => 'SALE-1001',
            'points' => 9,
        ]);
    }

    public function test_in_store_point_voucher_is_applied_and_redeemed_once(): void
    {
        $this->loyaltySetting(0.01);
        $customer = $this->customer();
        $branch = $this->branch('صالة الشعلان');
        $redemption = $this->redemption($customer, $branch, 200, 50);

        $log = app(CustomerQrScanService::class)->recordHallSale(
            identifier: (string) $customer->account_no,
            branch: $branch,
            data: [
                'reference_no' => 'SALE-VOUCHER-1',
                'sale_amount' => 500,
                'additional_discount_amount' => 50,
                'point_voucher_code' => $redemption->order_no,
            ],
        );

        $this->assertEquals(250.0, (float) $log->discount_amount);
        $this->assertEquals(250.0, (float) $log->net_amount);
        $this->assertEquals(2.5, (float) $log->points_earned);
        $this->assertEquals(50.0, (float) $log->points_spent);
        $this->assertSame($redemption->getKey(), (int) $log->point_voucher_redemption_id);

        $redemption->refresh();
        $this->assertSame('redeemed', $redemption->status);
        $this->assertNotNull($redemption->applied_at);
        $this->assertSame('صالة الشعلان', $redemption->branch);
    }

    public function test_voucher_for_another_branch_rolls_back_the_entire_operation(): void
    {
        $this->loyaltySetting(0.01);
        $customer = $this->customer();
        $firstBranch = $this->branch('صالة أولى');
        $secondBranch = $this->branch('صالة ثانية');
        $redemption = $this->redemption($customer, $firstBranch, 100, 20);
        $qrCode = $customer->qrCode()->firstOrFail();

        try {
            app(CustomerQrScanService::class)->recordHallSale(
                identifier: (string) $customer->account_no,
                branch: $secondBranch,
                data: [
                    'reference_no' => 'SALE-WRONG-BRANCH',
                    'sale_amount' => 500,
                    'point_voucher_code' => $redemption->order_no,
                ],
            );

            $this->fail('The hall sale should have failed.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString(
                'صالة أو فرع مختلف',
                $exception->getMessage(),
            );
        }

        $this->assertSame('available', $redemption->refresh()->status);
        $this->assertSame(0, (int) $qrCode->refresh()->scan_count);
        $this->assertEquals(0.0, (float) $customer->loyaltyWallet()->firstOrFail()->points_balance);
        $this->assertDatabaseMissing('customer_qr_logs', [
            'reference_no' => 'SALE-WRONG-BRANCH',
        ]);
    }

    public function test_duplicate_branch_reference_cannot_add_points_twice(): void
    {
        $this->loyaltySetting(0.01);
        $customer = $this->customer();
        $branch = $this->branch();
        $service = app(CustomerQrScanService::class);

        $service->recordHallSale(
            identifier: (string) $customer->account_no,
            branch: $branch,
            data: [
                'reference_no' => 'DUPLICATE-100',
                'sale_amount' => 1000,
            ],
        );

        try {
            $service->recordHallSale(
                identifier: (string) $customer->account_no,
                branch: $branch,
                data: [
                    'reference_no' => 'DUPLICATE-100',
                    'sale_amount' => 1000,
                ],
            );

            $this->fail('The duplicate operation should have failed.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString(
                'تم تسجيل هذا المرجع',
                $exception->getMessage(),
            );
        }

        $this->assertEquals(10.0, (float) $customer->loyaltyWallet()->firstOrFail()->points_balance);
        $this->assertSame(1, CustomerLoyaltyTransaction::query()
            ->where('source_type', 'qr_hall_sale')
            ->where('reference_no', 'DUPLICATE-100')
            ->count());
    }

    public function test_online_voucher_cannot_be_applied_inside_a_hall(): void
    {
        $customer = $this->customer();
        $branch = $this->branch();
        $redemption = $this->redemption($customer, $branch, 100, 20, 'online');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('مخصصة للاستخدام عبر الموقع');

        app(CustomerQrScanService::class)->recordHallSale(
            identifier: (string) $customer->account_no,
            branch: $branch,
            data: [
                'reference_no' => 'SALE-ONLINE-VOUCHER',
                'sale_amount' => 500,
                'point_voucher_code' => $redemption->order_no,
            ],
        );
    }

    public function test_total_discounts_cannot_exceed_the_sale_amount(): void
    {
        $customer = $this->customer();
        $branch = $this->branch();
        $redemption = $this->redemption($customer, $branch, 80, 20);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('مجموع الحسومات لا يمكن أن يتجاوز قيمة الفاتورة');

        app(CustomerQrScanService::class)->recordHallSale(
            identifier: (string) $customer->account_no,
            branch: $branch,
            data: [
                'reference_no' => 'SALE-OVER-DISCOUNT',
                'sale_amount' => 100,
                'additional_discount_amount' => 30,
                'point_voucher_code' => $redemption->order_no,
            ],
        );
    }

    public function test_disabled_loyalty_records_the_sale_without_adding_points(): void
    {
        $setting = $this->loyaltySetting(0.01);
        $setting->update(['enabled' => false]);

        $customer = $this->customer();
        $branch = $this->branch();

        $log = app(CustomerQrScanService::class)->recordHallSale(
            identifier: (string) $customer->account_no,
            branch: $branch,
            data: [
                'reference_no' => 'SALE-NO-POINTS',
                'sale_amount' => 1000,
            ],
        );

        $this->assertEquals(0.0, (float) $log->points_earned);
        $this->assertEquals(0.0, (float) $customer->loyaltyWallet()->firstOrFail()->points_balance);
        $this->assertDatabaseMissing('customer_loyalty_transactions', [
            'source_type' => 'qr_hall_sale',
            'source_id' => $log->getKey(),
        ]);
    }

    protected function customer(): Customer
    {
        static $counter = 0;
        $counter++;

        return Customer::create([
            'account_no' => 'QR-ACCOUNT-' . $counter,
            'name' => 'زبون QR ' . $counter,
            'mobile' => '099900' . str_pad((string) $counter, 4, '0', STR_PAD_LEFT),
            'email' => 'qr-customer-' . $counter . '@example.test',
            'password' => 'password123',
            'status' => 'active',
        ]);
    }

    protected function branch(string $name = 'صالة دمشق'): Branch
    {
        static $counter = 0;
        $counter++;

        $category = BranchCategory::create([
            'name_ar' => 'تصنيف الصالات ' . $counter,
            'name_en' => 'Hall category ' . $counter,
            'status' => 'active',
        ]);

        return Branch::create([
            'branch_category_id' => $category->getKey(),
            'type' => 'hall',
            'name_ar' => $name,
            'name_en' => 'Hall ' . $counter,
            'slug' => 'qr-hall-' . $counter,
            'status' => 'active',
            'sort_order' => $counter,
        ]);
    }

    protected function loyaltySetting(float $rate): CustomerLoyaltySetting
    {
        $setting = CustomerLoyaltySetting::singleton();
        $setting->update([
            'enabled' => true,
            'award_on_status' => 'delivered',
            'points_base' => 'net_total',
            'points_per_currency' => $rate,
        ]);

        return $setting->refresh();
    }

    protected function redemption(
        Customer $customer,
        Branch $branch,
        float $value,
        float $pointsSpent,
        string $usageMethod = 'in_store',
    ): PointVoucherRedemption {
        static $counter = 0;
        $counter++;

        $voucher = PointsVoucher::create([
            'code' => 'QR-PV-' . $counter,
            'name' => 'قسيمة QR ' . $counter,
            'points_required' => $pointsSpent,
            'voucher_value' => $value,
            'usage_method' => $usageMethod,
            'branch' => $branch->name_ar,
            'valid_days' => 30,
            'status' => 'active',
        ]);

        return PointVoucherRedemption::create([
            'customer_id' => $customer->getKey(),
            'points_voucher_id' => $voucher->getKey(),
            'order_no' => 'PVR-QR-' . $counter,
            'customer_name' => $customer->name,
            'account_no' => $customer->account_no,
            'mobile' => $customer->mobile,
            'voucher_value' => $value,
            'points_spent' => $pointsSpent,
            'usage_method' => $usageMethod,
            'branch' => $branch->name_ar,
            'status' => 'available',
            'issued_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);
    }
}
