<?php

namespace App\Models;

use App\Models\Concerns\HasWebpMedia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CompanyNewsItem extends Model
{
    use HasFactory;
    use HasWebpMedia;

    protected $fillable = [
        'type',
        'slug',
        'title_ar',
        'title_en',
        'excerpt_ar',
        'excerpt_en',
        'content_ar',
        'content_en',
        'event_date',
        'main_image',
        'gallery_images',
        'sort_order',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'gallery_images' => 'array',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $item): void {
            if (blank($item->slug)) {
                $item->slug = static::uniqueSlug($item->title_ar ?: $item->title_en ?: 'news-item');
            }

            if (blank($item->sort_order)) {
                $item->sort_order = ((int) static::max('sort_order')) + 1;
            }
        });
    }

    protected function webpSingleImageFields(): array
    {
        return ['main_image'];
    }

    protected function webpMultipleImageFields(): array
    {
        return ['gallery_images'];
    }

    protected function webpImageSettings(string $field): array
    {
        return match ($field) {
            'main_image' => config('company_media.news_main_image', []),
            'gallery_images' => config('company_media.news_gallery_image', []),
            default => [],
        };
    }

    protected static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'news-item';
        $slug = $base;
        $counter = 1;

        while (static::query()->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))->where('slug', $slug)->exists()) {
            $slug = $base . '-' . (++$counter);
        }

        return $slug;
    }
}
