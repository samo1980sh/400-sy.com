<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! $this->indexExists('products', 'products_collection_filter_index')) {
            Schema::table('products', function (Blueprint $table): void {
                $table->index('collection', 'products_collection_filter_index');
            });
        }

        if (! $this->indexExists('products', 'products_special_offer_filter_index')) {
            Schema::table('products', function (Blueprint $table): void {
                $table->index('is_special_offer', 'products_special_offer_filter_index');
            });
        }
    }

    public function down(): void
    {
        if ($this->indexExists('products', 'products_special_offer_filter_index')) {
            Schema::table('products', function (Blueprint $table): void {
                $table->dropIndex('products_special_offer_filter_index');
            });
        }

        if ($this->indexExists('products', 'products_collection_filter_index')) {
            Schema::table('products', function (Blueprint $table): void {
                $table->dropIndex('products_collection_filter_index');
            });
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        $table = str_replace('`', '``', $table);

        return DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$index]) !== [];
    }
};
