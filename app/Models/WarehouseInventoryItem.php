<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WarehouseInventoryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'country',
        'model_code',
        'barcode',
        'short_code',
        'item_name',
        'size_code',
        'color_name',
        'color_code',
        'card_price',
        'discount_rate',
        'sale_price',
        'warehouse_stock',
    ];

    protected $casts = [
        'card_price' => 'decimal:2',
        'discount_rate' => 'decimal:4',
        'sale_price' => 'decimal:2',
        'warehouse_stock' => 'decimal:2',
    ];

    public function balances(): HasMany
    {
        return $this->hasMany(WarehouseInventoryBalance::class);
    }
}
