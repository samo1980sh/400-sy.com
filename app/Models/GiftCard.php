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
        'gift_card_request_id',
        'customer_id',
        'code',
        'recipient_name',
        'recipient_mobile',
        'display_name',
        'sender_name',
        'amount',
        'balance',
        'currency',
        'redemption_branch_id',
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

    public function request(): BelongsTo
    {
        return $this->belongsTo(GiftCardRequest::class, 'gift_card_request_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function redemptionBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'redemption_branch_id');
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

            if ($giftCard->balance === null || $giftCard->balance === '') {
                $giftCard->balance = $giftCard->amount ?? 0;
            }

            $giftCard->currency = strtoupper((string) ($giftCard->currency ?: 'SYP'));
        });
    }
}
