<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TraderOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_no',
        'trader_id',
        'trader_name_snapshot',
        'trader_mobile_snapshot',
        'trader_account_no_snapshot',
        'trader_group_snapshot',
        'shipping_contact_name_snapshot',
        'shipping_mobile_snapshot',
        'shipping_city_snapshot',
        'shipping_area_snapshot',
        'shipping_address_line_snapshot',
        'status',
        'payment_status',
        'payment_method',
        'branch',
        'total_before_discount',
        'discount_value',
        'shipping_cost',
        'total',
        'confirmed_at',
        'shipped_at',
        'delivered_at',
        'cancelled_at',
        'notes',
    ];

    protected $casts = [
        'total_before_discount' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'total' => 'decimal:2',
        'confirmed_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (TraderOrder $order): void {
            if (blank($order->order_no)) {
                $order->order_no = 'TO-' . now()->format('Ymd') . '-' . strtoupper(str()->random(6));
            }
        });
    }

    public function trader(): BelongsTo
    {
        return $this->belongsTo(Trader::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(TraderOrderItem::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(TraderOrderStatusHistory::class);
    }
}
