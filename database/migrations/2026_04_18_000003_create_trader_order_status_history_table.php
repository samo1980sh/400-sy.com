<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('trader_order_status_history');

        Schema::create('trader_order_status_history', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('trader_order_id');
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->string('from_payment_status')->nullable();
            $table->string('to_payment_status')->nullable();
            $table->text('note')->nullable();
            $table->unsignedInteger('changed_by')->nullable();
            $table->timestamps();

            $table->foreign('trader_order_id', 'fk_tosh_order')
                ->references('id')
                ->on('trader_orders')
                ->cascadeOnDelete();
            $table->foreign('changed_by', 'fk_tosh_user')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trader_order_status_history');
    }
};
