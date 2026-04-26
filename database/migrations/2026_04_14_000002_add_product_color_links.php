<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->unsignedInteger('structure_color_id')->nullable()->after('structure');
        });

        Schema::table('product_variants', function (Blueprint $table): void {
            $table->unsignedInteger('product_color_id')->nullable()->after('product_id');
        });

        DB::statement('
            UPDATE product_variants pv
            INNER JOIN product_colors pc
                ON pc.product_id = pv.product_id
               AND pc.color_id = pv.color_id
            SET pv.product_color_id = pc.id
            WHERE pv.product_color_id IS NULL
        ');

        Schema::table('products', function (Blueprint $table): void {
            $table->foreign('structure_color_id', 'products_structure_color_id_foreign')
                ->references('id')
                ->on('colors')
                ->nullOnDelete();
        });

        Schema::table('product_variants', function (Blueprint $table): void {
            $table->foreign('product_color_id', 'product_variants_product_color_id_foreign')
                ->references('id')
                ->on('product_colors')
                ->cascadeOnDelete();

            $table->index(['product_id', 'product_color_id', 'size_id'], 'product_variants_product_color_size_idx');
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table): void {
            $table->dropIndex('product_variants_product_color_size_idx');
            $table->dropForeign('product_variants_product_color_id_foreign');
            $table->dropColumn('product_color_id');
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropForeign('products_structure_color_id_foreign');
            $table->dropColumn('structure_color_id');
        });
    }
};
