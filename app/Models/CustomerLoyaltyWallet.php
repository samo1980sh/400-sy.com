<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerLoyaltyWallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'points_balance',
        'points_earned_total',
        'points_spent_total',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'points_balance' => 'decimal:2',
            'points_earned_total' => 'decimal:2',
            'points_spent_total' => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(CustomerLoyaltyTransaction::class);
    }
}
