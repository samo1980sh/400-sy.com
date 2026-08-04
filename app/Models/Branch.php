<?php

namespace App\Models;

use App\Services\WebpImageService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_category_id',
        'type',
        'name_ar',
        'name_en',
        'slug',
        'sort_order',
        'status',
        'address_ar',
        'address_en',
        'phone',
        'mobile',
        'whatsapp',
        'email',
        'map_url',
        'description_ar',
        'description_en',
        'main_image',
        'gallery_images',
        'notes_ar',
        'notes_en',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'gallery_images' => 'array',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BranchCategory::class, 'branch_category_id');
    }

    protected static function booted(): void
    {
        static::creating(function (self $branch): void {
            if (blank($branch->sort_order)) {
                $branch->sort_order = ((int) static::max('sort_order')) + 1;
            }

            if (blank($branch->slug)) {
                $branch->slug = static::generateUniqueSlug($branch->name_ar);
            }
        });

        static::saving(function (self $branch): void {
            $service = app(WebpImageService::class);

            $branch->main_image = filled($branch->main_image)
                ? $service->convertStoredPath(
                    (string) $branch->main_image,
                    config('company_media.branches.main_image', []),
                )
                : null;

            $galleryImages = is_array($branch->gallery_images) ? $branch->gallery_images : [];
            $branch->gallery_images = $service->convertStoredPaths($galleryImages, config('company_media.branches.gallery_image', []));
        });

        static::saved(function (self $branch): void {
            $service = app(WebpImageService::class);
            $originalMainImage = $branch->getOriginal('main_image');

            if (filled($originalMainImage) && $originalMainImage !== $branch->main_image) {
                $service->deleteStoredPath($originalMainImage);
            }

            $originalGallery = $branch->getOriginal('gallery_images');
            $originalGallery = is_string($originalGallery) ? json_decode($originalGallery, true) : $originalGallery;
            $originalGallery = is_array($originalGallery) ? $originalGallery : [];
            $currentGallery = $branch->gallery_images ?? [];

            foreach (array_diff($originalGallery, $currentGallery) as $removedPath) {
                $service->deleteStoredPath($removedPath);
            }
        });

        static::deleting(function (self $branch): void {
            $service = app(WebpImageService::class);

            if (filled($branch->main_image)) {
                $service->deleteStoredPath((string) $branch->main_image);
            }

            $service->deleteStoredPaths($branch->gallery_images ?? []);
        });
    }

    protected static function generateUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($title);
        $slug = $baseSlug !== '' ? $baseSlug : 'branch';
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
                : 'branch-' . $counter;
        }

        return $slug;
    }
}
