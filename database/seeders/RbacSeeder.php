<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RbacSeeder extends Seeder
{
    public function run(): void
    {
        $resources = [
            'customers' => 'الزبائن',
            'customer-groups.retail' => 'فئات المفرق',
            'customer-groups.wholesale' => 'فئات التاجر',
            'traders' => 'التجار',
            'trader-orders' => 'طلبات التجار',
            'customer-addresses' => 'عناوين الزبائن',
            'customer-loyalty-wallets' => 'الولاءات والنقاط',
            'customer-loyalty-transactions' => 'سجل النقاط',
            'customer-loyalty-settings' => 'إعدادات الولاء',
            'gift-cards' => 'بطاقات الهدايا',
            'gift-card-redemptions' => 'سجل بطاقات الهدايا',
            'points-vouchers' => 'قسائم النقاط',
            'point-voucher-redemptions' => 'سجل قسائم النقاط',
            'coupon-settings' => 'إعدادات الكوبونات',
            'customer-service-settings' => 'خدمة الزبائن',
            'customer-service-faqs' => 'الأسئلة الشائعة',
            'coupons' => 'الكوبونات',
            'coupon-redemptions' => 'سجل الكوبونات',
            'customer-qr-codes' => 'QR Code',
            'customer-qr-logs' => 'سجل QR',
            'company-pages' => 'حول الشركة',
            'company-news-items' => 'الأخبار والأحداث',
            'company-header-images' => 'صور الهيدر العام',
            'company-news-ticker-items' => 'الشريط الإخباري المتحرك',
            'company-social-links' => 'روابط التواصل الاجتماعي',
            'internal-page-headers' => 'هيدرات الصفحات الداخلية',
            'orders' => 'الطلبات',
            'shipping-methods' => 'طرق الشحن',
            'payment-methods' => 'طرق الدفع',
            'import-batches' => 'دفعات الاستيراد',
            'import-rows' => 'أسطر الاستيراد',
            'exchange-rate-settings' => 'سعر الصرف',
            'contact-info-settings' => 'معلومات الاتصال العامة',
            'job-vacancies' => 'التوظيف',
            'agency-request-pages' => 'طلب وكالة',
            'warehouse-users' => 'مستخدمو المستودع',
            'warehouse-halls' => 'صالات المستودع',
            'warehouse-inventory' => 'مخزون المستودع',
            'products' => 'المنتجات',
            'categories' => 'التصنيفات',
            'branch-categories' => 'تصنيفات الأفرع',
            'branches' => 'الأفرع والصالات',
            'colors' => 'الألوان',
            'sizes' => 'القياسات',
            'measurement-charts' => 'زمر وحدة القياس',
            'product-variants' => 'توافر القياسات',
            'product-wholesale-availabilities' => 'توافر التاجر',
            'rbac.users' => 'المستخدمون',
            'rbac.roles' => 'الأدوار',
            'rbac.permissions' => 'الصلاحيات',
        ];

        $abilities = [
            'view-any' => 'عرض',
            'create' => 'إنشاء',
            'update' => 'تعديل',
            'delete' => 'حذف',
        ];

        $permissions = collect();

        foreach ($resources as $prefix => $label) {
            foreach ($abilities as $ability => $abilityLabel) {
                $slug = $prefix . '.' . $ability;

                $permissions->push(
                    Permission::query()->updateOrCreate(
                        ['slug' => $slug],
                        [
                            'name' => trim($label . ' ' . $abilityLabel),
                            'group' => $label,
                            'description' => null,
                            'is_active' => true,
                        ]
                    )
                );
            }
        }

        $role = Role::query()->updateOrCreate(
            ['slug' => 'super-admin'],
            [
                'name' => 'Super Admin',
                'description' => 'Full access to all modules.',
                'is_active' => true,
            ]
        );

        $role->permissions()->sync($permissions->pluck('id')->all());
    }
}
