<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_complements')) {
            return;
        }

        Schema::create('product_complements', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('related_product_id');
            $table->unsignedTinyInteger('sort_order')->default(1);
            $table->timestamps();

            $table->foreign('product_id', 'product_complements_product_id_foreign')
                ->references('id')
                ->on('products')
                ->cascadeOnDelete();
            $table->foreign('related_product_id', 'product_complements_related_product_id_foreign')
                ->references('id')
                ->on('products')
                ->cascadeOnDelete();
            $table->unique(['product_id', 'related_product_id'], 'product_complements_unique');
            $table->index(['product_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_complements');
    }
};
