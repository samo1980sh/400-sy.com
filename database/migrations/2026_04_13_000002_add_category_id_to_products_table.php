<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('products', 'category_id')) {
            Schema::table('products', function (Blueprint $table): void {
                $table->dropColumn('category_id');
            });
        }

        Schema::table('products', function (Blueprint $table): void {
            $table->unsignedInteger('category_id')
                ->nullable()
                ->after('model_no')
                ->index();
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->foreign('category_id', 'products_category_id_foreign')
                ->references('id')
                ->on('categories')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropForeign('products_category_id_foreign');
            $table->dropColumn('category_id');
        });
    }
};
