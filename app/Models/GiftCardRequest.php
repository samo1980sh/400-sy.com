<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GiftCardRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_no',
        'customer_id',
        'display_name_type',
        'requester_name',
        'recipient_name',
        'display_name',
        'card_quantity',
        'recipient_mobile',
        'card_amount',
        'currency',
        'cards_subtotal',
        'fulfillment_method',
        'pickup_branch_id',
        'shipping_method_id',
        'delivery_address',
        'delivery_fee',
        'payment_method_id',
        'redemption_branch_id',
        'total_amount',
        'status',
        'payment_status',
        'customer_notes',
        'admin_notes',
        'submitted_at',
        'reviewed_at',
        'issued_at',
        'completed_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'card_quantity' => 'integer',
            'card_amount' => 'decimal:2',
            'cards_subtotal' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'issued_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function pickupBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'pickup_branch_id');
    }

    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function redemptionBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'redemption_branch_id');
    }

    public function giftCards(): HasMany
    {
        return $this->hasMany(GiftCard::class);
    }

    public static function statusOptions(): array
    {
        return [
            'pending' => 'بانتظار المراجعة',
            'reviewing' => 'قيد المراجعة',
            'approved' => 'موافق عليه',
            'issued' => 'تم إصدار البطاقات',
            'completed' => 'مكتمل',
            'rejected' => 'مرفوض',
            'cancelled' => 'ملغى',
        ];
    }

    public static function paymentStatusOptions(): array
    {
        return [
            'pending' => 'بانتظار الدفع',
            'paid' => 'مدفوع',
            'failed' => 'فشل الدفع',
            'refunded' => 'مسترد',
        ];
    }

    public static function displayNameTypeOptions(): array
    {
        return [
            'recipient' => 'اسم المستفيد',
            'requester' => 'اسم طالب البطاقة',
            'anonymous' => 'مجهول',
        ];
    }

    public static function fulfillmentMethodOptions(): array
    {
        return [
            'branch_pickup' => 'الاستلام من الفرع',
            'delivery' => 'التوصيل',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $request): void {
            if (blank($request->request_no)) {
                $request->request_no = 'GCR-' . now()->format('ymd') . '-' . strtoupper(str()->random(6));
            }

            $request->submitted_at ??= now();
        });

        static::saving(function (self $request): void {
            $quantity = max(1, (int) $request->card_quantity);
            $amount = max(0, (float) $request->card_amount);
            $deliveryFee = max(0, (float) $request->delivery_fee);

            $request->card_quantity = $quantity;
            $request->currency = strtoupper((string) ($request->currency ?: 'SYP'));
            $request->cards_subtotal = round($quantity * $amount, 2);
            $request->total_amount = round((float) $request->cards_subtotal + $deliveryFee, 2);

            $request->display_name = match ($request->display_name_type) {
                'recipient' => $request->recipient_name,
                'anonymous' => null,
                default => $request->requester_name,
            };
        });
    }
}
