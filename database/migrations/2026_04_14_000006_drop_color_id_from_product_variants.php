<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $table = 'product_variants';

        if (Schema::hasColumn($table, 'color_id')) {
            $constraints = DB::select(
                'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL',
                [$table, 'color_id']
            );

            foreach ($constraints as $constraint) {
                DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$constraint->CONSTRAINT_NAME}`");
            }

            $indexes = DB::select(
                'SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
                [$table, 'color_id']
            );

            foreach ($indexes as $index) {
                if ($index->INDEX_NAME === 'PRIMARY') {
                    continue;
                }

                DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$index->INDEX_NAME}`");
            }

            DB::statement("ALTER TABLE `{$table}` DROP COLUMN `color_id`");
        }

        $uniqueExists = DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::raw('DATABASE()'))
            ->where('TABLE_NAME', $table)
            ->where('INDEX_NAME', 'uniq_product_color_size')
            ->exists();

        if (! $uniqueExists && Schema::hasTable($table)) {
            Schema::table($table, function (Blueprint $table): void {
                $table->unique(['product_id', 'product_color_id', 'size_id'], 'uniq_product_color_size');
            });
        }
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table): void {
            $table->unsignedInteger('color_id')->nullable()->after('product_color_id');
            $table->unique(['product_id', 'product_color_id', 'size_id'], 'uniq_product_color_size');
        });
    }
};
