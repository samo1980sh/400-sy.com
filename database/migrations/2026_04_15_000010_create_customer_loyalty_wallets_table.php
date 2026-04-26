<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('customer_loyalty_wallets')) {
            return;
        }

        Schema::create('customer_loyalty_wallets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('points_balance', 12, 2)->default(0);
            $table->decimal('points_earned_total', 12, 2)->default(0);
            $table->decimal('points_spent_total', 12, 2)->default(0);
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_loyalty_wallets');
    }
};
