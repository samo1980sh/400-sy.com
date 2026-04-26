<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CompanyPage extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'title_ar',
        'title_en',
        'content_ar',
        'content_en',
        'sort_order',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $page): void {
            if (blank($page->slug)) {
                $page->slug = static::uniqueSlug($page->title_ar ?: $page->title_en ?: 'page');
            }

            if (blank($page->sort_order)) {
                $page->sort_order = ((int) static::max('sort_order')) + 1;
            }
        });
    }

    protected static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'page';
        $slug = $base;
        $counter = 1;

        while (static::query()->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))->where('slug', $slug)->exists()) {
            $slug = $base . '-' . (++$counter);
        }

        return $slug;
    }
}
