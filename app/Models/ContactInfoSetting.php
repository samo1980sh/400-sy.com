<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactInfoSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_name_ar',
        'company_name_en',
        'address_ar',
        'address_en',
        'phone',
        'mobile',
        'whatsapp',
        'email',
        'map_url',
        'facebook_url',
        'instagram_url',
        'x_url',
        'youtube_url',
        'working_hours_ar',
        'working_hours_en',
        'notes_ar',
        'notes_en',
    ];

    public static function singleton(): self
    {
        return static::query()->first() ?? static::query()->create([]);
    }
}
