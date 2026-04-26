<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductWholesaleGroupAssignment extends Model
{
    protected $fillable = [
        'product_id',
        'wholesale_customer_group_id',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function wholesaleCustomerGroup(): BelongsTo
    {
        return $this->belongsTo(WholesaleCustomerGroup::class);
    }
}
