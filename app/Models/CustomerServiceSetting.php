<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerServiceSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'setting_key',
        'title_ar',
        'title_en',
        'content_ar',
        'content_en',
        'app_ios_url',
        'app_android_url',
        'app_direct_url',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function defaultSettings(): array
    {
        return [
            'membership_card' => [
                'setting_key' => 'membership_card',
                'title_ar' => 'بطاقة العضوية',
                'title_en' => 'Membership Card',
                'content_ar' => null,
                'content_en' => null,
                'sort_order' => 1,
                'is_active' => true,
            ],
            'app_400' => [
                'setting_key' => 'app_400',
                'title_ar' => 'تطبيق 400',
                'title_en' => '400 App',
                'content_ar' => null,
                'content_en' => null,
                'app_ios_url' => null,
                'app_android_url' => null,
                'app_direct_url' => null,
                'sort_order' => 2,
                'is_active' => true,
            ],
            'terms' => [
                'setting_key' => 'terms',
                'title_ar' => 'الشروط والأحكام',
                'title_en' => 'Terms and Conditions',
                'content_ar' => null,
                'content_en' => null,
                'sort_order' => 3,
                'is_active' => true,
            ],
            'exchange_policy' => [
                'setting_key' => 'exchange_policy',
                'title_ar' => 'سياسة التبديل',
                'title_en' => 'Exchange Policy',
                'content_ar' => null,
                'content_en' => null,
                'sort_order' => 4,
                'is_active' => true,
            ],
        ];
    }

    public static function seedDefaults(): void
    {
        foreach (static::defaultSettings() as $attributes) {
            static::query()->firstOrCreate(
                ['setting_key' => $attributes['setting_key']],
                array_merge($attributes, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]),
            );
        }
    }

    public static function syncDefaults(): void
    {
        foreach (static::defaultSettings() as $attributes) {
            static::query()->updateOrCreate(
                ['setting_key' => $attributes['setting_key']],
                [
                    'title_ar' => $attributes['title_ar'],
                    'title_en' => $attributes['title_en'],
                    'sort_order' => $attributes['sort_order'],
                    'is_active' => $attributes['is_active'],
                ],
            );
        }
    }
}
