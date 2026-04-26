<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_ar',
        'name_en',
        'code',
        'cost',
        'delivery_time',
        'active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'cost' => 'decimal:2',
            'active' => 'boolean',
        ];
    }
}
