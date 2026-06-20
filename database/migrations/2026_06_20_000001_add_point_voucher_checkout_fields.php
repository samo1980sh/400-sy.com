<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            if (! Schema::hasColumn('orders', 'point_voucher_code_snapshot')) {
                $table->string('point_voucher_code_snapshot')->nullable()->after('coupon_code_snapshot');
            }

            if (! Schema::hasColumn('orders', 'point_voucher_discount_value')) {
                $table->decimal('point_voucher_discount_value', 12, 2)->default(0)->after('coupon_discount_value');
            }
        });

        Schema::table('point_voucher_redemptions', function (Blueprint $table): void {
            if (! Schema::hasColumn('point_voucher_redemptions', 'order_id')) {
                $table->foreignId('order_id')
                    ->nullable()
                    ->after('customer_id')
                    ->constrained('orders')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('point_voucher_redemptions', 'applied_at')) {
                $table->timestamp('applied_at')->nullable()->after('issued_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('point_voucher_redemptions', function (Blueprint $table): void {
            if (Schema::hasColumn('point_voucher_redemptions', 'order_id')) {
                $table->dropConstrainedForeignId('order_id');
            }

            if (Schema::hasColumn('point_voucher_redemptions', 'applied_at')) {
                $table->dropColumn('applied_at');
            }
        });

        Schema::table('orders', function (Blueprint $table): void {
            if (Schema::hasColumn('orders', 'point_voucher_code_snapshot')) {
                $table->dropColumn('point_voucher_code_snapshot');
            }

            if (Schema::hasColumn('orders', 'point_voucher_discount_value')) {
                $table->dropColumn('point_voucher_discount_value');
            }
        });
    }
};
