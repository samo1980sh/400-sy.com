<?php

namespace App\Models;

use App\Models\Concerns\HasWebpMedia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasWebpMedia;

    protected $fillable = [
        'parent_id',
        'title_ar',
        'title_en',
        'image',
        'banner',
        'show_in_home',
        'slug',
        'sort_order',
    ];

    protected function webpSingleImageFields(): array
    {
        return ['image', 'banner'];
    }

    protected function webpImageSettings(string $field): array
    {
        return config('company_media.categories.' . $field, []);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function isLeaf(): bool
    {
        if ($this->relationLoaded('children')) {
            return $this->children->isEmpty();
        }

        return ! $this->children()->exists();
    }

    /**
     * @return Collection<int, self>
     */
    public function breadcrumbTrail(): Collection
    {
        $trail = collect([$this]);
        $current = $this->relationLoaded('parent')
            ? $this->getRelation('parent')
            : $this->parent()->first();

        while ($current) {
            $trail->prepend($current);
            $current = $current->relationLoaded('parent')
                ? $current->getRelation('parent')
                : $current->parent()->first();
        }

        return $trail->values();
    }

    /**
     * @return Collection<int, self>
     */
    public static function breadcrumbTrailFor(?int $categoryId): Collection
    {
        if (! $categoryId) {
            return collect();
        }

        $category = static::query()->find($categoryId);

        if (! $category) {
            return collect();
        }

        return $category->breadcrumbTrail();
    }

    protected static function booted(): void
    {
        static::creating(function (self $category): void {
            if (blank($category->sort_order)) {
                $category->sort_order = ((int) static::max('sort_order')) + 1;
            }

            if (blank($category->slug)) {
                $category->slug = static::generateUniqueSlug($category->title_ar);
            }
        });

        static::updating(function (self $category): void {
            if (blank($category->slug)) {
                $category->slug = static::generateUniqueSlug($category->title_ar, $category->getKey());
            }
        });
    }

    public static function hierarchyOptions(?int $exceptId = null): array
    {
        $categories = static::query()
            ->orderBy('sort_order')
            ->orderBy('title_ar')
            ->get();

        return static::buildHierarchyOptionsWithPaths($categories, $exceptId);
    }

    public static function breadcrumbOptions(?int $exceptId = null): array
    {
        $categories = static::query()
            ->orderBy('sort_order')
            ->orderBy('title_ar')
            ->get();

        $options = [];

        foreach ($categories as $category) {
            if ($exceptId && $category->id === $exceptId) {
                continue;
            }

            $trail = $category->breadcrumbTrail()
                ->pluck('title_ar')
                ->filter()
                ->values()
                ->all();

            if ($trail === []) {
                continue;
            }

            $options[$category->id] = implode(' > ', $trail);
        }

        asort($options, SORT_NATURAL | SORT_FLAG_CASE);

        return $options;
    }

    public function scopeLeaf(Builder $query): Builder
    {
        return $query->whereDoesntHave('children');
    }

    public static function leafBreadcrumbOptions(?int $exceptId = null): array
    {
        $categories = static::query()
            ->leaf()
            ->with('parent')
            ->orderBy('sort_order')
            ->orderBy('title_ar')
            ->get();

        $options = [];

        foreach ($categories as $category) {
            if ($exceptId && $category->id === $exceptId) {
                continue;
            }

            $trail = $category->breadcrumbTrail()
                ->pluck('title_ar')
                ->filter()
                ->values()
                ->all();

            if ($trail === []) {
                continue;
            }

            $options[$category->id] = implode(' > ', $trail);
        }

        asort($options, SORT_NATURAL | SORT_FLAG_CASE);

        return $options;
    }

    public static function blockedSelectionIds(?int $categoryId = null): array
    {
        $rootIds = static::query()
            ->whereNull('parent_id')
            ->pluck('id')
            ->all();

        if (! $categoryId) {
            return array_values(array_unique($rootIds));
        }

        return array_values(array_unique([
            ...$rootIds,
            $categoryId,
            ...static::descendantIds($categoryId),
        ]));
    }

    protected static function buildHierarchyOptions(Collection $categories, ?int $exceptId = null): array
    {
        $options = [];

        $roots = $categories
            ->whereNull('parent_id')
            ->sortBy([
                ['sort_order', 'asc'],
                ['title_ar', 'asc'],
            ]);

        foreach ($roots as $root) {
            $groupOptions = [
                $root->id => static::optionLabel($root, 0, true),
            ];

            $children = static::flattenHierarchyOptions($categories, $root->id);

            if ($children !== []) {
                $groupOptions += $children;
            }

            $options[$root->title_ar] = $groupOptions;
        }

        return $options;
    }

    protected static function buildHierarchyOptionsWithPaths(Collection $categories, ?int $exceptId = null): array
    {
        $options = [];

        foreach ($categories->whereNull('parent_id') as $root) {
            static::appendHierarchyPaths($options, $categories, $root, [], $exceptId);
        }

        return $options;
    }

    protected static function appendHierarchyPaths(array &$options, Collection $categories, self $category, array $trail = [], ?int $exceptId = null): void
    {
        if ($exceptId && $category->id === $exceptId) {
            return;
        }

        $trail[] = $category->title_ar;
        $label = implode(' > ', $trail);

        $options[$category->id] = $label;

        $children = $categories
            ->where('parent_id', $category->id)
            ->sortBy([
                ['sort_order', 'asc'],
                ['title_ar', 'asc'],
            ]);

        foreach ($children as $child) {
            static::appendHierarchyPaths($options, $categories, $child, $trail, $exceptId);
        }
    }

    protected static function flattenHierarchyOptions(Collection $categories, int $parentId, int $depth = 1): array
    {
        $options = [];

        $children = $categories
            ->where('parent_id', $parentId)
            ->sortBy([
                ['sort_order', 'asc'],
                ['title_ar', 'asc'],
            ]);

        foreach ($children as $child) {
            $options[$child->id] = static::optionLabel($child, $depth);

            $descendants = static::flattenHierarchyOptions($categories, $child->id, $depth + 1);

            if ($descendants !== []) {
                $options += $descendants;
            }
        }

        return $options;
    }

    protected static function descendantIds(int $categoryId): array
    {
        $ids = [];

        $children = static::query()
            ->where('parent_id', $categoryId)
            ->get();

        foreach ($children as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, static::descendantIds($child->id));
        }

        return array_values(array_unique($ids));
    }

    protected static function optionLabel(self $category, int $depth = 1, bool $root = false): string
    {
        $prefix = $root ? '◆ ' : str_repeat('— ', max(0, $depth - 1));

        return $prefix . $category->title_ar . ($root ? ' (رئيسي)' : '');
    }

    protected static function generateUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($title);
        $slug = $baseSlug !== '' ? $baseSlug : 'category';
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
                : 'category-' . $counter;
        }

        return $slug;
    }
}
