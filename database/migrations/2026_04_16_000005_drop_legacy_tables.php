<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'activity_log',
            'branches',
            'branches_images',
            'campaign_discounts',
            'campaign_products',
            'campaigns',
            'contact_info',
            'coupon_users',
            'coupons',
            'countries',
            'currencies',
            'discounts',
            'home_banners',
            'newsletter_subscribers',
            'notifications',
            'page_heroes',
            'pages',
            'product_categories',
            'reviews',
            'settings',
            'sliders',
            'social_links',
            'stock_movements',
            'system_errors',
            'top_bar',
            'user_roles',
            'user_tokens',
            'variant_discounts',
            'wishlist',
        ];

        Schema::disableForeignKeyConstraints();

        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // Legacy tables are intentionally not restored.
    }
};
