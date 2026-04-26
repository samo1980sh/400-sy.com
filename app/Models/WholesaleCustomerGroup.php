<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class WholesaleCustomerGroup extends Model
{
    protected $fillable = [
        'name_ar',
        'name_en',
        'code',
        'status',
        'sort_order',
    ];

    public $timestamps = false;

    public function traders(): HasMany
    {
        return $this->hasMany(Trader::class);
    }
}
