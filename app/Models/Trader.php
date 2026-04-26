<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trader extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_no',
        'name',
        'mobile',
        'secondary_mobile',
        'email',
        'wholesale_customer_group_id',
        'city',
        'area',
        'address_line',
        'password',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function wholesaleCustomerGroup(): BelongsTo
    {
        return $this->belongsTo(WholesaleCustomerGroup::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(TraderOrder::class);
    }
}
