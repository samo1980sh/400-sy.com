<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductWholesaleQuantity extends Model
{
    protected $fillable = [
        'product_id',
        'product_wholesale_color_id',
        'series_group',
        'size_text',
        'quantity',
        'source_value',
    ];

    protected $casts = [
        'series_group' => 'integer',
        'quantity' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function wholesaleColor(): BelongsTo
    {
        return $this->belongsTo(ProductWholesaleColor::class, 'product_wholesale_color_id');
    }
}
