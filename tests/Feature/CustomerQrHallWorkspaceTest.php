<?php

namespace Tests\Feature;

use App\Filament\Pages\HallQrWorkspace;
use App\Filament\Resources\CustomerLoyaltySettings\CustomerLoyaltySettingResource;
use App\Filament\Resources\CustomerLoyaltyTransactions\CustomerLoyaltyTransactionResource;
use App\Filament\Resources\CustomerLoyaltyWallets\CustomerLoyaltyWalletResource;
use App\Filament\Resources\CustomerQrCodes\CustomerQrCodeResource;
use App\Filament\Resources\CustomerQrLogs\CustomerQrLogResource;
use App\Filament\Resources\PointVoucherRedemptions\PointVoucherRedemptionResource;
use App\Filament\Resources\PointsVouchers\PointsVoucherResource;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use ReflectionClass;
use Tests\TestCase;

class CustomerQrHallWorkspaceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_authorized_staff_can_access_the_hall_workspace(): void
    {
        $this->actingAs($this->superAdmin());

        $this->assertTrue(HallQrWorkspace::canAccess());
    }

    public function test_hall_workspace_renders_as_a_native_filament_form(): void
    {
        $this->actingAs($this->superAdmin());

        Livewire::test(HallQrWorkspace::class)
            ->assertStatus(200)
            ->assertSee('التعرف على حساب الزبون')
            ->assertSee('الصالة / الفرع')
            ->assertSee('QR أو رقم حساب الزبون')
            ->assertSee('التعرف على الحساب')
            ->assertDontSee('لا يوجد حساب محدد بعد')
            ->assertDontSee('تسجيل فاتورة الصالة');
    }

    public function test_inactive_or_customer_user_cannot_access_the_hall_workspace(): void
    {
        $inactive = User::create([
            'name' => 'موظف غير فعال',
            'email' => 'inactive-hall-workspace@example.test',
            'password' => 'password123',
            'status' => 'inactive',
            'user_type' => 'staff',
        ]);

        $this->actingAs($inactive);
        $this->assertFalse(HallQrWorkspace::canAccess());

        $customerUser = User::create([
            'name' => 'حساب غير موظف',
            'email' => 'customer-hall-workspace@example.test',
            'password' => 'password123',
            'status' => 'active',
            'user_type' => 'customer',
        ]);

        $this->actingAs($customerUser);
        $this->assertFalse(HallQrWorkspace::canAccess());
    }

    public function test_loyalty_resources_use_the_standard_navigation_group_without_a_custom_parent_page(): void
    {
        foreach ([
            CustomerLoyaltySettingResource::class,
            CustomerLoyaltyWalletResource::class,
            CustomerLoyaltyTransactionResource::class,
            PointsVoucherResource::class,
            PointVoucherRedemptionResource::class,
            CustomerQrCodeResource::class,
            CustomerQrLogResource::class,
        ] as $resource) {
            $reflection = new ReflectionClass($resource);
            $groupProperty = $reflection->getProperty('navigationGroup');
            $groupProperty->setAccessible(true);

            $this->assertSame(
                'الولاء والنقاط و QR',
                $groupProperty->getValue(),
                $resource,
            );

            $parentProperty = $reflection->getProperty('navigationParentItem');
            $parentProperty->setAccessible(true);

            $this->assertNull(
                $parentProperty->getValue(),
                $resource,
            );
        }
    }

    public function test_workspace_blade_only_renders_the_native_filament_schema(): void
    {
        $workspaceView = file_get_contents(
            resource_path('views/filament/pages/hall-qr-workspace.blade.php')
        );

        $this->assertIsString($workspaceView);
        $this->assertStringContainsString('{{ $this->form }}', $workspaceView);
        $this->assertStringNotContainsString('<style>', $workspaceView);
        $this->assertStringNotContainsString('<x-filament::section', $workspaceView);
        $this->assertStringNotContainsString('grid gap-', $workspaceView);
        $this->assertFileDoesNotExist(
            app_path('Filament/Pages/LoyaltyManagement.php')
        );
        $this->assertFileDoesNotExist(
            resource_path('views/filament/pages/loyalty-management.blade.php')
        );
    }

    protected function superAdmin(): User
    {
        $user = User::create([
            'name' => 'مدير شاشة QR',
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password123',
            'status' => 'active',
            'user_type' => 'staff',
        ]);

        $role = Role::query()->firstOrCreate(
            ['slug' => 'super-admin'],
            [
                'name' => 'super-admin',
                'description' => 'Super administrator',
                'is_active' => true,
            ],
        );

        $user->roles()->syncWithoutDetaching([$role->getKey()]);

        return $user;
    }
}
