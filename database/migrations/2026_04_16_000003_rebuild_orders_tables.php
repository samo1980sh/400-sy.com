<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('order_status_history');
        Schema::dropIfExists('order_addresses');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::enableForeignKeyConstraints();

        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->string('order_no')->unique();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedInteger('shipping_address_id')->nullable();
            $table->unsignedInteger('shipping_method_id')->nullable();

            $table->string('customer_name_snapshot')->nullable();
            $table->string('customer_mobile_snapshot')->nullable();
            $table->string('customer_email_snapshot')->nullable();
            $table->string('customer_account_no_snapshot')->nullable();

            $table->string('shipping_label_snapshot')->nullable();
            $table->string('shipping_contact_name_snapshot')->nullable();
            $table->string('shipping_mobile_snapshot')->nullable();
            $table->string('shipping_city_snapshot')->nullable();
            $table->string('shipping_area_snapshot')->nullable();
            $table->text('shipping_address_line_snapshot')->nullable();
            $table->string('shipping_address_type_snapshot')->nullable();

            $table->enum('status', ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'])->default('pending');
            $table->enum('payment_status', ['unpaid', 'paid'])->default('unpaid');
            $table->string('payment_method')->nullable();
            $table->string('branch')->nullable();
            $table->boolean('is_gift')->default(false);
            $table->text('gift_message')->nullable();

            $table->decimal('total_before_discount', 12, 2)->default(0);
            $table->decimal('discount_value', 12, 2)->default(0);
            $table->decimal('shipping_cost', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);

            $table->dateTime('confirmed_at')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->dateTime('shipped_at')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedInteger('product_id')->nullable();
            $table->unsignedInteger('product_variant_id')->nullable();

            $table->string('product_name_snapshot')->nullable();
            $table->string('product_model_no_snapshot')->nullable();
            $table->string('product_sku_snapshot')->nullable();
            $table->string('product_barcode_snapshot')->nullable();
            $table->string('color_name_snapshot')->nullable();
            $table->string('size_name_snapshot')->nullable();

            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('line_total', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('order_status_history');
        Schema::dropIfExists('order_addresses');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::enableForeignKeyConstraints();
    }
};
