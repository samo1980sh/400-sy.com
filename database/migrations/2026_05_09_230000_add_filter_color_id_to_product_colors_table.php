<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('product_colors', 'filter_color_id')) {
            return;
        }

        $colorsIdColumn = DB::selectOne("SHOW COLUMNS FROM colors WHERE Field = 'id'");
        $colorsIdType = strtolower((string) ($colorsIdColumn->Type ?? ''));

        Schema::table('product_colors', function (Blueprint $table) use ($colorsIdType): void {
            if (str_contains($colorsIdType, 'bigint')) {
                $table->unsignedBigInteger('filter_color_id')->nullable()->after('product_id');
            } else {
                $table->unsignedInteger('filter_color_id')->nullable()->after('product_id');
            }

            $table->index(['filter_color_id', 'status'], 'product_colors_filter_status_index');
        });

        Schema::table('product_colors', function (Blueprint $table): void {
            $table
                ->foreign('filter_color_id', 'product_colors_filter_color_id_foreign')
                ->references('id')
                ->on('colors')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('product_colors', 'filter_color_id')) {
            return;
        }

        Schema::table('product_colors', function (Blueprint $table): void {
            try {
                $table->dropForeign('product_colors_filter_color_id_foreign');
            } catch (Throwable $e) {
                // Foreign key may not exist if the previous migration failed midway.
            }

            try {
                $table->dropIndex('product_colors_filter_status_index');
            } catch (Throwable $e) {
                // Index may not exist if the previous migration failed midway.
            }

            $table->dropColumn('filter_color_id');
        });
    }
};
