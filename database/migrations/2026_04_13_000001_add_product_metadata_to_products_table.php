<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->string('country', 100)->nullable()->after('compare_price');
            $table->string('structure', 100)->nullable()->after('country');
            $table->string('collection', 100)->nullable()->after('structure');
            $table->string('currency_ar', 50)->nullable()->after('collection');
            $table->string('currency_en', 50)->nullable()->after('currency_ar');
            $table->string('visibility_targets', 255)->nullable()->after('currency_en');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn([
                'country',
                'structure',
                'collection',
                'currency_ar',
                'currency_en',
                'visibility_targets',
            ]);
        });
    }
};
