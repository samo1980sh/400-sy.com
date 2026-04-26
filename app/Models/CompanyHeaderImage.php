<?php

namespace App\Models;

use App\Models\Concerns\HasWebpMedia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyHeaderImage extends Model
{
    use HasFactory;
    use HasWebpMedia;

    protected $fillable = [
        'image',
        'video',
        'sort_order',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    protected function webpSingleImageFields(): array
    {
        return ['image'];
    }

    protected function webpImageSettings(string $field): array
    {
        return config('company_media.header_images', []);
    }
}
