<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PointsVoucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'retail_customer_group_id',
        'points_required',
        'voucher_value',
        'usage_method',
        'branch',
        'valid_days',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'points_required' => 'decimal:2',
            'voucher_value' => 'decimal:2',
        ];
    }

    public function customerGroup(): BelongsTo
    {
        return $this->belongsTo(RetailCustomerGroup::class, 'retail_customer_group_id');
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(PointVoucherRedemption::class);
    }

    protected static function booted(): void
    {
        static::creating(function (PointsVoucher $voucher): void {
            if (blank($voucher->code)) {
                $voucher->code = 'PV-' . strtoupper(str()->random(8));
            }
        });
    }
}
