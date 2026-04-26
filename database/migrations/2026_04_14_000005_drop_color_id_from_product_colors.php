<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_colors', function (Blueprint $table): void {
            $table->dropUnique('product_colors_product_color_unique');
            $table->dropForeign('product_colors_color_id_foreign');
            $table->dropColumn('color_id');
            $table->unique(['product_id', 'color_code'], 'product_colors_product_code_unique');
        });
    }

    public function down(): void
    {
        Schema::table('product_colors', function (Blueprint $table): void {
            $table->unsignedInteger('color_id')->after('product_id');
            $table->unique(['product_id', 'color_id'], 'product_colors_product_color_unique');
            $table->foreign('color_id', 'product_colors_color_id_foreign')
                ->references('id')
                ->on('colors')
                ->restrictOnDelete();
            $table->dropUnique('product_colors_product_code_unique');
        });
    }
};
