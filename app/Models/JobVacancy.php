<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class JobVacancy extends Model
{
    use HasFactory;

    protected $fillable = [
        'title_ar',
        'title_en',
        'slug',
        'sort_order',
        'status',
        'location_ar',
        'location_en',
        'deadline_at',
        'description_ar',
        'description_en',
        'requirements_ar',
        'requirements_en',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'deadline_at' => 'date',
        ];
    }

    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    protected static function booted(): void
    {
        static::creating(function (self $job): void {
            if (blank($job->sort_order)) {
                $job->sort_order = ((int) static::max('sort_order')) + 1;
            }

            if (blank($job->slug)) {
                $job->slug = static::generateUniqueSlug($job->title_ar);
            }
        });

        static::updating(function (self $job): void {
            if (blank($job->slug)) {
                $job->slug = static::generateUniqueSlug($job->title_ar, $job->getKey());
            }
        });
    }

    protected static function generateUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($title);
        $slug = $baseSlug !== '' ? $baseSlug : 'job-vacancy';
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
                : 'job-vacancy-' . $counter;
        }

        return $slug;
    }
}
