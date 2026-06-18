<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\GiftCard;
use App\Models\GiftCardRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class GiftCardRequestService
{
    public function issueCards(GiftCardRequest $request, array $data): Collection
    {
        return DB::transaction(function () use ($request, $data): Collection {
            $lockedRequest = GiftCardRequest::query()
                ->with('giftCards')
                ->lockForUpdate()
                ->findOrFail($request->getKey());

            if ($lockedRequest->status !== 'approved') {
                throw new RuntimeException('يجب اعتماد الطلب قبل إصدار بطاقات الهدايا.');
            }

            if ($lockedRequest->payment_status !== 'paid') {
                throw new RuntimeException('لا يمكن إصدار البطاقات قبل تثبيت حالة الدفع كمدفوع.');
            }

            if ($lockedRequest->giftCards->isNotEmpty()) {
                throw new RuntimeException('تم إصدار بطاقات لهذا الطلب مسبقاً.');
            }

            $quantity = max(1, (int) $lockedRequest->card_quantity);

            if ($quantity > 50) {
                throw new RuntimeException('عدد البطاقات في الطلب يتجاوز الحد المسموح للمعالجة دفعة واحدة.');
            }

            $cards = collect();

            for ($index = 0; $index < $quantity; $index++) {
                $cards->push(GiftCard::query()->create([
                    'gift_card_request_id' => $lockedRequest->getKey(),
                    'customer_id' => $lockedRequest->customer_id,
                    'recipient_name' => $lockedRequest->recipient_name,
                    'recipient_mobile' => $lockedRequest->recipient_mobile,
                    'display_name' => $lockedRequest->display_name,
                    'sender_name' => $lockedRequest->requester_name,
                    'amount' => $lockedRequest->card_amount,
                    'balance' => $lockedRequest->card_amount,
                    'currency' => $lockedRequest->currency,
                    'redemption_branch_id' => $lockedRequest->redemption_branch_id,
                    'status' => 'active',
                    'issued_at' => now(),
                    'expires_at' => $data['expires_at'] ?? null,
                    'message' => $lockedRequest->customer_notes,
                    'usage_instructions' => $data['usage_instructions'] ?? null,
                    'redemption_terms' => $data['redemption_terms'] ?? null,
                    'notes' => $data['notes'] ?? $lockedRequest->admin_notes,
                ]));
            }

            $lockedRequest->update([
                'status' => 'issued',
                'issued_at' => now(),
                'admin_notes' => $data['notes'] ?? $lockedRequest->admin_notes,
            ]);

            return $cards;
        }, 3);
    }
}
