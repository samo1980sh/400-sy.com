<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgencyRequestPage extends Model
{
    use HasFactory;

    protected $fillable = [
        'title_ar',
        'title_en',
        'content_ar',
        'content_en',
        'terms_ar',
        'terms_en',
    ];

    public static function singleton(): self
    {
        return static::query()->first() ?? static::query()->create([]);
    }
}
