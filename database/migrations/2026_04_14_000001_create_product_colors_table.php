<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_colors', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('color_id');
            $table->string('color_code', 50)->nullable();
            $table->string('color_name_ar', 255)->nullable();
            $table->string('color_name_en', 255)->nullable();
            $table->unique(['product_id', 'color_id'], 'product_colors_product_color_unique');
            $table->index(['product_id', 'color_code'], 'product_colors_product_code_index');
            $table->foreign('product_id', 'product_colors_product_id_foreign')
                ->references('id')
                ->on('products')
                ->cascadeOnDelete();
            $table->foreign('color_id', 'product_colors_color_id_foreign')
                ->references('id')
                ->on('colors')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_colors');
    }
};
