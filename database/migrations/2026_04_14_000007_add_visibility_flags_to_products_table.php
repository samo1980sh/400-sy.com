<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->boolean('show_web')->default(false)->after('display_channels');
            $table->boolean('show_app')->default(false)->after('show_web');
            $table->boolean('show_retail')->default(false)->after('show_app');
            $table->boolean('show_wholesale')->default(false)->after('show_retail');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn(['show_web', 'show_app', 'show_retail', 'show_wholesale']);
        });
    }
};
