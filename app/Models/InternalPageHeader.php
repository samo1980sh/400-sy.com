<?php

namespace App\Models;

use App\Models\Concerns\HasWebpMedia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InternalPageHeader extends Model
{
    use HasFactory;
    use HasWebpMedia;

    protected $fillable = [
        'section_key',
        'title_ar',
        'title_en',
        'image',
        'sort_order',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function configuredSections(): array
    {
        return array_values(array_filter(
            config('internal_page_sections', []),
            fn (mixed $section): bool => is_array($section) && filled($section['section_key'] ?? null)
        ));
    }

    public static function syncConfiguredSections(): void
    {
        foreach (static::configuredSections() as $section) {
            static::query()->updateOrCreate(
                ['section_key' => $section['section_key']],
                [
                    'title_ar' => $section['title_ar'] ?? $section['section_key'],
                    'title_en' => $section['title_en'] ?? $section['section_key'],
                    'sort_order' => (int) ($section['sort_order'] ?? 0),
                    'status' => $section['status'] ?? 'active',
                ]
            );
        }
    }

    protected function webpSingleImageFields(): array
    {
        return ['image'];
    }

    protected function webpImageSettings(string $field): array
    {
        return config('company_media.internal_headers', []);
    }
}
