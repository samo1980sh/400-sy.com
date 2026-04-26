<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_users', function (Blueprint $table): void {
            $table->id();
            $table->string('account_no')->unique()->nullable();
            $table->string('name');
            $table->string('mobile')->unique();
            $table->string('secondary_mobile')->nullable();
            $table->string('email')->unique()->nullable();
            $table->string('password')->nullable();
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('warehouse_halls', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('code')->nullable()->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('warehouse_inventory_items', function (Blueprint $table): void {
            $table->id();
            $table->string('country')->nullable();
            $table->string('model_code')->nullable();
            $table->string('barcode')->nullable();
            $table->string('short_code')->unique();
            $table->string('item_name')->nullable();
            $table->string('size_code')->nullable();
            $table->string('color_name')->nullable();
            $table->string('color_code')->nullable();
            $table->decimal('card_price', 12, 2)->default(0);
            $table->decimal('discount_rate', 8, 4)->default(0);
            $table->decimal('sale_price', 12, 2)->default(0);
            $table->decimal('warehouse_stock', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('warehouse_inventory_balances', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('warehouse_inventory_item_id');
            $table->unsignedBigInteger('warehouse_hall_id');
            $table->decimal('quantity', 12, 2)->default(0);
            $table->timestamps();

            $table->foreign('warehouse_inventory_item_id', 'fk_wib_item')
                ->references('id')
                ->on('warehouse_inventory_items')
                ->cascadeOnDelete();
            $table->foreign('warehouse_hall_id', 'fk_wib_hall')
                ->references('id')
                ->on('warehouse_halls')
                ->cascadeOnDelete();

            $table->unique(['warehouse_inventory_item_id', 'warehouse_hall_id'], 'uniq_warehouse_inventory_balance');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_inventory_balances');
        Schema::dropIfExists('warehouse_inventory_items');
        Schema::dropIfExists('warehouse_halls');
        Schema::dropIfExists('warehouse_users');
    }
};
