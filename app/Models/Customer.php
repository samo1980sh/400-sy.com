<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Customer extends Authenticatable
{
    use HasFactory;

    protected $fillable = [
        'account_no',
        'name',
        'birth_date',
        'nationality',
        'mobile',
        'secondary_mobile',
        'gender',
        'city',
        'area',
        'job_title',
        'marital_status',
        'email',
        'password',
        'status',
        'mobile_verified_at',
        'email_verified_at',
        'notes',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'mobile_verified_at' => 'datetime',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function loyaltyWallet(): HasOne
    {
        return $this->hasOne(CustomerLoyaltyWallet::class);
    }

    public function loyaltyTransactions(): HasMany
    {
        return $this->hasMany(CustomerLoyaltyTransaction::class);
    }

    public function giftCards(): HasMany
    {
        return $this->hasMany(GiftCard::class);
    }

    public function giftCardRequests(): HasMany
    {
        return $this->hasMany(GiftCardRequest::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function pointVoucherRedemptions(): HasMany
    {
        return $this->hasMany(PointVoucherRedemption::class);
    }

    public function qrCode(): HasOne
    {
        return $this->hasOne(CustomerQrCode::class);
    }

    public function qrLogs(): HasMany
    {
        return $this->hasMany(CustomerQrLog::class);
    }

    public function retailGroups(): BelongsToMany
    {
        return $this->belongsToMany(
            RetailCustomerGroup::class,
            'customer_retail_group_assignments'
        )->withTimestamps();
    }

    public function wishlistProducts(): BelongsToMany
    {
        return $this->belongsToMany(
            Product::class,
            'customer_wishlist_items'
        )->withTimestamps();
    }

    protected static function booted(): void
    {
        static::created(function (Customer $customer): void {
            CustomerLoyaltyWallet::firstOrCreate(
                ['customer_id' => $customer->id],
                [
                    'points_balance' => 0,
                    'points_earned_total' => 0,
                    'points_spent_total' => 0,
                    'status' => 'active',
                ]
            );

            CustomerQrCode::firstOrCreate(
                ['customer_id' => $customer->id],
                [
                    'token' => null,
                    'status' => 'active',
                    'generated_at' => now(),
                    'scan_count' => 0,
                ]
            );
        });
    }
}
