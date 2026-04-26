<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('traders')) {
            return;
        }

        Schema::create('traders', function (Blueprint $table): void {
            $table->id();
            $table->string('account_no')->unique()->nullable();
            $table->string('name');
            $table->string('mobile')->unique();
            $table->string('secondary_mobile')->nullable();
            $table->string('email')->unique()->nullable();
            $table->unsignedInteger('wholesale_customer_group_id')->nullable();
            $table->string('city')->nullable();
            $table->string('area')->nullable();
            $table->text('address_line')->nullable();
            $table->string('password')->nullable();
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('traders');
    }
};
            $table->foreign('wholesale_customer_group_id', 'fk_traders_wholesale_customer_group')
                ->references('id')
                ->on('wholesale_customer_groups')
                ->nullOnDelete();
