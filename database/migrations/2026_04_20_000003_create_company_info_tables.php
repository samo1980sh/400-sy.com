<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_pages', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title_ar');
            $table->string('title_en');
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->string('status')->default('active')->index();
            $table->longText('content_ar')->nullable();
            $table->longText('content_en')->nullable();
            $table->timestamps();
        });

        Schema::create('company_news_items', function (Blueprint $table): void {
            $table->id();
            $table->string('type')->default('news')->index();
            $table->string('slug')->unique();
            $table->string('title_ar');
            $table->string('title_en');
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->string('status')->default('active')->index();
            $table->date('event_date')->nullable();
            $table->string('main_image')->nullable();
            $table->json('gallery_images')->nullable();
            $table->string('excerpt_ar')->nullable();
            $table->string('excerpt_en')->nullable();
            $table->longText('content_ar')->nullable();
            $table->longText('content_en')->nullable();
            $table->timestamps();
        });

        Schema::create('company_header_images', function (Blueprint $table): void {
            $table->id();
            $table->string('title_ar')->nullable();
            $table->string('title_en')->nullable();
            $table->string('link_url')->nullable();
            $table->string('image')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->string('status')->default('active')->index();
            $table->timestamps();
        });

        Schema::create('company_news_ticker_items', function (Blueprint $table): void {
            $table->id();
            $table->string('text_ar');
            $table->string('text_en')->nullable();
            $table->string('link_url')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->string('status')->default('active')->index();
            $table->timestamps();
        });

        Schema::create('company_social_links', function (Blueprint $table): void {
            $table->id();
            $table->string('platform_key')->unique();
            $table->string('title_ar');
            $table->string('title_en');
            $table->string('url')->nullable();
            $table->string('icon')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->string('status')->default('active')->index();
            $table->timestamps();
        });

        Schema::create('internal_page_headers', function (Blueprint $table): void {
            $table->id();
            $table->string('section_key')->unique();
            $table->string('title_ar');
            $table->string('title_en');
            $table->string('image')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->string('status')->default('active')->index();
            $table->timestamps();
        });

        DB::table('company_pages')->insert([
            [
                'slug' => 'about-company',
                'title_ar' => 'حول الشركة',
                'title_en' => 'About Company',
                'content_ar' => null,
                'content_en' => null,
                'sort_order' => 1,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $now = now();
        $internalHeaderRows = collect(config('internal_page_sections', []))
            ->filter(fn (mixed $section): bool => is_array($section) && filled($section['section_key'] ?? null))
            ->map(fn (array $section): array => [
                'section_key' => $section['section_key'],
                'title_ar' => $section['title_ar'] ?? $section['section_key'],
                'title_en' => $section['title_en'] ?? $section['section_key'],
                'image' => null,
                'sort_order' => (int) ($section['sort_order'] ?? 0),
                'status' => $section['status'] ?? 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->values()
            ->all();

        if ($internalHeaderRows !== []) {
            DB::table('internal_page_headers')->insert($internalHeaderRows);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('internal_page_headers');
        Schema::dropIfExists('company_social_links');
        Schema::dropIfExists('company_news_ticker_items');
        Schema::dropIfExists('company_header_images');
        Schema::dropIfExists('company_news_items');
        Schema::dropIfExists('company_pages');
    }
};
