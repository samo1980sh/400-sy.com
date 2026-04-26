<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_wholesale_colors')) {
            Schema::create('product_wholesale_colors', function (Blueprint $table): void {
                $table->increments('id');
                $table->unsignedInteger('product_id');
                $table->string('color_code', 100)->nullable();
                $table->string('color_name_ar', 150)->nullable();
                $table->string('color_name_en', 150)->nullable();
                $table->timestamps();

                $table->foreign('product_id', 'fk_pwc_product')->references('id')->on('products')->cascadeOnDelete();
                $table->unique(['product_id', 'color_code'], 'uniq_product_wholesale_color_code');
            });
        }

        Schema::dropIfExists('product_wholesale_availabilities');
        Schema::create('product_wholesale_availabilities', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('product_wholesale_color_id');
            $table->unsignedInteger('wholesale_customer_group_id');
            $table->integer('max_quantity')->default(0);
            $table->timestamps();

            $table->foreign('product_id', 'fk_pwa_product')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('product_wholesale_color_id', 'fk_pwa_color')->references('id')->on('product_wholesale_colors')->cascadeOnDelete();
            $table->foreign('wholesale_customer_group_id', 'fk_pwa_group')->references('id')->on('wholesale_customer_groups')->cascadeOnDelete();
            $table->unique(
                ['product_id', 'product_wholesale_color_id', 'wholesale_customer_group_id'],
                'uniq_product_wholesale_availability'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_wholesale_availabilities');
        Schema::dropIfExists('product_wholesale_colors');
    }
};
