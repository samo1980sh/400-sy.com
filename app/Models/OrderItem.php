<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_variant_id',
        'quantity',
        'approved_quantity',
        'unit_price',
        'product_name_snapshot',
        'product_model_no_snapshot',
        'product_sku_snapshot',
        'product_barcode_snapshot',
        'color_name_snapshot',
        'size_name_snapshot',
        'line_total',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'approved_quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
