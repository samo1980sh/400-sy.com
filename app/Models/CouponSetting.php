<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CouponSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'enabled',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
        ];
    }

    public static function singleton(): self
    {
        return static::query()->first() ?? static::query()->create([
            'enabled' => false,
        ]);
    }
}
