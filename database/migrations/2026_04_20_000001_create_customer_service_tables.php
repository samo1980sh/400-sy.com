<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_service_settings', function (Blueprint $table): void {
            $table->id();
            $table->longText('membership_card_ar')->nullable();
            $table->longText('membership_card_en')->nullable();
            $table->string('app_ios_url')->nullable();
            $table->string('app_android_url')->nullable();
            $table->string('app_direct_url')->nullable();
            $table->longText('app_details_ar')->nullable();
            $table->longText('app_details_en')->nullable();
            $table->longText('terms_ar')->nullable();
            $table->longText('terms_en')->nullable();
            $table->longText('exchange_policy_ar')->nullable();
            $table->longText('exchange_policy_en')->nullable();
            $table->timestamps();
        });

        Schema::create('customer_service_faqs', function (Blueprint $table): void {
            $table->id();
            $table->string('question_ar');
            $table->string('question_en')->nullable();
            $table->longText('answer_ar');
            $table->longText('answer_en')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_service_faqs');
        Schema::dropIfExists('customer_service_settings');
    }
};
