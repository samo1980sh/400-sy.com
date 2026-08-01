<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->assertColumnsExist('products', [
            'show_web',
            'show_retail',
            'is_active',
            'deleted_at',
            'category_id',
        ]);
        $this->assertColumnsExist('product_colors', ['product_id', 'status']);
        $this->assertColumnsExist('product_variants', [
            'status',
            'quantity',
            'size_id',
            'product_id',
        ]);

        $this->createIndexIfMissing(
            'products',
            ['show_web', 'show_retail', 'is_active', 'deleted_at', 'category_id'],
            'products_front_visibility_category_index'
        );

        $this->createIndexIfMissing(
            'product_colors',
            ['product_id', 'status'],
            'product_colors_product_status_index'
        );

        $this->createIndexIfMissing(
            'product_variants',
            ['status', 'quantity', 'size_id', 'product_id'],
            'product_variants_active_available_size_index'
        );
    }

    public function down(): void
    {
        $this->dropIndexIfExists('product_variants', 'product_variants_active_available_size_index');
        $this->dropIndexIfExists('product_colors', 'product_colors_product_status_index');
        $this->dropIndexIfExists('products', 'products_front_visibility_category_index');
    }

    private function createIndexIfMissing(string $table, array $columns, string $index): void
    {
        if ($this->isPretending()) {
            Schema::table($table, function (Blueprint $blueprint) use ($columns, $index): void {
                $blueprint->index($columns, $index);
            });

            return;
        }

        if ($this->indexExists($table, $index) || $this->indexPrefixExists($table, $columns)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $index): void {
            $blueprint->index($columns, $index);
        });
    }

    private function dropIndexIfExists(string $table, string $index): void
    {
        if ($this->isPretending()) {
            Schema::table($table, function (Blueprint $blueprint) use ($index): void {
                $blueprint->dropIndex($index);
            });

            return;
        }

        if (! Schema::hasTable($table) || ! $this->indexExists($table, $index)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($index): void {
            $blueprint->dropIndex($index);
        });
    }

    private function assertColumnsExist(string $table, array $columns): void
    {
        if ($this->isPretending()) {
            return;
        }

        if (! Schema::hasTable($table)) {
            throw new \RuntimeException("Cannot create front filter indexes: table [{$table}] does not exist.");
        }

        foreach ($columns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                throw new \RuntimeException(
                    "Cannot create front filter indexes: column [{$table}.{$column}] does not exist."
                );
            }
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        $table = str_replace('`', '``', $table);

        return DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$index]) !== [];
    }

    private function indexPrefixExists(string $table, array $columns): bool
    {
        $table = str_replace('`', '``', $table);
        $indexes = [];

        foreach (DB::select("SHOW INDEX FROM `{$table}`") as $row) {
            $name = (string) $row->Key_name;
            $sequence = (int) $row->Seq_in_index;
            $indexes[$name][$sequence] = (string) $row->Column_name;
        }

        foreach ($indexes as $indexedColumns) {
            ksort($indexedColumns);
            $indexedColumns = array_values($indexedColumns);

            if (array_slice($indexedColumns, 0, count($columns)) === $columns) {
                return true;
            }
        }

        return false;
    }

    private function isPretending(): bool
    {
        $connection = DB::connection();

        return method_exists($connection, 'pretending') && $connection->pretending();
    }
};
