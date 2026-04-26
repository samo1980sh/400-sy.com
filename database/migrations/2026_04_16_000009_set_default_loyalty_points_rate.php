<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('customer_loyalty_settings')->exists()) {
            DB::table('customer_loyalty_settings')
                ->where('points_per_currency', 0)
                ->update([
                    'points_per_currency' => 0.001,
                    'award_on_status' => DB::raw("COALESCE(award_on_status, 'delivered')"),
                    'points_base' => DB::raw("COALESCE(points_base, 'net_total')"),
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        if (DB::table('customer_loyalty_settings')->exists()) {
            DB::table('customer_loyalty_settings')
                ->where('points_per_currency', 0.001)
                ->update([
                    'points_per_currency' => 0,
                    'updated_at' => now(),
                ]);
        }
    }
};
