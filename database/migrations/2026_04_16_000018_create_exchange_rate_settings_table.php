<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('exchange_rate_settings')) {
            return;
        }

        Schema::create('exchange_rate_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('base_currency_code', 10)->default('SYP');
            $table->boolean('show_usd')->default(true);
            $table->boolean('show_eur')->default(true);
            $table->decimal('usd_syp_rate', 12, 4)->default(0);
            $table->decimal('eur_syp_rate', 12, 4)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        DB::table('exchange_rate_settings')->insert([
            'base_currency_code' => 'SYP',
            'show_usd' => true,
            'show_eur' => true,
            'usd_syp_rate' => 0,
            'eur_syp_rate' => 0,
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_rate_settings');
    }
};
