<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('customer_loyalty_settings')) {
            return;
        }

        Schema::create('customer_loyalty_settings', function (Blueprint $table): void {
            $table->id();
            $table->boolean('enabled')->default(true);
            $table->string('award_on_status', 50)->default('delivered');
            $table->string('points_base', 50)->default('net_total');
            $table->decimal('points_per_currency', 12, 4)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        DB::table('customer_loyalty_settings')->insert([
            'enabled' => true,
            'award_on_status' => 'delivered',
            'points_base' => 'net_total',
            'points_per_currency' => 0,
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_loyalty_settings');
    }
};
