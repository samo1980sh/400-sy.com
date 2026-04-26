<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'discount_type',
        'discount_value',
        'currency',
        'starts_at',
        'ends_at',
        'usage_limit_per_customer',
        'status',
        'created_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(CouponRedemption::class);
    }

    protected static function booted(): void
    {
        static::creating(function (Coupon $coupon): void {
            if (blank($coupon->code)) {
                $coupon->code = 'CP-' . strtoupper(str()->random(8));
            }

            if (blank($coupon->created_by) && auth()->check()) {
                $coupon->created_by = auth()->id();
            }
        });
    }
}
