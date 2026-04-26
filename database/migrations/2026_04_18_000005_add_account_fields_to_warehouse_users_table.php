<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouse_users', function (Blueprint $table): void {
            $table->string('username')->unique()->nullable()->after('account_no');
            $table->string('country')->default('سوريا')->after('username');
            $table->string('account_type')->default('point_of_sale')->after('country');
        });
    }

    public function down(): void
    {
        Schema::table('warehouse_users', function (Blueprint $table): void {
            $table->dropColumn(['username', 'country', 'account_type']);
        });
    }
};
