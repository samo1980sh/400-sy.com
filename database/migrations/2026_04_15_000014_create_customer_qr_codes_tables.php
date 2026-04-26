<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_qr_codes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('token')->unique();
            $table->string('status')->default('active');
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('disabled_at')->nullable();
            $table->timestamp('last_scanned_at')->nullable();
            $table->unsignedInteger('scan_count')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('customer_qr_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_qr_code_id')->constrained('customer_qr_codes')->cascadeOnDelete();
            $table->string('action_type')->default('scan');
            $table->string('account_no')->nullable();
            $table->string('customer_name');
            $table->string('mobile')->nullable();
            $table->string('branch')->nullable();
            $table->string('reference_no')->nullable();
            $table->decimal('points_earned', 12, 2)->default(0);
            $table->decimal('points_spent', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('scanned_at')->nullable();
            $table->timestamps();

            $table->index(['action_type', 'branch']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_qr_logs');
        Schema::dropIfExists('customer_qr_codes');
    }
};
