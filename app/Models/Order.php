<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_no',
        'customer_id',
        'shipping_address_id',
        'shipping_method_id',
        'customer_name_snapshot',
        'customer_mobile_snapshot',
        'customer_email_snapshot',
        'customer_account_no_snapshot',
        'coupon_code_snapshot',
        'point_voucher_code_snapshot',
        'shipping_label_snapshot',
        'shipping_contact_name_snapshot',
        'shipping_mobile_snapshot',
        'shipping_city_snapshot',
        'shipping_area_snapshot',
        'shipping_address_line_snapshot',
        'shipping_address_type_snapshot',
        'status',
        'payment_status',
        'payment_method',
        'branch',
        'is_gift',
        'gift_message',
        'total_before_discount',
        'discount_value',
        'coupon_discount_value',
        'point_voucher_discount_value',
        'shipping_cost',
        'total',
        'confirmed_at',
        'paid_at',
        'shipped_at',
        'delivered_at',
        'cancelled_at',
        'notes',
    ];

    protected $casts = [
        'is_gift' => 'boolean',
        'total_before_discount' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'coupon_discount_value' => 'decimal:2',
        'point_voucher_discount_value' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'total' => 'decimal:2',
        'confirmed_at' => 'datetime',
        'paid_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Order $order): void {
            if (blank($order->order_no)) {
                $order->order_no = 'ORD-' . now()->format('Ymd') . '-' . strtoupper(str()->random(6));
            }
        });

        static::updated(function (Order $order): void {
            if (! $order->wasChanged('status')) {
                return;
            }

            $redemption = $order->pointVoucherRedemption()->first();

            if (! $redemption instanceof PointVoucherRedemption) {
                return;
            }

            if (in_array($order->status, ['confirmed', 'shipped', 'delivered'], true)) {
                if ($redemption->status !== 'redeemed') {
                    $redemption->forceFill([
                        'status' => 'redeemed',
                        'applied_at' => $redemption->applied_at ?: now(),
                    ])->save();
                }

                return;
            }

            if ($order->status === 'cancelled' && $redemption->status === 'reserved') {
                $redemption->forceFill([
                    'order_id' => null,
                    'status' => 'available',
                    'applied_at' => null,
                ])->save();
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function shippingAddress(): BelongsTo
    {
        return $this->belongsTo(CustomerAddress::class, 'shipping_address_id');
    }

    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class, 'shipping_method_id');
    }

    public function couponRedemption(): HasOne
    {
        return $this->hasOne(CouponRedemption::class);
    }

    public function pointVoucherRedemption(): HasOne
    {
        return $this->hasOne(PointVoucherRedemption::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    public function rating(): HasOne
    {
        return $this->hasOne(OrderRating::class);
    }
}
