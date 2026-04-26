<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('retail_customer_groups')) {
            Schema::create('retail_customer_groups', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('name', 150)->unique();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('wholesale_customer_groups')) {
            Schema::create('wholesale_customer_groups', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('name_ar', 150);
                $table->string('name_en', 150)->nullable();
                $table->string('code', 50)->nullable()->unique();
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->integer('sort_order')->default(0);
                $table->timestamp('created_at')->useCurrent();
            });
        }

        Schema::dropIfExists('product_retail_group_assignments');
        Schema::create('product_retail_group_assignments', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('retail_customer_group_id');
            $table->timestamps();

            $table->foreign('product_id', 'fk_prga_product')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('retail_customer_group_id', 'fk_prga_group')->references('id')->on('retail_customer_groups')->cascadeOnDelete();
            $table->unique(['product_id', 'retail_customer_group_id'], 'uniq_product_retail_group_assignment');
        });

        Schema::dropIfExists('product_wholesale_group_assignments');
        Schema::create('product_wholesale_group_assignments', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('wholesale_customer_group_id');
            $table->timestamps();

            $table->foreign('product_id', 'fk_pwga_product')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('wholesale_customer_group_id', 'fk_pwga_group')->references('id')->on('wholesale_customer_groups')->cascadeOnDelete();
            $table->unique(['product_id', 'wholesale_customer_group_id'], 'uniq_product_wholesale_group_assignment');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_wholesale_group_assignments');
        Schema::dropIfExists('product_retail_group_assignments');
        Schema::dropIfExists('wholesale_customer_groups');
        Schema::dropIfExists('retail_customer_groups');
    }
};
