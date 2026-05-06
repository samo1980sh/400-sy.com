<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('measurement_chart_groups', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('guide_image')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('measurement_chart_groups');
    }
};
