<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('slug')->unique();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->string('status')->default('active')->index();
            $table->longText('description_ar')->nullable();
            $table->longText('description_en')->nullable();
            $table->longText('notes_ar')->nullable();
            $table->longText('notes_en')->nullable();
            $table->timestamps();
        });

        Schema::create('branches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_category_id')->constrained('branch_categories')->cascadeOnDelete();
            $table->string('type')->default('branch')->index();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('slug')->unique();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->string('status')->default('active')->index();
            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('email')->nullable();
            $table->string('map_url')->nullable();
            $table->longText('address_ar')->nullable();
            $table->longText('address_en')->nullable();
            $table->longText('description_ar')->nullable();
            $table->longText('description_en')->nullable();
            $table->string('main_image')->nullable();
            $table->json('gallery_images')->nullable();
            $table->longText('notes_ar')->nullable();
            $table->longText('notes_en')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
        Schema::dropIfExists('branch_categories');
    }
};
