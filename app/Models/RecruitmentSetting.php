<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecruitmentSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'is_enabled',
        'title_ar',
        'title_en',
        'intro_ar',
        'intro_en',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
        ];
    }

    public static function singleton(): self
    {
        return static::query()->first() ?? static::query()->create([
            'is_enabled' => true,
        ]);
    }
}
