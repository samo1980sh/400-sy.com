<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->index('deleted_at', 'products_deleted_at_index');
            $table->index('is_active', 'products_is_active_index');
            $table->index('show_web', 'products_show_web_index');
            $table->index('show_app', 'products_show_app_index');
            $table->index('show_retail', 'products_show_retail_index');
            $table->index('show_wholesale', 'products_show_wholesale_index');
            $table->index('is_new', 'products_is_new_index');
            $table->index('is_special_offer', 'products_is_special_offer_index');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropIndex('products_deleted_at_index');
            $table->dropIndex('products_is_active_index');
            $table->dropIndex('products_show_web_index');
            $table->dropIndex('products_show_app_index');
            $table->dropIndex('products_show_retail_index');
            $table->dropIndex('products_show_wholesale_index');
            $table->dropIndex('products_is_new_index');
            $table->dropIndex('products_is_special_offer_index');
        });
    }
};
