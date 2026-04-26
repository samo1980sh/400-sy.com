<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseInventoryBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_inventory_item_id',
        'warehouse_hall_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(WarehouseInventoryItem::class, 'warehouse_inventory_item_id');
    }

    public function hall(): BelongsTo
    {
        return $this->belongsTo(WarehouseHall::class, 'warehouse_hall_id');
    }
}
