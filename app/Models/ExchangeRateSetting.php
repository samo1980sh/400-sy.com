<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExchangeRateSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'base_currency_code',
        'show_usd',
        'show_eur',
        'usd_syp_rate',
        'eur_syp_rate',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'show_usd' => 'boolean',
            'show_eur' => 'boolean',
            'usd_syp_rate' => 'decimal:4',
            'eur_syp_rate' => 'decimal:4',
        ];
    }

    public static function singleton(): self
    {
        return static::query()->first() ?? static::query()->create([
            'base_currency_code' => 'SYP',
            'show_usd' => true,
            'show_eur' => true,
            'usd_syp_rate' => 0,
            'eur_syp_rate' => 0,
        ]);
    }
}
