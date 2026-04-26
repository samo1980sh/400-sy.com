<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->string('account_no')->unique()->nullable();
            $table->string('name');
            $table->date('birth_date')->nullable();
            $table->string('nationality')->nullable();
            $table->string('mobile')->unique();
            $table->string('secondary_mobile')->nullable();
            $table->string('gender')->nullable();
            $table->string('city')->nullable();
            $table->string('area')->nullable();
            $table->string('job_title')->nullable();
            $table->string('marital_status')->nullable();
            $table->string('email')->unique()->nullable();
            $table->string('password')->nullable();
            $table->string('status')->default('active');
            $table->timestamp('mobile_verified_at')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
