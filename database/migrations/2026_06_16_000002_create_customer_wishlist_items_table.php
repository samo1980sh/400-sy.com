<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('customer_wishlist_items')) {
            return;
        }

        Schema::create('customer_wishlist_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->unsignedInteger('product_id');
            $table->timestamps();

            $table->unique(['customer_id', 'product_id'], 'customer_wishlist_unique');
            $table->index('product_id', 'customer_wishlist_product_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_wishlist_items');
    }
};
