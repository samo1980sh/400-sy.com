<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GiftCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'code',
        'recipient_name',
        'display_name',
        'sender_name',
        'amount',
        'balance',
        'status',
        'issued_at',
        'expires_at',
        'message',
        'usage_instructions',
        'redemption_terms',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'balance' => 'decimal:2',
            'issued_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(GiftCardRedemption::class);
    }

    protected static function booted(): void
    {
        static::creating(function (GiftCard $giftCard): void {
            if (blank($giftCard->code)) {
                $giftCard->code = 'GC-' . strtoupper(str()->random(8));
            }

            if (blank($giftCard->balance)) {
                $giftCard->balance = $giftCard->amount ?? 0;
            }
        });
    }
}
