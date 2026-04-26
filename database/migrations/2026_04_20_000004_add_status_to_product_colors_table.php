<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_colors', function (Blueprint $table): void {
            $table->string('status', 20)->default('active')->after('color_name_en');
        });
    }

    public function down(): void
    {
        Schema::table('product_colors', function (Blueprint $table): void {
            $table->dropColumn('status');
        });
    }
};
