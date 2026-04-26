<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('customer_addresses');
        Schema::enableForeignKeyConstraints();

        Schema::create('customer_addresses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('label')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('mobile')->nullable();
            $table->string('city')->nullable();
            $table->string('area')->nullable();
            $table->text('address_line')->nullable();
            $table->string('address_type')->default('home');
            $table->boolean('is_default')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('customer_id');
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('customer_addresses');
        Schema::enableForeignKeyConstraints();
    }
};
