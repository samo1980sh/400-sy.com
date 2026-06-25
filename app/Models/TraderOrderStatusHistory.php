<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TraderOrderStatusHistory extends Model
{
    use HasFactory;

    protected $table = 'trader_order_status_history';

    protected $fillable = [
        'trader_order_id',
        'from_status',
        'to_status',
        'from_payment_status',
        'to_payment_status',
        'note',
        'changed_by',
    ];

    public function traderOrder(): BelongsTo
    {
        return $this->belongsTo(TraderOrder::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
