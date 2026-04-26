<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('product_wholesale_quantities', 'series_group')) {
            Schema::table('product_wholesale_quantities', function (Blueprint $table): void {
                $table->unsignedSmallInteger('series_group')->default(1)->after('product_id');
            });
        }

        $productIdIndexExists = DB::select(
            'SHOW INDEX FROM product_wholesale_quantities WHERE Key_name = ?',
            ['idx_product_wholesale_quantities_product_id'],
        ) !== [];

        if (! $productIdIndexExists) {
            Schema::table('product_wholesale_quantities', function (Blueprint $table): void {
                $table->index('product_id', 'idx_product_wholesale_quantities_product_id');
            });
        }

        $legacyUniqueExists = DB::select(
            'SHOW INDEX FROM product_wholesale_quantities WHERE Key_name = ?',
            ['uniq_product_wholesale_size'],
        ) !== [];

        if ($legacyUniqueExists) {
            DB::statement('ALTER TABLE product_wholesale_quantities DROP INDEX uniq_product_wholesale_size');
        }

        $newUniqueExists = DB::select(
            'SHOW INDEX FROM product_wholesale_quantities WHERE Key_name = ?',
            ['uniq_product_wholesale_series'],
        ) !== [];

        if (! $newUniqueExists) {
            Schema::table('product_wholesale_quantities', function (Blueprint $table): void {
                $table->unique(['product_id', 'series_group', 'size_text'], 'uniq_product_wholesale_series');
            });
        }
    }

    public function down(): void
    {
        Schema::table('product_wholesale_quantities', function (Blueprint $table): void {
            $table->dropUnique('uniq_product_wholesale_series');
            $table->dropIndex('idx_product_wholesale_quantities_product_id');
        });

        Schema::table('product_wholesale_quantities', function (Blueprint $table): void {
            $table->dropColumn('series_group');
        });

        Schema::table('product_wholesale_quantities', function (Blueprint $table): void {
            $table->unique(['product_id', 'size_text'], 'uniq_product_wholesale_size');
        });
    }
};
