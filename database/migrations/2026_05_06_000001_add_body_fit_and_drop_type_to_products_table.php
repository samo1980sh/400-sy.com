<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->string('body_fit')->nullable()->after('collection');
            $table->index('body_fit', 'products_body_fit_index');
            $table->string('drop_type')->nullable()->after('body_fit');
            $table->index('drop_type', 'products_drop_type_index');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropIndex('products_drop_type_index');
            $table->dropColumn('drop_type');
            $table->dropIndex('products_body_fit_index');
            $table->dropColumn('body_fit');
        });
    }
};
