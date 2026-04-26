<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductWholesaleAvailability extends Model
{
    protected $fillable = [
        'product_id',
        'product_wholesale_color_id',
        'wholesale_customer_group_id',
        'max_quantity',
    ];

    protected $casts = [
        'max_quantity' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function wholesaleColor(): BelongsTo
    {
        return $this->belongsTo(ProductWholesaleColor::class, 'product_wholesale_color_id');
    }

    public function wholesaleCustomerGroup(): BelongsTo
    {
        return $this->belongsTo(WholesaleCustomerGroup::class);
    }
}
