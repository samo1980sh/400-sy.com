<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table): void {
            $table->unsignedInteger('stock_deducted_quantity')
                ->default(0)
                ->after('approved_quantity');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->dateTime('stock_deducted_at')
                ->nullable()
                ->after('cancelled_at');

            $table->dateTime('stock_restored_at')
                ->nullable()
                ->after('stock_deducted_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn([
                'stock_deducted_at',
                'stock_restored_at',
            ]);
        });

        Schema::table('order_items', function (Blueprint $table): void {
            $table->dropColumn('stock_deducted_quantity');
        });
    }
};
