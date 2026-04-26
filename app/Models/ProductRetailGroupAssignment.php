<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductRetailGroupAssignment extends Model
{
    protected $fillable = [
        'product_id',
        'retail_customer_group_id',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function retailCustomerGroup(): BelongsTo
    {
        return $this->belongsTo(RetailCustomerGroup::class);
    }
}
