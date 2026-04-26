<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_colors', function (Blueprint $table): void {
            $table->unsignedInteger('sort_order')->default(0)->after('status');
        });

        $rows = DB::table('product_colors')
            ->select(['id', 'product_id'])
            ->orderBy('product_id')
            ->orderBy('id')
            ->get()
            ->groupBy('product_id');

        foreach ($rows as $productRows) {
            foreach ($productRows->values() as $index => $row) {
                DB::table('product_colors')
                    ->where('id', $row->id)
                    ->update(['sort_order' => $index + 1]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('product_colors', function (Blueprint $table): void {
            $table->dropColumn('sort_order');
        });
    }
};
