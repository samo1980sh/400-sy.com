<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class BranchCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_ar',
        'name_en',
        'slug',
        'sort_order',
        'status',
        'description_ar',
        'description_en',
        'notes_ar',
        'notes_en',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    protected static function booted(): void
    {
        static::creating(function (self $category): void {
            if (blank($category->sort_order)) {
                $category->sort_order = ((int) static::max('sort_order')) + 1;
            }

            if (blank($category->slug)) {
                $category->slug = static::generateUniqueSlug($category->name_ar);
            }
        });

        static::updating(function (self $category): void {
            if (blank($category->slug)) {
                $category->slug = static::generateUniqueSlug($category->name_ar, $category->getKey());
            }
        });
    }

    protected static function generateUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($title);
        $slug = $baseSlug !== '' ? $baseSlug : 'branch-category';
        $counter = 1;

        while (
            static::query()
                ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $counter++;
            $slug = $baseSlug !== ''
                ? $baseSlug . '-' . $counter
                : 'branch-category-' . $counter;
        }

        return $slug;
    }
}
