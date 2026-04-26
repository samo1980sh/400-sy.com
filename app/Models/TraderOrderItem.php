<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TraderOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'trader_order_id',
        'product_id',
        'product_wholesale_color_id',
        'series_group',
        'size_text',
        'product_name_snapshot',
        'product_model_no_snapshot',
        'product_sku_snapshot',
        'product_barcode_snapshot',
        'color_name_snapshot',
        'series_snapshot',
        'quantity',
        'unit_price',
        'line_total',
        'notes',
    ];

    protected $casts = [
        'series_group' => 'integer',
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function traderOrder(): BelongsTo
    {
        return $this->belongsTo(TraderOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function wholesaleColor(): BelongsTo
    {
        return $this->belongsTo(ProductWholesaleColor::class, 'product_wholesale_color_id');
    }
}
