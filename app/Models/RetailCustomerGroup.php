<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class RetailCustomerGroup extends Model
{
    protected $fillable = [
        'name',
    ];

    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(
            Customer::class,
            'customer_retail_group_assignments'
        )->withTimestamps();
    }
}
