<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerLoyaltyTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'customer_loyalty_wallet_id',
        'type',
        'points',
        'balance_before',
        'balance_after',
        'source_type',
        'source_id',
        'reference_no',
        'occurred_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'decimal:2',
            'balance_before' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'occurred_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(CustomerLoyaltyWallet::class, 'customer_loyalty_wallet_id');
    }
}
