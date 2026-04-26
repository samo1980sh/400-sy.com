<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CustomerQrCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'token',
        'status',
        'generated_at',
        'disabled_at',
        'last_scanned_at',
        'scan_count',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
            'disabled_at' => 'datetime',
            'last_scanned_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(CustomerQrLog::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function disable(): void
    {
        $this->update([
            'status' => 'inactive',
            'disabled_at' => now(),
        ]);
    }

    public function enable(): void
    {
        $this->update([
            'status' => 'active',
            'disabled_at' => null,
        ]);
    }

    protected static function booted(): void
    {
        static::creating(function (CustomerQrCode $qrCode): void {
            if (blank($qrCode->token)) {
                $qrCode->token = 'QR-' . strtoupper(Str::random(20));
            }

            if (blank($qrCode->generated_at)) {
                $qrCode->generated_at = now();
            }
        });
    }
}
