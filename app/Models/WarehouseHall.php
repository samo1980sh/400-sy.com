<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WarehouseHall extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'sort_order',
        'status',
    ];

    public function balances(): HasMany
    {
        return $this->hasMany(WarehouseInventoryBalance::class);
    }
}
