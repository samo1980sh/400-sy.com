<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_info_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('company_name_ar')->nullable();
            $table->string('company_name_en')->nullable();
            $table->string('address_ar')->nullable();
            $table->string('address_en')->nullable();
            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('email')->nullable();
            $table->string('map_url')->nullable();
            $table->string('facebook_url')->nullable();
            $table->string('instagram_url')->nullable();
            $table->string('x_url')->nullable();
            $table->string('youtube_url')->nullable();
            $table->longText('working_hours_ar')->nullable();
            $table->longText('working_hours_en')->nullable();
            $table->longText('notes_ar')->nullable();
            $table->longText('notes_en')->nullable();
            $table->timestamps();
        });

        Schema::create('agency_request_pages', function (Blueprint $table): void {
            $table->id();
            $table->string('title_ar')->nullable();
            $table->string('title_en')->nullable();
            $table->longText('content_ar')->nullable();
            $table->longText('content_en')->nullable();
            $table->longText('terms_ar')->nullable();
            $table->longText('terms_en')->nullable();
            $table->timestamps();
        });

        Schema::create('recruitment_settings', function (Blueprint $table): void {
            $table->id();
            $table->boolean('is_enabled')->default(true);
            $table->string('title_ar')->nullable();
            $table->string('title_en')->nullable();
            $table->longText('intro_ar')->nullable();
            $table->longText('intro_en')->nullable();
            $table->timestamps();
        });

        Schema::create('job_vacancies', function (Blueprint $table): void {
            $table->id();
            $table->string('title_ar');
            $table->string('title_en');
            $table->string('slug')->unique();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->string('status')->default('active')->index();
            $table->string('location_ar')->nullable();
            $table->string('location_en')->nullable();
            $table->date('deadline_at')->nullable();
            $table->longText('description_ar')->nullable();
            $table->longText('description_en')->nullable();
            $table->longText('requirements_ar')->nullable();
            $table->longText('requirements_en')->nullable();
            $table->timestamps();
        });

        Schema::create('job_applications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('job_vacancy_id')->nullable()->constrained('job_vacancies')->nullOnDelete();
            $table->string('full_name');
            $table->string('mobile')->nullable();
            $table->string('email')->nullable();
            $table->string('city')->nullable();
            $table->string('cv_path')->nullable();
            $table->string('status')->default('new')->index();
            $table->longText('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_applications');
        Schema::dropIfExists('job_vacancies');
        Schema::dropIfExists('recruitment_settings');
        Schema::dropIfExists('agency_request_pages');
        Schema::dropIfExists('contact_info_settings');
    }
};
