<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('shipping_methods')) {
            return;
        }

        Schema::create('shipping_methods', function (Blueprint $table): void {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->string('code')->unique();
            $table->decimal('cost', 12, 2)->default(0);
            $table->string('delivery_time')->nullable();
            $table->boolean('active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_methods');
    }
};
