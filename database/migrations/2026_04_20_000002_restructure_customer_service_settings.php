<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_service_settings', function (Blueprint $table): void {
            if (! Schema::hasColumn('customer_service_settings', 'setting_key')) {
                $table->string('setting_key')->nullable()->unique()->after('id');
            }

            if (! Schema::hasColumn('customer_service_settings', 'title_ar')) {
                $table->string('title_ar')->nullable()->after('setting_key');
            }

            if (! Schema::hasColumn('customer_service_settings', 'title_en')) {
                $table->string('title_en')->nullable()->after('title_ar');
            }

            if (! Schema::hasColumn('customer_service_settings', 'content_ar')) {
                $table->longText('content_ar')->nullable()->after('title_en');
            }

            if (! Schema::hasColumn('customer_service_settings', 'content_en')) {
                $table->longText('content_en')->nullable()->after('content_ar');
            }

            if (! Schema::hasColumn('customer_service_settings', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('content_en');
            }

            if (! Schema::hasColumn('customer_service_settings', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('sort_order');
            }
        });

        $legacyRows = DB::table('customer_service_settings')
            ->orderBy('id')
            ->get();

        if ($legacyRows->isEmpty()) {
            DB::table('customer_service_settings')->insert([
                [
                    'setting_key' => 'membership_card',
                    'title_ar' => 'بطاقة العضوية',
                    'title_en' => 'Membership Card',
                    'content_ar' => null,
                    'content_en' => null,
                    'app_ios_url' => null,
                    'app_android_url' => null,
                    'app_direct_url' => null,
                    'sort_order' => 1,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'setting_key' => 'app_400',
                    'title_ar' => 'تطبيق 400',
                    'title_en' => '400 App',
                    'content_ar' => null,
                    'content_en' => null,
                    'app_ios_url' => null,
                    'app_android_url' => null,
                    'app_direct_url' => null,
                    'sort_order' => 2,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'setting_key' => 'terms',
                    'title_ar' => 'الشروط والأحكام',
                    'title_en' => 'Terms and Conditions',
                    'content_ar' => null,
                    'content_en' => null,
                    'app_ios_url' => null,
                    'app_android_url' => null,
                    'app_direct_url' => null,
                    'sort_order' => 3,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'setting_key' => 'exchange_policy',
                    'title_ar' => 'سياسة التبديل',
                    'title_en' => 'Exchange Policy',
                    'content_ar' => null,
                    'content_en' => null,
                    'app_ios_url' => null,
                    'app_android_url' => null,
                    'app_direct_url' => null,
                    'sort_order' => 4,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);

            return;
        }

        $legacy = $legacyRows->first();
        $now = now();

        DB::table('customer_service_settings')->delete();

        DB::table('customer_service_settings')->insert([
            [
                'setting_key' => 'membership_card',
                'title_ar' => 'بطاقة العضوية',
                'title_en' => 'Membership Card',
                'content_ar' => $legacy->membership_card_ar ?? null,
                'content_en' => $legacy->membership_card_en ?? null,
                'app_ios_url' => null,
                'app_android_url' => null,
                'app_direct_url' => null,
                'sort_order' => 1,
                'is_active' => true,
                'created_at' => $legacy->created_at ?? $now,
                'updated_at' => $legacy->updated_at ?? $now,
            ],
            [
                'setting_key' => 'app_400',
                'title_ar' => 'تطبيق 400',
                'title_en' => '400 App',
                'content_ar' => $legacy->app_details_ar ?? null,
                'content_en' => $legacy->app_details_en ?? null,
                'app_ios_url' => $legacy->app_ios_url ?? null,
                'app_android_url' => $legacy->app_android_url ?? null,
                'app_direct_url' => $legacy->app_direct_url ?? null,
                'sort_order' => 2,
                'is_active' => true,
                'created_at' => $legacy->created_at ?? $now,
                'updated_at' => $legacy->updated_at ?? $now,
            ],
            [
                'setting_key' => 'terms',
                'title_ar' => 'الشروط والأحكام',
                'title_en' => 'Terms and Conditions',
                'content_ar' => $legacy->terms_ar ?? null,
                'content_en' => $legacy->terms_en ?? null,
                'app_ios_url' => null,
                'app_android_url' => null,
                'app_direct_url' => null,
                'sort_order' => 3,
                'is_active' => true,
                'created_at' => $legacy->created_at ?? $now,
                'updated_at' => $legacy->updated_at ?? $now,
            ],
            [
                'setting_key' => 'exchange_policy',
                'title_ar' => 'سياسة التبديل',
                'title_en' => 'Exchange Policy',
                'content_ar' => $legacy->exchange_policy_ar ?? null,
                'content_en' => $legacy->exchange_policy_en ?? null,
                'app_ios_url' => null,
                'app_android_url' => null,
                'app_direct_url' => null,
                'sort_order' => 4,
                'is_active' => true,
                'created_at' => $legacy->created_at ?? $now,
                'updated_at' => $legacy->updated_at ?? $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::table('customer_service_settings', function (Blueprint $table): void {
            if (Schema::hasColumn('customer_service_settings', 'setting_key')) {
                $table->dropUnique(['setting_key']);
                $table->dropColumn('setting_key');
            }

            foreach (['title_ar', 'title_en', 'content_ar', 'content_en', 'sort_order', 'is_active'] as $column) {
                if (Schema::hasColumn('customer_service_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
