<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GiftCardRedemption extends Model
{
    use HasFactory;

    protected $fillable = [
        'gift_card_id',
        'customer_id',
        'order_id',
        'order_no',
        'customer_name',
        'account_no',
        'mobile',
        'amount_used',
        'balance_before',
        'balance_after',
        'status',
        'applied_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount_used' => 'decimal:2',
            'balance_before' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'applied_at' => 'datetime',
        ];
    }

    public function giftCard(): BelongsTo
    {
        return $this->belongsTo(GiftCard::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    protected static function booted(): void
    {
        static::creating(function (GiftCardRedemption $redemption): void {
            if (blank($redemption->order_no) && $redemption->order) {
                $redemption->order_no = $redemption->order->order_no;
            }

            if (blank($redemption->customer_name) && $redemption->customer) {
                $redemption->customer_name = $redemption->customer->name;
            }

            if (blank($redemption->account_no) && $redemption->customer) {
                $redemption->account_no = $redemption->customer->account_no;
            }

            if (blank($redemption->mobile) && $redemption->customer) {
                $redemption->mobile = $redemption->customer->mobile;
            }
        });
    }
}
