<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('product_details');

        Schema::create('product_details', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('product_id');
            $table->string('label_ar')->nullable();
            $table->string('label_en')->nullable();
            $table->text('value_ar')->nullable();
            $table->text('value_en')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('product_id');
            $table->index('is_active');
            $table->index('sort_order');
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_details');
    }
};
