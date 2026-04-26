<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanySocialLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'platform_key',
        'title_ar',
        'title_en',
        'url',
        'icon',
        'sort_order',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'status' => 'string',
        ];
    }
}
