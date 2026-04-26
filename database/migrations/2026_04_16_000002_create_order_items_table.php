<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('order_items')) {
            Schema::create('order_items', function (Blueprint $table): void {
                $table->id();
                $table->unsignedInteger('order_id');
                $table->unsignedInteger('variant_id')->nullable();
                $table->unsignedInteger('product_id')->nullable();
                $table->unsignedInteger('quantity')->default(1);
                $table->decimal('price', 12, 2)->default(0);
                $table->string('product_name_snapshot')->nullable();
                $table->string('product_model_no_snapshot')->nullable();
                $table->string('product_sku_snapshot')->nullable();
                $table->string('product_barcode_snapshot')->nullable();
                $table->string('color_name_snapshot')->nullable();
                $table->string('size_name_snapshot')->nullable();
                $table->decimal('line_total', 12, 2)->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();
            });

            return;
        }

        Schema::table('order_items', function (Blueprint $table): void {
            foreach ([
                'variant_id' => fn (Blueprint $table) => $table->unsignedInteger('variant_id')->nullable()->after('order_id'),
                'product_id' => fn (Blueprint $table) => $table->unsignedInteger('product_id')->nullable()->after('variant_id'),
                'product_name_snapshot' => fn (Blueprint $table) => $table->string('product_name_snapshot')->nullable(),
                'product_model_no_snapshot' => fn (Blueprint $table) => $table->string('product_model_no_snapshot')->nullable(),
                'product_sku_snapshot' => fn (Blueprint $table) => $table->string('product_sku_snapshot')->nullable(),
                'product_barcode_snapshot' => fn (Blueprint $table) => $table->string('product_barcode_snapshot')->nullable(),
                'color_name_snapshot' => fn (Blueprint $table) => $table->string('color_name_snapshot')->nullable(),
                'size_name_snapshot' => fn (Blueprint $table) => $table->string('size_name_snapshot')->nullable(),
                'line_total' => fn (Blueprint $table) => $table->decimal('line_total', 12, 2)->default(0),
                'notes' => fn (Blueprint $table) => $table->text('notes')->nullable(),
            ] as $column => $callback) {
                if (! Schema::hasColumn('order_items', $column)) {
                    $callback($table);
                }
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('order_items')) {
            return;
        }

        Schema::table('order_items', function (Blueprint $table): void {
            foreach ([
                'product_id',
                'product_name_snapshot',
                'product_model_no_snapshot',
                'product_sku_snapshot',
                'product_barcode_snapshot',
                'color_name_snapshot',
                'size_name_snapshot',
                'line_total',
                'notes',
            ] as $column) {
                if (Schema::hasColumn('order_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
