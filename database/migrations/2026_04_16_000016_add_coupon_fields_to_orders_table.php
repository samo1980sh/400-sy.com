<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            if (! Schema::hasColumn('orders', 'coupon_code_snapshot')) {
                $table->string('coupon_code_snapshot')->nullable()->after('payment_method');
            }

            if (! Schema::hasColumn('orders', 'coupon_discount_value')) {
                $table->decimal('coupon_discount_value', 12, 2)->default(0)->after('discount_value');
            }
        });

        Schema::table('coupon_redemptions', function (Blueprint $table): void {
            $table->unique('order_id', 'coupon_redemptions_order_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('coupon_redemptions', function (Blueprint $table): void {
            $table->dropUnique('coupon_redemptions_order_id_unique');
        });

        Schema::table('orders', function (Blueprint $table): void {
            if (Schema::hasColumn('orders', 'coupon_discount_value')) {
                $table->dropColumn('coupon_discount_value');
            }

            if (Schema::hasColumn('orders', 'coupon_code_snapshot')) {
                $table->dropColumn('coupon_code_snapshot');
            }
        });
    }
};
