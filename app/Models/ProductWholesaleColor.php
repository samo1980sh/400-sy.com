<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductWholesaleColor extends Model
{
    protected $fillable = [
        'product_id',
        'color_code',
        'color_name_ar',
        'color_name_en',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
