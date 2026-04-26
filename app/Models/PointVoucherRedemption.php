<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointVoucherRedemption extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'points_voucher_id',
        'order_no',
        'customer_name',
        'account_no',
        'mobile',
        'voucher_value',
        'points_spent',
        'usage_method',
        'branch',
        'status',
        'issued_at',
        'expires_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'voucher_value' => 'decimal:2',
            'points_spent' => 'decimal:2',
            'issued_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(PointsVoucher::class, 'points_voucher_id');
    }

    protected static function booted(): void
    {
        static::creating(function (PointVoucherRedemption $redemption): void {
            if (blank($redemption->order_no)) {
                $redemption->order_no = 'PVR-' . now()->format('Ymd') . '-' . strtoupper(str()->random(5));
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
