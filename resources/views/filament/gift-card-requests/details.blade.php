@php
    $money = static fn ($value, $currency): string => number_format((float) $value, 2, '.', ',') . ' ' . strtoupper((string) ($currency ?: 'SYP'));
    $statusLabel = \App\Models\GiftCardRequest::statusOptions()[$request->status] ?? $request->status;
    $paymentStatusLabel = \App\Models\GiftCardRequest::paymentStatusOptions()[$request->payment_status] ?? $request->payment_status;
    $displayNameTypeLabel = \App\Models\GiftCardRequest::displayNameTypeOptions()[$request->display_name_type] ?? $request->display_name_type;
    $fulfillmentLabel = \App\Models\GiftCardRequest::fulfillmentMethodOptions()[$request->fulfillment_method] ?? $request->fulfillment_method;
@endphp

<div class="space-y-6">
    <x-filament::section heading="معلومات الطلب والزبون">
        <dl class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <dt class="text-sm text-gray-500">رقم الطلب</dt>
                <dd class="font-medium" dir="ltr">{{ $request->request_no }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">حالة الطلب</dt>
                <dd class="font-medium">{{ $statusLabel }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">الزبون</dt>
                <dd class="font-medium">{{ $request->customer?->name ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">رقم الحساب</dt>
                <dd class="font-medium" dir="ltr">{{ $request->customer?->account_no ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">رقم الموبايل</dt>
                <dd class="font-medium" dir="ltr">{{ $request->customer?->mobile ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">تاريخ الطلب</dt>
                <dd class="font-medium">{{ $request->submitted_at?->format('Y-m-d H:i') ?: '—' }}</dd>
            </div>
        </dl>
    </x-filament::section>

    <x-filament::section heading="بيانات البطاقات المطلوبة">
        <dl class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <dt class="text-sm text-gray-500">الاسم المطلوب على البطاقة</dt>
                <dd class="font-medium">{{ $displayNameTypeLabel }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">الاسم الظاهر</dt>
                <dd class="font-medium">{{ $request->display_name ?: 'مجهول' }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">اسم طالب البطاقة</dt>
                <dd class="font-medium">{{ $request->requester_name }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">اسم المستفيد</dt>
                <dd class="font-medium">{{ $request->recipient_name ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">موبايل المستفيد</dt>
                <dd class="font-medium" dir="ltr">{{ $request->recipient_mobile ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">عدد البطاقات</dt>
                <dd class="font-medium">{{ $request->card_quantity }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">قيمة البطاقة الواحدة</dt>
                <dd class="font-medium">{{ $money($request->card_amount, $request->currency) }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">قيمة البطاقات</dt>
                <dd class="font-medium">{{ $money($request->cards_subtotal, $request->currency) }}</dd>
            </div>
        </dl>
    </x-filament::section>

    <x-filament::section heading="الاستلام والدفع والصرف">
        <dl class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <dt class="text-sm text-gray-500">طريقة الاستلام</dt>
                <dd class="font-medium">{{ $fulfillmentLabel }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">فرع الاستلام</dt>
                <dd class="font-medium">{{ $request->pickupBranch?->name_ar ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">طريقة التوصيل</dt>
                <dd class="font-medium">{{ $request->shippingMethod?->name_ar ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">عنوان التوصيل</dt>
                <dd class="font-medium">{{ $request->delivery_address ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">رسوم التوصيل</dt>
                <dd class="font-medium">{{ $money($request->delivery_fee, $request->currency) }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">إجمالي الطلب</dt>
                <dd class="font-medium">{{ $money($request->total_amount, $request->currency) }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">طريقة الدفع</dt>
                <dd class="font-medium">{{ $request->paymentMethod?->name_ar ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">حالة الدفع</dt>
                <dd class="font-medium">{{ $paymentStatusLabel }}</dd>
            </div>
            <div class="md:col-span-2">
                <dt class="text-sm text-gray-500">فرع صرف البطاقة</dt>
                <dd class="font-medium">{{ $request->redemptionBranch?->name_ar ?: '—' }}</dd>
            </div>
        </dl>
    </x-filament::section>

    <x-filament::section heading="الملاحظات">
        <div class="space-y-4">
            <div>
                <div class="text-sm text-gray-500">ملاحظات الزبون</div>
                <div class="mt-1 whitespace-pre-line">{{ $request->customer_notes ?: '—' }}</div>
            </div>
            <div>
                <div class="text-sm text-gray-500">ملاحظات الإدارة</div>
                <div class="mt-1 whitespace-pre-line">{{ $request->admin_notes ?: '—' }}</div>
            </div>
        </div>
    </x-filament::section>

    @if ($request->giftCards->isNotEmpty())
        <x-filament::section heading="البطاقات الصادرة">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                    <tr class="border-b border-gray-200 text-right dark:border-gray-700">
                        <th class="px-3 py-2">الرمز</th>
                        <th class="px-3 py-2">القيمة</th>
                        <th class="px-3 py-2">الرصيد</th>
                        <th class="px-3 py-2">الحالة</th>
                        <th class="px-3 py-2">الانتهاء</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($request->giftCards as $card)
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <td class="px-3 py-2" dir="ltr">{{ $card->code }}</td>
                            <td class="px-3 py-2">{{ $money($card->amount, $card->currency) }}</td>
                            <td class="px-3 py-2">{{ $money($card->balance, $card->currency) }}</td>
                            <td class="px-3 py-2">{{ $card->status }}</td>
                            <td class="px-3 py-2">{{ $card->expires_at?->format('Y-m-d H:i') ?: '—' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @endif
</div>
