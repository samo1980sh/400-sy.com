<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerQrLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'customer_qr_code_id',
        'branch_id',
        'scanned_by_user_id',
        'point_voucher_redemption_id',
        'action_type',
        'account_no',
        'customer_name',
        'mobile',
        'branch',
        'reference_no',
        'points_earned',
        'points_spent',
        'discount_amount',
        'sale_amount',
        'net_amount',
        'ip_address',
        'user_agent',
        'notes',
        'is_suspicious',
        'suspicious_reason',
        'scanned_at',
    ];

    protected function casts(): array
    {
        return [
            'points_earned' => 'decimal:2',
            'points_spent' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'sale_amount' => 'decimal:2',
            'net_amount' => 'decimal:2',
            'is_suspicious' => 'boolean',
            'scanned_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function qrCode(): BelongsTo
    {
        return $this->belongsTo(CustomerQrCode::class, 'customer_qr_code_id');
    }

    public function branchRecord(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function scannedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scanned_by_user_id');
    }

    public function pointVoucherRedemption(): BelongsTo
    {
        return $this->belongsTo(PointVoucherRedemption::class);
    }
}
