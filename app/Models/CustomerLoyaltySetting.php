<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerLoyaltySetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'enabled',
        'award_on_status',
        'points_base',
        'points_per_currency',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'points_per_currency' => 'decimal:4',
        ];
    }

    public static function singleton(): self
    {
        return static::query()->first() ?? static::query()->create([
            'enabled' => true,
            'award_on_status' => 'delivered',
            'points_base' => 'net_total',
            'points_per_currency' => 0.001,
        ]);
    }
}
