<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table): void {
            $table->unsignedInteger('approved_quantity')
                ->nullable()
                ->after('quantity');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->decimal('requested_total_before_discount', 12, 2)
                ->nullable()
                ->after('total_before_discount');

            $table->decimal('requested_total', 12, 2)
                ->nullable()
                ->after('total');
        });

        $completedOrderIds = DB::table('orders')
            ->whereIn('status', ['confirmed', 'shipped', 'delivered'])
            ->pluck('id');

        if ($completedOrderIds->isNotEmpty()) {
            DB::table('order_items')
                ->whereIn('order_id', $completedOrderIds)
                ->whereNull('approved_quantity')
                ->update([
                    'approved_quantity' => DB::raw('quantity'),
                ]);
        }

        DB::table('orders')
            ->whereIn('status', ['confirmed', 'shipped', 'delivered'])
            ->whereNull('requested_total_before_discount')
            ->update([
                'requested_total_before_discount' => DB::raw('total_before_discount'),
                'requested_total' => DB::raw('total'),
            ]);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn([
                'requested_total_before_discount',
                'requested_total',
            ]);
        });

        Schema::table('order_items', function (Blueprint $table): void {
            $table->dropColumn('approved_quantity');
        });
    }
};
