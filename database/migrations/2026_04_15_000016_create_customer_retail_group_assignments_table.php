<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_retail_group_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('retail_customer_group_id');
            $table->timestamps();

            $table->foreign('retail_customer_group_id', 'fk_crga_group')
                ->references('id')
                ->on('retail_customer_groups')
                ->cascadeOnDelete();

            $table->unique(['customer_id', 'retail_customer_group_id'], 'uniq_customer_retail_group_assignment');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_retail_group_assignments');
    }
};
