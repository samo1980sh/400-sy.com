<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('trader_order_items');
        Schema::dropIfExists('trader_orders');

        Schema::create('trader_orders', function (Blueprint $table): void {
            $table->id();
            $table->string('order_no')->unique();
            $table->unsignedBigInteger('trader_id');

            $table->string('trader_name_snapshot')->nullable();
            $table->string('trader_mobile_snapshot')->nullable();
            $table->string('trader_account_no_snapshot')->nullable();
            $table->string('trader_group_snapshot')->nullable();
            $table->string('shipping_contact_name_snapshot')->nullable();
            $table->string('shipping_mobile_snapshot')->nullable();
            $table->string('shipping_city_snapshot')->nullable();
            $table->string('shipping_area_snapshot')->nullable();
            $table->text('shipping_address_line_snapshot')->nullable();

            $table->enum('status', ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'])->default('pending');
            $table->enum('payment_status', ['unpaid', 'paid'])->default('unpaid');
            $table->string('payment_method')->nullable();
            $table->string('branch')->nullable();

            $table->decimal('total_before_discount', 12, 2)->default(0);
            $table->decimal('discount_value', 12, 2)->default(0);
            $table->decimal('shipping_cost', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);

            $table->dateTime('confirmed_at')->nullable();
            $table->dateTime('shipped_at')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('trader_id', 'fk_trader_orders_trader')
                ->references('id')
                ->on('traders')
                ->cascadeOnDelete();
        });

        Schema::create('trader_order_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('trader_order_id');
            $table->unsignedInteger('product_id')->nullable();
            $table->unsignedInteger('product_wholesale_color_id')->nullable();

            $table->unsignedInteger('series_group')->nullable();
            $table->string('size_text')->nullable();

            $table->string('product_name_snapshot')->nullable();
            $table->string('product_model_no_snapshot')->nullable();
            $table->string('product_sku_snapshot')->nullable();
            $table->string('product_barcode_snapshot')->nullable();
            $table->string('color_name_snapshot')->nullable();
            $table->string('series_snapshot')->nullable();

            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('line_total', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('trader_order_id', 'fk_trader_order_items_order')
                ->references('id')
                ->on('trader_orders')
                ->cascadeOnDelete();
            $table->foreign('product_id', 'fk_trader_order_items_product')
                ->references('id')
                ->on('products')
                ->nullOnDelete();
            $table->foreign('product_wholesale_color_id', 'fk_trader_order_items_color')
                ->references('id')
                ->on('product_wholesale_colors')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trader_order_items');
        Schema::dropIfExists('trader_orders');
    }
};
