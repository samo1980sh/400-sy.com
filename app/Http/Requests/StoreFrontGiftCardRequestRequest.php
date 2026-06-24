<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreFrontGiftCardRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::guard('customer')->check();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'display_name_type' => $this->input('display_name_type', 'requester'),
            'fulfillment_method' => $this->input('fulfillment_method', 'branch_pickup'),
            'currency' => strtoupper((string) $this->input('currency', 'SYP')),
            'card_quantity' => (int) $this->input('card_quantity', 1),
            'requester_name' => trim((string) $this->input('requester_name')),
            'recipient_name' => trim((string) $this->input('recipient_name')),
            'recipient_mobile' => trim((string) $this->input('recipient_mobile')),
        ]);
    }

    public function rules(): array
    {
        return [
            'display_name_type' => ['required', 'string', 'in:recipient,requester,anonymous'],
            'requester_name' => ['required', 'string', 'max:190'],
            'recipient_name' => ['nullable', 'required_if:display_name_type,recipient', 'string', 'max:190'],
            'card_quantity' => ['required', 'integer', 'min:1', 'max:50'],
            'recipient_mobile' => ['nullable', 'string', 'max:40'],
            'card_amount' => ['required', 'numeric', 'min:1', 'max:999999999'],
            'currency' => ['required', 'string', 'size:3'],

            'fulfillment_method' => ['required', 'string', 'in:branch_pickup,delivery'],
            'pickup_branch_id' => ['nullable', 'required_if:fulfillment_method,branch_pickup', 'integer', 'exists:branches,id'],
            'shipping_method_id' => ['nullable', 'required_if:fulfillment_method,delivery', 'integer', 'exists:shipping_methods,id'],
            'delivery_address' => ['nullable', 'required_if:fulfillment_method,delivery', 'string', 'max:2000'],

            'payment_method_id' => ['required', 'integer', 'exists:payment_methods,id'],
            'redemption_branch_id' => ['required', 'integer', 'exists:branches,id'],
            'customer_notes' => ['nullable', 'string', 'max:2000'],
            'terms' => ['accepted'],
        ];
    }

    public function attributes(): array
    {
        return [
            'display_name_type' => 'الاسم الظاهر على البطاقة',
            'requester_name' => 'اسم طالب البطاقة',
            'recipient_name' => 'اسم المستفيد',
            'card_quantity' => 'عدد البطاقات',
            'recipient_mobile' => 'رقم المستفيد',
            'card_amount' => 'قيمة البطاقة',
            'currency' => 'العملة',
            'fulfillment_method' => 'طريقة الاستلام',
            'pickup_branch_id' => 'فرع الاستلام',
            'shipping_method_id' => 'طريقة التوصيل',
            'delivery_address' => 'عنوان التوصيل',
            'payment_method_id' => 'طريقة الدفع',
            'redemption_branch_id' => 'فرع استخدام البطاقة',
            'customer_notes' => 'الملاحظات',
            'terms' => 'الشروط والتعليمات',
        ];
    }

    public function messages(): array
    {
        return [
            'recipient_name.required_if' => 'يرجى إدخال اسم المستفيد عند اختيار إظهار اسم المستفيد على البطاقة.',
            'pickup_branch_id.required_if' => 'يرجى اختيار فرع الاستلام.',
            'shipping_method_id.required_if' => 'يرجى اختيار طريقة التوصيل.',
            'delivery_address.required_if' => 'يرجى إدخال عنوان التوصيل.',
            'terms.accepted' => 'يرجى الموافقة على تعليمات وشروط بطاقة الهدية.',
        ];
    }
}
