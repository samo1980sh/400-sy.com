<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_colors', function (Blueprint $table): void {
            if (! Schema::hasColumn('product_colors', 'swatch_image')) {
                $table->string('swatch_image')->nullable()->after('color_hex');
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_colors', function (Blueprint $table): void {
            if (Schema::hasColumn('product_colors', 'swatch_image')) {
                $table->dropColumn('swatch_image');
            }
        });
    }
};
