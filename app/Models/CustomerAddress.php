<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class CustomerAddress extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'label',
        'contact_name',
        'mobile',
        'city',
        'area',
        'address_line',
        'address_type',
        'is_default',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'shipping_address_id');
    }

    protected static function booted(): void
    {
        static::saved(function (CustomerAddress $address): void {
            if (! $address->is_default || blank($address->customer_id)) {
                return;
            }

            DB::table('customer_addresses')
                ->where('customer_id', $address->customer_id)
                ->where('id', '!=', $address->id)
                ->update(['is_default' => 0]);
        });
    }
}
