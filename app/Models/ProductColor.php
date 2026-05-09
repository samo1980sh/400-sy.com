<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductColor extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'product_id',
        'filter_color_id',
        'color_code',
        'color_name_ar',
        'color_name_en',
        'color_hex',
        'swatch_image',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'filter_color_id' => 'integer',
        'status' => 'string',
        'sort_order' => 'integer',
    ];

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function filterColor(): BelongsTo
    {
        return $this->belongsTo(Color::class, 'filter_color_id');
    }
}
