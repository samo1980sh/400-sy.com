<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('measurement_charts', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('size_code');
            $table->decimal('chest', 10, 2)->nullable();
            $table->decimal('shoulder', 10, 2)->nullable();
            $table->decimal('waist', 10, 2)->nullable();
            $table->decimal('length', 10, 2)->nullable();
            $table->decimal('sleeve', 10, 2)->nullable();
            $table->decimal('collar', 10, 2)->nullable();
            $table->decimal('inside_leg', 10, 2)->nullable();
            $table->decimal('waistline', 10, 2)->nullable();
            $table->decimal('thigh_width', 10, 2)->nullable();
            $table->decimal('leg_width', 10, 2)->nullable();
            $table->decimal('leg_length', 10, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('measurement_charts');
    }
};
