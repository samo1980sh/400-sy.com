<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('product_wholesale_quantities');

        Schema::create('product_wholesale_quantities', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('product_id');
            $table->string('size_text', 100);
            $table->integer('quantity')->default(0);
            $table->text('source_value')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'size_text'], 'uniq_product_wholesale_size');
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_wholesale_quantities');
    }
};
