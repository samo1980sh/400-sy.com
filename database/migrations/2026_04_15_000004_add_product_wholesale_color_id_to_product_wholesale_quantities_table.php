<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('product_wholesale_quantities', 'product_wholesale_color_id')) {
            DB::statement('ALTER TABLE product_wholesale_quantities MODIFY product_wholesale_color_id INT UNSIGNED NULL');
        } else {
            Schema::table('product_wholesale_quantities', function (Blueprint $table): void {
                $table->unsignedInteger('product_wholesale_color_id')->nullable()->after('product_id');
            });
        }

        if (! DB::select('SHOW INDEX FROM product_wholesale_quantities WHERE Key_name = ?', ['idx_product_wholesale_quantities_color_id'])) {
            Schema::table('product_wholesale_quantities', function (Blueprint $table): void {
                $table->index('product_wholesale_color_id', 'idx_product_wholesale_quantities_color_id');
            });
        }

        if (DB::select('SHOW INDEX FROM product_wholesale_quantities WHERE Key_name = ?', ['uniq_product_wholesale_series'])) {
            DB::statement('ALTER TABLE product_wholesale_quantities DROP INDEX uniq_product_wholesale_series');
        }

        $seriesColorUniqueExists = DB::select(
            'SHOW INDEX FROM product_wholesale_quantities WHERE Key_name = ?',
            ['uniq_product_wholesale_series_color'],
        ) !== [];

        if (! $seriesColorUniqueExists) {
            Schema::table('product_wholesale_quantities', function (Blueprint $table): void {
                $table->unique(['product_id', 'product_wholesale_color_id', 'series_group', 'size_text'], 'uniq_product_wholesale_series_color');
            });
        }

        Schema::table('product_wholesale_quantities', function (Blueprint $table): void {
            $table->foreign('product_wholesale_color_id')
                ->references('id')
                ->on('product_wholesale_colors')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('product_wholesale_quantities', function (Blueprint $table): void {
            $table->dropForeign(['product_wholesale_color_id']);
        });

        Schema::table('product_wholesale_quantities', function (Blueprint $table): void {
            $table->dropUnique('uniq_product_wholesale_series_color');
        });

        Schema::table('product_wholesale_quantities', function (Blueprint $table): void {
            $table->dropIndex('idx_product_wholesale_quantities_color_id');
        });

        Schema::table('product_wholesale_quantities', function (Blueprint $table): void {
            $table->dropColumn('product_wholesale_color_id');
        });

        Schema::table('product_wholesale_quantities', function (Blueprint $table): void {
            $table->unique(['product_id', 'series_group', 'size_text'], 'uniq_product_wholesale_series');
        });
    }
};
